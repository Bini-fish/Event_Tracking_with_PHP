# PHP Files — Detailed Explanation (Part 1 of 2)

> **📊 Diagrams not rendering?** Open [PROJECT_DIAGRAMS.html](PROJECT_DIAGRAMS.html) in your browser.

> **Part 1** — Configuration · Database · Core Includes · Models (23 files)
> → [Continue to Part 2](PHP_FILES_DETAILED_EXPLANATION_PART2.md) — Front Controller · Action Handlers · Views · Scripts (35 files)

---

## Introduction

In our City-Wide Event Tracking System we have **58 PHP files** total. This document explains every single one in a logical learning sequence — starting from the foundational files that everything else depends on, and building up to the user-facing pages. By reading this document in order, every team member will understand how the entire codebase fits together.

### File Inventory (58 files)

| Folder | Count | Role |
|---|---|---|
| `config/` | 3 | Application configuration, database, routing |
| `includes/` | 7 | Shared middleware — session, auth, validation, helpers |
| `models/` | 6 | Data access layer (one per database table) |
| `public/index.php` | 1 | Front controller (single entry point) |
| `public/actions/auth/` | 4 | Authentication action handlers |
| `public/actions/events/` | 9 | Event-related action handlers |
| `public/actions/admin/` | 3 | Admin action handlers |
| `views/layout/` | 2 | Header and footer templates |
| `views/pages/` | 13 | Page templates |
| `views/partials/` | 5 | Reusable UI components |
| `scripts/` | 4 | CLI maintenance scripts |
| Root | 1 | Seed data repair script |

---

## Group 1: Configuration Files (`config/`)

These are the first files loaded on every request. They define how our application connects to the database, what constants govern behaviour, and how URLs map to pages.

---

### File 1: `config.php`

- **Location**: `config/config.php`
- **Purpose**: The central nervous system of our application configuration. Every request starts by loading this file.

**What this file does:**
We defined all application-wide constants and security settings here. It sets the app name, environment mode, database credentials, rate-limit thresholds, event duration rules, and computes the `BASE_URL` dynamically so our app works regardless of where it's deployed.

**Key sections:**

```php
declare(strict_types=1);  // Enforced in EVERY file — catches type errors early

const APP_NAME = 'City-Wide Event Tracking System';
const APP_ENV  = 'development';
```

**Environment variable overrides** — We designed credentials to read from environment variables first, falling back to defaults for local dev:
```php
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'city_events');
```

**Dynamic BASE_URL computation** — We calculate the base URL by comparing the `public/` directory against the document root, handling both Apache and built-in PHP server:
```php
$publicReal  = realpath(__DIR__ . '/../public');
$docRootReal = realpath($_SERVER['DOCUMENT_ROOT']);
$basePath = substr($publicReal, strlen($docRootReal));
define('BASE_URL', $scheme . '://' . $host . $basePath . '/');
```

**`bootstrap_request_security()`** — Called automatically at the bottom of the file, this function sets HTTP security headers on every response:
- `X-Frame-Options: SAMEORIGIN` — prevents clickjacking
- `X-Content-Type-Options: nosniff` — prevents MIME sniffing
- `Referrer-Policy: strict-origin-when-cross-origin` — limits referrer leakage
- `Permissions-Policy: camera=(), microphone=(), geolocation=()` — disables browser APIs we don't need
- `Strict-Transport-Security` — enables HSTS when on HTTPS

**Key PHP concepts**: `declare(strict_types=1)`, `define()` vs `const`, `getenv()`, ternary operator `?:`, `str_starts_with()`, `realpath()`, `header()`, conditional constant definition with `defined()`.

**Interacts with**: Loaded by every other file via `require_once`.

---

### File 2: `db.php`

- **Location**: `config/db.php`
- **Purpose**: Provides a single shared PDO database connection for the entire application.

**What this file does:**
We implemented a **singleton pattern** using a static variable inside `get_pdo()`. No matter how many times this function is called during a request, only one database connection is created.

**The `get_pdo()` function:**
```php
function get_pdo(): PDO {
    static $pdo = null;          // Persists between calls
    if ($pdo instanceof PDO) return $pdo;  // Return existing

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME
         . ';charset=utf8mb4;connect_timeout=5';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
```

**Special: `LoggedPDOStatement` class** — We created a custom PDO statement subclass that wraps `execute()` to measure query duration. When `ENABLE_SQL_QUERY_LOGGING` is on, it logs queries slower than `SQL_SLOW_QUERY_MS` to `error_log`. This gives us production monitoring without external tools.

**Special: Production safety check** — If the app detects it's running in production with the `root` MySQL user, it writes a security warning to the error log.

**Key PHP concepts**: Singleton pattern via `static` variable, PDO configuration, class inheritance (`extends PDOStatement`), `microtime(true)` for benchmarking, `error_log()`.

**Interacts with**: `config.php` (loaded first for DB constants). Called by every model file.

---

### File 3: `routes.php`

- **Location**: `config/routes.php`
- **Purpose**: Maps page name strings to their corresponding view file paths.

**What this file does:**
We defined a simple associative array `$routes` that the front controller (`index.php`) uses to resolve which view file to load:

```php
$routes = [
    'landing'        => __DIR__ . '/../views/pages/landing.php',
    'event_feed'     => __DIR__ . '/../views/pages/event_feed.php',
    'dashboard'      => __DIR__ . '/../views/pages/dashboard.php',
    'admin_events'   => __DIR__ . '/../views/pages/admin_events.php',
    // ... 12 routes total
];
```

**Why this design?** Instead of a complex routing framework, we use a flat key-value map. A user requests `?page=event_feed`, the front controller looks up `$routes['event_feed']`, and `require`s that file. Simple, fast, and easy to understand.

**Key PHP concepts**: Associative arrays, `__DIR__` magic constant for relative paths.

**Interacts with**: Used exclusively by `public/index.php`.

---

## Group 2: Core Includes (`includes/`)

These 7 files form our "middleware" layer — shared functionality that action handlers and views depend on. They are loaded via `require_once` so they're never duplicated.

---

### File 4: `session.php`

- **Location**: `includes/session.php`
- **Purpose**: The most critical infrastructure file. Manages PHP sessions, user login/logout, CSRF protection, role management, and the remember-me cookie system.

**What this file does:**
This 327-line file is the backbone of our authentication system. It auto-starts the session and auto-checks the remember-me cookie on every page load.

**Main functions (13 total):**

| Function | Purpose |
|---|---|
| `start_session_if_needed()` | Starts session with secure cookie params (`httponly`, `samesite=Strict`, `secure`) |
| `login_user($user)` | Stores user in session + calls `session_regenerate_id(true)` to prevent session fixation |
| `logout_user()` | Wipes `$_SESSION`, deletes session cookie, deletes remember-me token, calls `session_destroy()` |
| `current_user_id()` | Returns `$_SESSION['user_id']` or `null` |
| `current_user_role()` | Returns `$_SESSION['user_role']` or `null` |
| `get_csrf_token()` | Generates 64-char hex token per session via `random_bytes(32)` |
| `verify_csrf_token($token)` | Timing-safe comparison using `hash_equals()` |
| `issue_remember_me_token($userId)` | Creates split-token cookie: `selector:validator` |
| `login_from_remember_me_if_possible()` | Auto-login on page load if valid cookie exists |
| `set_admin_view_role($role)` | Stores admin's contextual view role |
| `current_effective_role()` | Returns admin's view role or actual role for non-admins |

**Special — Split-token remember-me**: We generate a `selector` (16 hex chars) for database lookup and a `validator` (64 hex chars) as the secret. Only the SHA-256 hash of the validator is stored in the database. On each auto-login, the old token is deleted and a new one is issued (token rotation).

**Special — Auto-execution**: The last two lines execute automatically when the file is loaded:
```php
start_session_if_needed();       // Line 28
login_from_remember_me_if_possible();  // Line 325
```

**Key PHP concepts**: `session_start()`, `session_regenerate_id()`, `session_destroy()`, `$_SESSION` superglobal, `$_COOKIE`, `setcookie()`, `random_bytes()`, `hash()`, `hash_equals()`, `bin2hex()`.

---

### File 5: `helpers.php`

- **Location**: `includes/helpers.php`
- **Purpose**: Small utility functions used everywhere — output escaping, URL building, redirects, flash messages, and form data preservation.

**Main functions (11 total):**

| Function | What It Does |
|---|---|
| `e($value)` | `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` — our XSS prevention function, used in every view |
| `url_for($page, $params)` | Builds `BASE_URL/index.php?page=X&key=val` URLs |
| `redirect($page, $params)` | Sends `Location` header + `exit` — the PRG pattern |
| `set_flash($type, $message)` | Stores messages in `$_SESSION['flash'][$type][]` |
| `get_flashes()` | Returns and clears all flash messages (one-time read) |
| `set_old_input($input)` | Stores form data in session for post-redirect preservation |
| `old($key, $default)` | Retrieves old input with dot-notation (`'register.email'`) |
| `set_validation_errors($errors)` | Stores field-specific errors for UI highlighting |
| `has_datetime_passed($dateTime)` | Checks if an event datetime is in the past |
| `log_exception($e, $context)` | Logs exception message + stack trace to `error_log` |

**Special — `old()` with dot-notation**: We built a nested array accessor so views can retrieve old form data cleanly:
```php
// In the view:
value="<?= e(old('register.email')) ?>"
// Traverses $_SESSION['old_input']['register']['email']
```

**Key PHP concepts**: `htmlspecialchars()`, `header()` for redirects, `http_build_query()`, `$_SESSION` for flash messaging, `DateTimeImmutable`, `Throwable` interface.

---

### File 6: `validation.php`

- **Location**: `includes/validation.php`
- **Purpose**: Our centralised validation library. Every form submission uses these functions.

**What this file does:**
We built a validation library where every function returns `true` on success or a human-readable error `string` on failure. This consistent contract makes validation clean across the entire app.

**Validator functions (12 total):**

| Function | Validates |
|---|---|
| `validate_required($value, $label)` | Non-empty after trim |
| `validate_max_length($value, $max, $label)` | String length ceiling (mb_strlen aware) |
| `validate_min_length($value, $min, $label)` | String length floor |
| `validate_email($email, $label)` | `filter_var(FILTER_VALIDATE_EMAIL)` |
| `validate_password_strength($pw, $min, $req, $label)` | Min 8 chars + letter + digit |
| `validate_password_confirmation($pw, $confirm, $label)` | Match with `hash_equals()` |
| `validate_positive_int($value, $label)` | Must be > 0 |
| `validate_event_datetime_format($value, $label)` | Tries 4 datetime formats |
| `validate_event_datetime_bounds($value, ...)` | Future date, not too far ahead |
| `validate_file_upload($file, $max, $mimes, $label)` | Size, MIME, upload error checks |
| `collect_validation_errors($checks)` | Aggregates multiple validators into keyed error array |
| `request_string($data, $key)` / `request_int(...)` | Safe input extraction with trim/cast |

**Special — `collect_validation_errors()`**: This function lets us show ALL validation errors at once:
```php
$errors = collect_validation_errors([
    'title'    => validate_required($title, 'Title'),
    'capacity' => validate_positive_int($capacity, 'Capacity'),
]);
// Returns only the failures as ['field' => 'error message']
```

**Special — `parse_event_datetime()`**: Tries multiple datetime formats (`Y-m-d H:i:s`, `Y-m-d\TH:i:s`, `Y-m-d\TH:i`, `Y-m-d`) so our app accepts dates from HTML5 datetime-local inputs and manual entry.

**Key PHP concepts**: Union return types (`true|string`), `filter_var()`, `DateTimeImmutable::createFromFormat()`, `preg_match()`, `finfo` class for MIME detection, `mb_strlen()`.

---

### File 7: `auth_guard.php`

- **Location**: `includes/auth_guard.php`
- **Purpose**: Route protection functions that action handlers call to enforce authentication and role requirements.

**Functions (5 total):**

| Function | Rule |
|---|---|
| `require_login()` | Redirects to login if `current_user_id() === null` |
| `require_role($role)` | Calls `require_login()` then checks `current_effective_role()` |
| `require_admin()` | Requires actual `admin` role (not effective/view role) |
| `require_organizer()` | Requires effective role = `organizer` |
| `require_admin_or_organizer()` | Either role is acceptable |

**Special**: `require_admin()` checks `user_has_role('admin')` — the **actual** role, not the effective view role. This means an admin who has switched to "organizer view" can still access admin-only pages. But `require_organizer()` uses `current_effective_role()`, so an admin in organizer view can access organizer features.

**Key PHP concepts**: Guard clause pattern, `http_response_code(403)`, `exit` for stopping execution.

---

### File 8: `policy.php`

- **Location**: `includes/policy.php`
- **Purpose**: Fine-grained authorisation policies for specific actions on specific resources.

**What this file does:**
While `auth_guard.php` checks "can this user access this page?", `policy.php` answers "can this user do this specific thing to this specific event?"

**Policy functions (7 total):**

| Function | Rule |
|---|---|
| `can_view_event($userId, $event)` | Admin, owner, or verified event |
| `can_rsvp_event($userId, $event)` | Event MUST be verified (approved) |
| `can_edit_event($userId, $event)` | Admin or event owner |
| `can_comment_event($userId, $event)` | Same as can_view |
| `can_create_event($userId)` | Organizer or admin role |
| `can_manage_rsvps($userId, $event)` | Admin or event owner |
| `can_submit_feedback($userId)` | Any logged-in user (DB checks done elsewhere) |

**Special**: `can_rsvp_event()` has a hard rule — `is_verified` MUST be 1, no exceptions. Even admins and event owners cannot RSVP to an unapproved event. This prevents RSVPs on events that haven't been reviewed yet.

**Key PHP concepts**: Policy/authorisation pattern, nullable parameters, boolean logic with early returns.

---

### File 9: `rate_limit.php`

- **Location**: `includes/rate_limit.php`
- **Purpose**: Database-backed sliding-window rate limiter to prevent abuse of RSVP, comment, and feedback submissions.

**How it works:**
```php
function rate_limit_check(string $action, int $maxHits, int $windowSeconds): bool
```
1. Builds a composite key: `action|IP|user_id`
2. Deletes expired hits older than the window
3. Counts remaining hits for this key
4. If count ≥ max → returns `false` (blocked)
5. Otherwise inserts a new hit and returns `true`

**Special — Fail-open design**: If the database is unavailable, `rate_limit_check()` returns `true` (allows the action). We made this choice because blocking all users due to a rate-limit table error would be worse than temporarily allowing extra requests.

**Key PHP concepts**: Sliding window algorithm, composite keys, `try/catch` with fail-open strategy, `NOW()` and `INTERVAL` in SQL.

---

### File 10: `upload.php`

- **Location**: `includes/upload.php`
- **Purpose**: Centralised image upload handler used by event creation and editing.

**`handle_image_upload()` return values:**
- `string` — relative path to stored file (success)
- `null` — no file uploaded (that's OK, image is optional)
- `false` — validation/storage failed (flash error already set)

**Security measures:**
1. MIME type verified with `finfo` (not just file extension)
2. Only `image/jpeg`, `image/png`, `image/gif` allowed
3. Max 5 MB enforced
4. **Random filename**: `bin2hex(random_bytes(16)) . '.' . $ext` — prevents path traversal and filename collisions
5. `is_uploaded_file()` check prevents local file inclusion attacks

**`delete_uploaded_image()`** — Safely removes old images when events are updated with new ones.

**Key PHP concepts**: `$_FILES` superglobal, `UPLOAD_ERR_*` constants, `finfo` class, `move_uploaded_file()`, `random_bytes()`, `mkdir()` with recursive flag.

---

## Group 3: Models (`models/`)

Our models are pure data-access functions — no classes, no ORM. Each file corresponds to one database table and contains all SQL queries for that entity. Every query uses prepared statements.

---

### File 11: `UserModel.php`

- **Location**: `models/UserModel.php`
- **Purpose**: All user-related database operations — find, create, authenticate, update, delete.

**Functions (8 total):**

| Function | SQL Operation |
|---|---|
| `user_find_by_email($email)` | `SELECT * FROM users WHERE email = ?` |
| `user_find_by_id($id)` | `SELECT * FROM users WHERE id = ?` |
| `user_create($name, $email, $hash, $role)` | `INSERT INTO users ...` |
| `user_authenticate($email, $password)` | Find by email + `password_verify()` |
| `user_update_password_hash($id, $hash)` | `UPDATE users SET password_hash = ?` |
| `user_get_all($roleFilter)` | `SELECT` with subquery counts for events and RSVPs |
| `user_update_role($userId, $newRole)` | `UPDATE` with whitelist validation |
| `user_delete($userId)` | Transaction: delete feedback → comments → RSVPs → events → user |
| `user_count_by_role()` | `GROUP BY role` aggregate for admin stats |

**Special — `user_delete()` cascade**: We manually delete related records in a transaction rather than relying solely on DB cascades, ensuring complete cleanup:
```php
$pdo->beginTransaction();
$pdo->prepare('DELETE FROM feedback WHERE user_id = ?')->execute([$userId]);
$pdo->prepare('DELETE FROM comments WHERE user_id = ?')->execute([$userId]);
$pdo->prepare('DELETE FROM rsvps WHERE user_id = ?')->execute([$userId]);
$pdo->prepare('DELETE FROM events WHERE organizer_id = ?')->execute([$userId]);
$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
$pdo->commit();
```

**Key PHP concepts**: `password_verify()`, `password_hash()`, named parameters (`:name`), `lastInsertId()`, `beginTransaction()`/`commit()`/`rollBack()`, subqueries.

---

### File 12: `EventModel.php`

- **Location**: `models/EventModel.php`
- **Purpose**: All event-related database operations — CRUD, approval, overlap detection, categories.

**Functions (10 total):**

| Function | Purpose |
|---|---|
| `event_get_approved_events($page, $perPage)` | Paginated query with `LIMIT/OFFSET` |
| `event_count_approved()` | Count for pagination math |
| `event_get_pending_events()` | Unapproved events for admin queue |
| `event_get_by_id($id)` | Single event lookup |
| `event_get_by_organizer($orgId)` | Organiser's events with RSVP counts (subquery) |
| `event_detect_organizer_overlap(...)` | Time-range overlap detection |
| `event_create(...)` | INSERT with `is_verified=0` (always starts pending) |
| `event_update_basic(...)` | UPDATE all editable fields |
| `event_get_categories()` | `SELECT DISTINCT category` for filter dropdowns |
| `event_delete($eventId, $orgId)` | DELETE with ownership check |
| `event_set_verified($eventId, $bool)` | Toggle approval status |

**Special — Overlap detection SQL**: We detect time overlaps between events using the standard interval overlap formula:
```sql
WHERE e.organizer_id = :org
  AND e.event_date < :end    -- existing starts before new ends
  AND e.event_end  > :start  -- existing ends after new starts
```

**Key PHP concepts**: `PDO::PARAM_INT` for `LIMIT`/`OFFSET` binding, `bindValue()` vs `execute()`, `FETCH_COLUMN` for flat arrays.

---

### File 13: `RsvpModel.php`

- **Location**: `models/RsvpModel.php`
- **Purpose**: The most complex model — handles RSVP lifecycle, approval with row locking, conflict detection, and capacity enforcement.

**Functions (12 total):**

| Function | Purpose |
|---|---|
| `rsvp_count_approved($eventId)` | Count only `status='approved'` RSVPs |
| `rsvp_remaining_seats($eventId, $capacity)` | `capacity - approved_count` |
| `rsvp_user_has_rsvped($eventId, $userId)` | Check if RSVP exists (any status) |
| `rsvp_user_status($eventId, $userId)` | Return status string |
| `rsvp_user_is_approved_attendee(...)` | Status check for feedback gating |
| `rsvp_can_rsvp(...)` | Not already RSVP'd + seats available |
| `rsvp_detect_conflicts($eventId, $userId)` | Time-overlap with user's other RSVPs |
| `rsvp_get_for_event($eventId, $status)` | All RSVPs joined with user info |
| `rsvp_add($eventId, $userId)` | INSERT with `status='pending'` |
| `rsvp_approve($rsvpId, $approverId)` | **Transaction + row lock + capacity check** |
| `rsvp_reject($rsvpId, $approverId)` | Update to `status='rejected'` |
| `rsvp_get_pending_for_organizer($orgId)` | Pending RSVPs across all organiser's events |

**Special — `rsvp_approve()` concurrency safety**: This is our most sophisticated database operation. It uses `SELECT ... FOR UPDATE` inside a transaction to lock the RSVP row, re-counts approved RSVPs, and only approves if capacity allows. This prevents overbooking even under concurrent requests.

**Key PHP concepts**: `FOR UPDATE` row locking, transactions, JOINs, `IN ('pending','approved')`, composite queries.

---

### File 14: `CommentModel.php`

- **Location**: `models/CommentModel.php`
- **Purpose**: Comment and reply data access — supports one level of threading via `parent_comment_id`.

**Functions (4 total):**
- `comment_get_by_event($eventId)` — All comments for an event, joined with user names, ordered chronologically
- `comment_get_by_id($commentId)` — Single comment lookup (for reply validation)
- `comment_add($eventId, $userId, $body)` — Top-level comment (`parent_comment_id = NULL`)
- `comment_add_reply($eventId, $userId, $parentId, $body)` — Reply to existing comment

**Key PHP concepts**: Self-referencing foreign keys, JOIN for denormalization, `NULL` handling.

---

### File 15: `FeedbackModel.php`

- **Location**: `models/FeedbackModel.php`
- **Purpose**: Post-event star rating and comment storage with aggregate statistics.

**Functions (5 total):**
- `feedback_submit(...)` — INSERT rating (1-5) + optional comment
- `feedback_has_submitted($eventId, $userId)` — Duplicate prevention check
- `feedback_get_for_event($eventId)` — All feedback with user names
- `feedback_summary($eventId)` — `AVG(rating)` and `COUNT(*)` for one event
- `feedback_summaries_for_events($eventIds)` — **Batch query** using `IN(...)` with dynamic placeholders

**Special — Batch query**: Instead of N+1 queries on the dashboard, we fetch all feedback summaries in one query:
```php
$placeholders = implode(',', array_fill(0, count($eventIds), '?'));
$stmt = $pdo->prepare("SELECT event_id, AVG(rating), COUNT(*) FROM feedback WHERE event_id IN ({$placeholders}) GROUP BY event_id");
```

**Key PHP concepts**: `array_fill()` for dynamic placeholders, `ROUND(AVG(...), 1)`, `GROUP BY`, N+1 query prevention.

---

### File 16: `RememberTokenModel.php`

- **Location**: `models/RememberTokenModel.php`
- **Purpose**: CRUD operations for the `remember_tokens` table used by the persistent login system.

**Functions (5 total):**
- `remember_token_create(...)` — INSERT selector + validator hash + expiry
- `remember_token_find_by_selector($selector)` — Lookup by public selector key
- `remember_token_delete_by_selector($selector)` — Delete after use (rotation)
- `remember_token_delete_for_user($userId)` — Clear all tokens for a user
- `remember_token_delete_expired()` — Cleanup: `DELETE WHERE expires_at <= NOW()`

**Key PHP concepts**: Token-based authentication storage, indexed lookups on `selector`, expiry-based cleanup.

---

## Recommended Reading Order (Part 1)

For the best learning experience, study these files in this exact order:

1. **`config/config.php`** — Understand all constants and security bootstrap
2. **`config/db.php`** — Learn the PDO singleton and connection setup
3. **`includes/helpers.php`** — Learn the utility functions used everywhere
4. **`includes/session.php`** — Understand session management and CSRF
5. **`includes/validation.php`** — Study the validation contract pattern
6. **`includes/auth_guard.php`** — See how routes are protected
7. **`includes/policy.php`** — Understand fine-grained authorisation
8. **`includes/rate_limit.php`** — Learn the rate limiting strategy
9. **`includes/upload.php`** — Study secure file upload handling
10. **`models/UserModel.php`** — Start with the simplest model
11. **`models/EventModel.php`** — Learn event data access patterns
12. **`models/RsvpModel.php`** — Study the most complex model (transactions, locking)
13. **`models/CommentModel.php`** — Simple threading model
14. **`models/FeedbackModel.php`** — Aggregation queries
15. **`models/RememberTokenModel.php`** — Token storage for remember-me
16. **`config/routes.php`** — Quick read — the route map

---

> **Continue to [Part 2 →](PHP_FILES_DETAILED_EXPLANATION_PART2.md)** for the Front Controller, all Action Handlers, View Templates, and CLI Scripts (35 remaining files).
