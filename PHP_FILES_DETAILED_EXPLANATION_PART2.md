# PHP Files — Detailed Explanation (Part 2 of 2)

> ← [Back to Part 1](PHP_FILES_DETAILED_EXPLANATION.md) — Configuration · Database · Core Includes · Models

> **Part 2** — Front Controller · Authentication Actions · Event Actions · Admin Actions · Views · Scripts (42 files)

---

## Group 4: Front Controller

### File 17: `public/index.php`

- **Location**: `public/index.php`
- **Purpose**: The **single entry point** for all page requests. Every URL in our app goes through this file.

**What this file does:**
1. Loads config, session, helpers, and routes
2. Reads `$_GET['page']` to determine which page the user wants
3. Defaults to `landing` for localhost, `event_feed` for logged-in users
4. Looks up the view file from `$routes` array
5. Returns 404 if the page name isn't in the map
6. Renders the page using the **shared layout pattern**: `header → flash_messages → page content → footer`

**Key code flow:**
```php
$page = $_GET['page'] ?? '';
$viewFile = $routes[$page] ?? null;

if ($viewFile === null) {
    http_response_code(404);
    $viewFile = __DIR__ . '/../views/pages/404.php';
}

// Shared layout wrapping
require __DIR__ . '/../views/layout/header.php';
require __DIR__ . '/../views/partials/flash_messages.php';
require $viewFile;
require __DIR__ . '/../views/layout/footer.php';
```

**Special — `$authFluidMain`**: We track which pages are "auth pages" (login, register, landing) to apply a fluid (full-width) layout class instead of the standard container.

**Key PHP concepts**: Front controller pattern, `$_GET` superglobal, `require` vs `require_once`, `http_response_code()`, `$GLOBALS` for sharing state.

---

## Group 5: Authentication Action Handlers (`public/actions/auth/`)

These files handle POST form submissions for login, registration, and logout. They validate, process, and redirect — following the **PRG (Post/Redirect/Get)** pattern.

---

### File 18: `auth/login.php`

- **Location**: `public/actions/auth/login.php`
- **Purpose**: Handles login form submission with throttling, credential verification, role validation, remember-me, and safe redirects.

**This is our longest action handler (238 lines).** Key sections:

1. **Login throttling system** (lines 11–76): 3 helper functions implement session-based rate limiting — 5 attempts per 15 minutes per email+IP combination. Uses `SHA-256(email|IP)` as the throttle key.

2. **Input validation** (lines 87–119): Validates email format, required fields, and checks throttle status before touching the database.

3. **Credential verification** (lines 132–163): Finds user by email, then `password_verify()`. On failure: generic "Invalid credentials" message — never reveals whether the email exists.

4. **Role-specific login enforcement** (lines 165–192): If the form includes `login_role` (from role-specific login pages), the user's actual role must match. Mismatch = "You are not authorized to access this panel."

5. **Post-login setup** (lines 194–212): `login_user()`, clear throttle, check `password_needs_rehash()` for transparent hash upgrades, optionally issue remember-me token.

6. **Safe redirect** (lines 214–237): Validates `$_POST['redirect']` to prevent open redirect attacks — only allows relative paths starting with `/` (not `//`).

**Key PHP concepts**: `password_verify()`, `password_needs_rehash()`, session-based throttling, `parse_url()` for redirect validation, `hash()` for key generation.

---

### File 19: `auth/register.php`

- **Location**: `public/actions/auth/register.php`
- **Purpose**: Creates new user accounts with full validation and auto-login.

**Flow:**
1. CSRF check → validate name, email, password strength, password match
2. Restrict role to `attendee` or `organizer` only (no self-registration as admin)
3. Check email uniqueness, then `password_hash(PASSWORD_DEFAULT)`
4. `user_create()` → auto-login via `login_user()` → redirect to appropriate page
5. Catches `PDOException` with code `23000` for race condition duplicate emails

**Special — `remember_register_input()`**: Stores name, email, and role (never password) for form re-population after validation errors.

---

### File 20: `auth/logout.php`

- **Location**: `public/actions/auth/logout.php`
- **Purpose**: Destroys user session and clears all authentication state.

**Only 21 lines.** Validates CSRF token on POST, calls `logout_user()` (which handles session wipe + remember-me cleanup), sets success flash, and redirects to event feed.

---

### File 21: `auth/switch_view_role.php`

- **Location**: `public/actions/auth/switch_view_role.php`
- **Purpose**: Allows admins to switch their contextual view between admin, organizer, and attendee perspectives.

**Flow:** Validates that the user is actually an admin → validates CSRF → validates the requested role → `set_admin_view_role()` → redirects to the appropriate page for that role (dashboard for organizer, event_feed for attendee, admin_events for admin).

---

## Group 6: Event Action Handlers (`public/actions/events/`)

These 9 files handle all event-related form submissions.

---

### File 22: `events/create_event.php`

- **Location**: `public/actions/events/create_event.php`
- **Purpose**: Full event creation pipeline with all-at-once validation, overlap detection, and centralised image upload.

**Flow:** `require_organizer()` → `can_create_event()` → CSRF → read inputs → preserve old input → `collect_validation_errors()` for ALL fields → cross-field checks (start < end, duration ≥ 30 min) → organiser buffer overlap check → `handle_image_upload()` → `event_create()` → flash + redirect.

**Special**: Events always start with `is_verified=0` — they're invisible to the public until an admin approves them.

---

### File 23: `events/update_event.php`

- **Location**: `public/actions/events/update_event.php`
- **Purpose**: Event editing with row locking, image replacement, and automatic re-approval for organiser edits.

**This is our most complex action handler (177 lines).** Key features:
- Uses `SELECT ... FOR UPDATE` to lock the event row during the transaction
- If an organiser edits an approved event → `is_verified` is reset to 0 (needs re-approval)
- Admin edits are trusted and don't trigger re-approval
- Old image is only deleted after the new one is successfully uploaded
- Records edit audit trail: `edited_at`, `edited_by`, `edit_reason`

---

### File 24: `events/delete_event.php`

- **Location**: `public/actions/events/delete_event.php`
- **Purpose**: Event deletion restricted to the event owner or admin.

Uses `can_edit_event()` policy check. Admin can delete any event (passes original `organizer_id` to bypass the ownership check in `event_delete()`).

---

### File 25: `events/rsvp.php`

- **Location**: `public/actions/events/rsvp.php`
- **Purpose**: RSVP submission with approval gating, rate limiting, and time-conflict detection.

**Flow:** Auth check → CSRF → rate limit → event must be approved (`is_verified=1`) → policy check → not already RSVP'd → **conflict detection** → if conflicts found, redirect with modal trigger → if confirmed or no conflicts, `rsvp_add()` with `status='pending'`.

**Special — Conflict flow**: Stores conflict data in `$_SESSION['rsvp_conflicts']` and redirects with `show_conflict=1` query param. The event detail page reads this to show a confirmation modal.

---

### File 26: `events/approve_rsvp.php`

- **Location**: `public/actions/events/approve_rsvp.php`
- **Purpose**: Organiser or admin approves a pending RSVP.

Fetches the RSVP joined with the event to verify the approver owns the event (or is admin). Then calls `rsvp_approve()` which uses row locking and capacity checks inside a transaction.

---

### File 27: `events/reject_rsvp.php`

- **Location**: `public/actions/events/reject_rsvp.php`
- **Purpose**: Organiser or admin rejects a pending RSVP.

Same ownership verification pattern as approve. Calls `rsvp_reject()` which only updates if `status='pending'`.

---

### File 28: `events/comment.php`

- **Location**: `public/actions/events/comment.php`
- **Purpose**: Handles both top-level comments and organiser replies.

**Access control logic:**
- Regular attendees can only comment **after the event ends**
- Organisers and admins can comment anytime (for pre-event announcements)
- Only the event organiser or admin can post **replies** to existing comments
- Reply validation: parent comment must exist and belong to the same event

---

### File 29: `events/submit_feedback.php`

- **Location**: `public/actions/events/submit_feedback.php`
- **Purpose**: Post-event star rating (1-5) and optional comment submission.

**Four gates checked in order:**
1. Event must have ended (`has_datetime_passed()`)
2. User must be an approved attendee (`rsvp_user_is_approved_attendee()`)
3. User must not have already submitted (`feedback_has_submitted()`)
4. Rating must be 1-5

---

### File 30: `events/toggle_approval.php`

- **Location**: `public/actions/events/toggle_approval.php`
- **Purpose**: Admin-only action to approve or unapprove events.

**Only 44 lines.** Calls `require_admin()`, verifies CSRF, then `event_set_verified($eventId, $newStatus)`. Simple but critical — this is the gateway that makes events visible to the public.

---

## Group 7: Admin Action Handlers (`public/actions/admin/`)

---

### File 31: `admin/add_user.php`

- **Location**: `public/actions/admin/add_user.php`
- **Purpose**: Admin creates user accounts (can assign any role, including admin).

Uses `collect_validation_errors()` for all-at-once validation, checks email uniqueness, catches `23000` SQLSTATE for race-condition duplicates. Unlike self-registration, this allows creating admin accounts.

---

### File 32: `admin/update_user_role.php`

- **Location**: `public/actions/admin/update_user_role.php`
- **Purpose**: Admin changes a user's role.

**Self-protection:** `$targetId === $actorId` → "You cannot change your own role." Validates role against whitelist.

---

### File 33: `admin/delete_user.php`

- **Location**: `public/actions/admin/delete_user.php`
- **Purpose**: Admin deletes a user account with cascade cleanup.

**Self-protection:** "You cannot delete your own account." Calls `user_delete()` which cascade-deletes in a transaction.

---

## Group 8: View Templates — Layout (`views/layout/`)

---

### File 34: `layout/header.php`

- **Location**: `views/layout/header.php`
- **Purpose**: The HTML `<head>`, navigation bar, and opening `<main>` tag. Loaded on every page.

**What this file does:**
- Outputs `<!DOCTYPE html>`, meta tags, Google Fonts (Inter + Manrope), CSS link, Lottie player script
- Renders **role-aware navigation** — different nav links for guests, attendees, organisers, and admins
- Admins get additional "Switch to Organizer/Attendee" buttons (POST forms with CSRF tokens)
- Shows an "admin view notice" bar when admin is in switched view mode
- Includes hamburger menu toggle (JS) for mobile responsive nav

**Special — Every logout/switch-role link is a POST form** with CSRF token, not a GET link. This prevents CSRF-based forced logout attacks.

---

### File 35: `layout/footer.php`

- **Location**: `views/layout/footer.php`
- **Purpose**: Closes `</main>`, renders footer, loads JS files, and shows login success overlay.

**Special — Login success overlay**: Uses `sessionStorage` to detect fresh logins and shows a Lottie animation welcome overlay that auto-dismisses after 4 seconds.

---

## Group 9: View Templates — Pages (`views/pages/`)

---

### File 36: `pages/landing.php`
Full-screen hero landing page with parallax background image of Hawassa, CTA buttons to explore or sign in. Loads `landing.js` for parallax scrolling effect.

### File 37: `pages/home.php`
Role-selection gateway with three cards (Attendee, Organizer, Admin) linking to role-specific login pages. Uses Hawassa-themed SVG icons.

### File 38: `pages/login.php`
Login hub page that presents three role tiles. When a user clicks a role, they go to the role-specific login page. Preserves redirect parameter across the navigation.

### File 39–41: `pages/login_attendee.php`, `login_organizer.php`, `login_admin.php`
**All three are 6-line files** that set `$loginRole` and include the shared `login_role_body.php` partial:
```php
$loginRole = 'admin';
require __DIR__ . '/../partials/login_role_body.php';
```
This DRY approach means we maintain one login form template used by all three role-specific pages.

### File 42: `pages/register.php`
Registration form with floating labels, password toggle buttons, real-time validation attributes, role selection (attendee/organizer fieldset), old input restoration, CSRF token, and submit button disabling on click.

### File 43: `pages/event_feed.php`
Paginated grid of approved events. Requires login. Shows event cards with images, capacity bars, status badges (Open/Full/Ended), and smart pagination with ellipsis. Uses `event_get_approved_events()` and `rsvp_count_approved()` for each card.

### File 44: `pages/event_detail.php`
**Our largest view (18,090 bytes).** Single event display with:
- Event header (image, title, dates, location, category, capacity)
- RSVP section with conflict detection modal
- RSVP status display (pending/approved/rejected)
- Feedback form (star rating + comment, gated to approved attendees post-event)
- Feedback summary (average rating, count)
- Comment thread with reply forms for organiser

### File 45: `pages/dashboard.php`
**Our largest file (27,918 bytes).** Organiser dashboard with:
- "Create Event" form with all validation, old input restoration, image upload
- Event listing table with edit/delete actions
- Inline edit forms (expand/collapse)
- Pending RSVP management (approve/reject buttons)
- Feedback summaries per event

### File 46: `pages/admin_events.php`
Admin event moderation panel showing pending events with approve/reject buttons and all events listing. Uses `event_get_pending_events()` and `event_get_approved_events()`.

### File 47: `pages/admin_users.php`
Admin user management panel with:
- User stats (count by role)
- "Add User" form with validation
- User table with role change dropdown and delete button
- Self-protection: cannot change own role or delete own account

### File 48: `pages/404.php`
Simple not-found page with a search icon, message, and "Browse Events" link. Uses `ui_icon()` for the icon.

---

## Group 10: View Templates — Partials (`views/partials/`)

---

### File 49: `partials/flash_messages.php`
Renders session flash messages as an animated overlay with Lottie animations (success checkmark or error icon). Auto-dismisses after 4.5 seconds. Supports multiple messages as a list. Uses `get_flashes()` which clears messages after reading (one-time display).

### File 50: `partials/event_card.php`
Simple reusable event card component. Expects an `$event` array in scope. Displays title, verified badge, date, location, and a "View details" link.

### File 51: `partials/login_role_body.php`
Shared login form used by all three role-specific login pages. Renders the form with email, password, remember-me checkbox, CSRF token, hidden `login_role` field, password toggle, old input restoration, and themed role badge with icon. Includes client-side JS for `sessionStorage`-based login tracking.

### File 52: `partials/ui_icons.php`
SVG icon library implemented as a PHP function `ui_icon($name, $options)`. Contains 12+ inline SVG icons (calendar, map_pin, ticket, user_plus, search, star, etc.) that can be sized and styled. Returns raw SVG markup for embedding anywhere.

### File 53: `partials/hawassa_svg_icons.php`
Hawassa-themed decorative SVG icons stored in a `$HAWASSA_SVG` array. Three role-specific icons (attendee, organizer, admin) used on the login hub and home page.

---

## Group 11: CLI Scripts (`scripts/`)

---

### File 54: `scripts/setup_admin_user.php`
CLI script to create or update an admin account. Supports two modes:
- **Environment variable**: `$env:ADMIN_PASSWORD = "secret"; php scripts/setup_admin_user.php --email=x --name=y`
- **Interactive prompt**: `--prompt` flag reads password from STDIN

If the email already exists, it updates the user to admin role with the new password.

### File 55: `scripts/setup_organizer_user.php`
Same pattern as `setup_admin_user.php` but creates organizer accounts.

### File 56: `scripts/reset_admin_password.php`
CLI script to reset an admin's password. Finds user by email, verifies they exist, hashes new password, and updates.

### File 57: `scripts/import_channel_announcements.php`
**Our largest script (12,162 bytes).** Bulk imports events from Telegram channel announcement data. Parses structured text, extracts title/description/date/location, creates events in the database. Used for initial data population from the Hawassa Rotaract channel.

---

## Group 12: Root File

### File 58: `fix_seed.php`

- **Location**: Project root
- **Purpose**: One-time maintenance script to reset seed user passwords and create placeholder images.

Resets all user passwords to `password` (bcrypt hash), creates minimal 1×1 JPEG placeholders for missing event images. Uses a hardcoded base64-encoded minimal JPEG (107 bytes) to avoid 404s on `<img>` tags.

---

## Complete Recommended Reading Order (Both Parts)

Study the files in this sequence for the best understanding:

**Foundation (Part 1):**
1. `config/config.php` — Constants and security bootstrap
2. `config/db.php` — Database connection singleton
3. `includes/helpers.php` — Utility functions
4. `includes/session.php` — Session and auth infrastructure
5. `includes/validation.php` — Validation library
6. `includes/auth_guard.php` — Route protection
7. `includes/policy.php` — Resource-level authorization
8. `includes/rate_limit.php` — Rate limiting
9. `includes/upload.php` — File upload handling
10. `models/UserModel.php` → `EventModel.php` → `RsvpModel.php` → `CommentModel.php` → `FeedbackModel.php` → `RememberTokenModel.php`
11. `config/routes.php` — Route map

**Application (Part 2):**
12. `public/index.php` — Front controller
13. `views/layout/header.php` → `footer.php` — Layout wrapping
14. `views/partials/flash_messages.php` — Flash system
15. `views/pages/landing.php` → `home.php` — Entry pages
16. `views/pages/login.php` → `login_attendee.php` → `partials/login_role_body.php` — Login flow
17. `views/pages/register.php` — Registration flow
18. `actions/auth/register.php` → `login.php` → `logout.php` — Auth handlers
19. `views/pages/event_feed.php` → `event_detail.php` — Attendee pages
20. `actions/events/rsvp.php` → `comment.php` → `submit_feedback.php` — Attendee actions
21. `views/pages/dashboard.php` — Organiser page
22. `actions/events/create_event.php` → `update_event.php` → `delete_event.php` — Organiser actions
23. `actions/events/approve_rsvp.php` → `reject_rsvp.php` — RSVP management
24. `views/pages/admin_events.php` → `admin_users.php` — Admin pages
25. `actions/events/toggle_approval.php` — Event approval
26. `actions/admin/add_user.php` → `update_user_role.php` → `delete_user.php` — User management

---

> ← [Back to Part 1](PHP_FILES_DETAILED_EXPLANATION.md) · Part 2 (you are here)
>
> See also: [Architecture Documentation](PROJECT_ARCHITECTURE_FULL_DOCUMENTATION.md) · [Visual Diagrams](PROJECT_DIAGRAMS.html)
