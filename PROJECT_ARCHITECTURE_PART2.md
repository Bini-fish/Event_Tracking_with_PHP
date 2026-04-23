# City-Wide Event Tracking System — Architecture Documentation (Part 2)

> **📊 Diagrams not rendering?** Open [PROJECT_DIAGRAMS.html](PROJECT_DIAGRAMS.html) in your browser to see all 17 architecture diagrams fully rendered. VS Code's built-in preview does not support Mermaid — the diagrams render correctly on GitHub and in the HTML file.

> **Part 2 of 3** — Authentication System · User Journeys with Diagrams
>
> ← [Back to Part 1](PROJECT_ARCHITECTURE_FULL_DOCUMENTATION.md) (Overview, Folder Structure, Architecture, Database)
> → [Continue to Part 3](PROJECT_ARCHITECTURE_PART3.md) (Core Logic, Security, Setup)

---

## 5. Authentication System (Technical Details)

### 5.1 Full Authentication Flow Overview

```mermaid
graph LR
    subgraph Registration
        R1["User fills form<br/>(name, email, password, role)"] --> R2["POST actions/auth/register.php"]
        R2 --> R3["CSRF check"]
        R3 --> R4["Validate all fields"]
        R4 --> R5["Check email uniqueness"]
        R5 --> R6["password_hash(PASSWORD_DEFAULT)"]
        R6 --> R7["INSERT INTO users"]
        R7 --> R8["Auto-login via login_user()"]
        R8 --> R9["Redirect to dashboard/feed"]
    end
```

```mermaid
graph LR
    subgraph Login
        L1["User submits email + password"] --> L2["POST actions/auth/login.php"]
        L2 --> L3["CSRF check"]
        L3 --> L4["Validate inputs"]
        L4 --> L5["Throttle check<br/>(5 attempts / 15 min)"]
        L5 --> L6["user_find_by_email()"]
        L6 --> L7["password_verify()"]
        L7 --> L8["Role validation<br/>(if role-specific page)"]
        L8 --> L9["login_user() → session_regenerate_id()"]
        L9 --> L10["Optional: issue remember-me token"]
        L10 --> L11["Redirect by role"]
    end
```

### 5.2 Registration Flow — Step by Step

**Entry**: `POST` to `public/actions/auth/register.php`

1. **Method guard** — Only POST is accepted; GET requests redirect to the form.
2. **CSRF verification** — `verify_csrf_token($_POST['csrf_token'])` compares the submitted token against `$_SESSION['csrf_token']` using `hash_equals()` (timing-safe).
3. **Input extraction** — `request_string()` trims whitespace; email is lowercased.
4. **Validation pipeline** — Runs sequentially; first failure stops and redirects:
   - `validate_required($name)` — Name must not be empty
   - `validate_email($email)` — Must pass `filter_var(FILTER_VALIDATE_EMAIL)`
   - `validate_password_strength($password, 8, true)` — Min 8 chars, at least 1 letter + 1 digit
   - `validate_password_confirmation($password, $confirm)` — Must match (using `hash_equals`)
5. **Role restriction** — Only `'attendee'` or `'organizer'` accepted from the form; admins can only be created via CLI scripts or the admin panel.
6. **Duplicate check** — `user_find_by_email()` queries the database before inserting.
7. **Password hashing** — `password_hash($password, PASSWORD_DEFAULT)` — PHP selects the best available algorithm (bcrypt currently, auto-upgrades in future PHP versions).
8. **Database insert** — `user_create()` inserts with named parameters.
9. **Auto-login** — `login_user()` is called immediately, so the user doesn't need to log in again.
10. **Old input preservation** — On any failure, `set_old_input()` stores name, email, and role (never the password) for form re-population after redirect.

### 5.3 Login Flow — Step by Step

**Entry**: `POST` to `public/actions/auth/login.php`

1. **CSRF check** + **input validation** (email required, password required, valid email format).
2. **Login throttling** (session-based):
   - Key = `SHA-256(lowercase_email | IP_address)` — binds attempts to both email and IP.
   - After **5 failed attempts** within 15 minutes → account is **locked for 15 minutes**.
   - `login_is_locked()` checks `$_SESSION['login_attempts'][$key]['lock_until']`.
   - On success, `clear_login_failures()` resets the counter.
3. **Credential verification**:
   - `user_find_by_email()` fetches the user row.
   - `password_verify($plainPassword, $user['password_hash'])` checks the bcrypt hash.
   - On failure: generic "Invalid credentials" message (never reveals if email exists).
4. **Role-specific login enforcement**:
   - If the form includes a `login_role` field (from role-specific login pages), the user's actual role must match.
   - Mismatch → "You are not authorized to access this panel."
5. **Session creation**:
   - `login_user($user)` calls `session_regenerate_id(true)` to **prevent session fixation**.
   - Stores `user_id` and `user_role` in `$_SESSION`.
6. **Password rehash check** — If `password_needs_rehash()` returns true, we transparently upgrade the hash to the latest algorithm.
7. **Remember-me** (optional):
   - If checkbox is checked, `issue_remember_me_token()` generates a split token.
   - See §5.5 below for full details.
8. **Safe redirect**:
   - Supports `$_POST['redirect']` for returning to the originally requested page.
   - **Open redirect prevention**: Only allows relative paths starting with `/` and not `//`.
   - Default redirects: admin → `admin_events`, organizer → `dashboard`, attendee → `event_feed`.

### 5.4 Logout Flow

**Entry**: `POST` to `public/actions/auth/logout.php`

```mermaid
sequenceDiagram
    participant B as Browser
    participant A as logout.php
    participant S as Session
    participant DB as MySQL

    B->>A: POST with CSRF token
    A->>A: Verify CSRF token
    A->>DB: Delete remember-me token (by selector from cookie)
    A->>S: Clear remember-me cookie (set expired)
    A->>S: $_SESSION = [] (wipe all data)
    A->>S: Delete session cookie (set expired)
    A->>S: session_destroy()
    A->>B: 302 Redirect → event_feed
```

### 5.5 Remember-Me System (Split Token Pattern)

Our remember-me implementation follows the **split-token pattern** recommended by security experts (Paragon Initiative):

```mermaid
sequenceDiagram
    participant B as Browser
    participant S as Server
    participant DB as MySQL

    Note over B,DB: === Token Issuance ===
    S->>S: selector = random_bytes(8) → hex (16 chars)
    S->>S: validator = random_bytes(32) → hex (64 chars)
    S->>S: validatorHash = sha256(validator)
    S->>DB: INSERT remember_tokens(user_id, selector, validatorHash, expires_at)
    S->>B: Set-Cookie: remember_me=selector:validator (30 days, httponly, secure, samesite=strict)

    Note over B,DB: === Auto-Login on Next Visit ===
    B->>S: Request with remember_me cookie
    S->>S: Split cookie → selector + validator
    S->>DB: SELECT * FROM remember_tokens WHERE selector = ?
    DB-->>S: Token row (validator_hash, user_id, expires_at)
    S->>S: hash_equals(sha256(validator), stored_hash)?
    S->>S: Check expiry
    S->>DB: DELETE old token (by selector)
    S->>S: Issue NEW token (rotation!)
    S->>S: login_user() with fetched user
    S->>B: Set-Cookie with new token
```

**Why split tokens?**
- The **selector** is a public lookup key (indexed, fast).
- The **validator** is the secret, stored only as a SHA-256 hash in the database.
- Even if the database is compromised, attackers cannot forge cookies — they'd need the unhashed validator.
- **Token rotation** on every use prevents replay attacks and limits the window of a stolen cookie.

### 5.6 Session Security Measures

| Measure | Implementation | Where |
|---|---|---|
| **Session fixation prevention** | `session_regenerate_id(true)` on every login and role change | `session.php` lines 36, 52 |
| **Secure cookie flags** | `httponly=true`, `samesite=Strict`, `secure` when HTTPS | `session.php` line 16–23 |
| **CSRF tokens** | 64-char hex token generated per session, verified on every POST | `session.php` lines 304–323 |
| **Timing-safe comparison** | `hash_equals()` for CSRF and password confirmation | `session.php` line 322, `validation.php` line 132 |
| **Session data wipe on logout** | `$_SESSION = []` + cookie destruction + `session_destroy()` | `session.php` lines 262–274 |
| **Login throttling** | 5 attempts per 15 minutes per email+IP pair | `login.php` lines 11–76 |
| **Role integrity** | Session role always validated against whitelist | `session.php` line 47 |

### 5.7 User Roles & Permissions Matrix

| Action | Attendee | Organizer | Admin |
|---|:---:|:---:|:---:|
| Browse approved events | ✅ | ✅ | ✅ |
| View event detail | ✅ | ✅ | ✅ |
| RSVP to event | ✅ | ✅ | ✅ |
| Submit feedback (post-event) | ✅ | ✅ | ✅ |
| Post comment (post-event) | ✅ | ✅ | ✅ |
| Create event | ❌ | ✅ | ✅ (via view switch) |
| Edit own events | ❌ | ✅ | ✅ |
| Delete own events | ❌ | ✅ | ✅ |
| Approve/reject RSVPs | ❌ | ✅ (own events) | ✅ (all events) |
| Reply to comments | ❌ | ✅ (own events) | ✅ |
| Approve/reject events | ❌ | ❌ | ✅ |
| Manage users (CRUD) | ❌ | ❌ | ✅ |
| Switch role view | ❌ | ❌ | ✅ |
| Create admin accounts | ❌ | ❌ | ✅ |

**Admin View Switching**: Admins can switch their effective role view to `organizer` or `attendee` to see the application exactly as those users would, while remaining authenticated as admin. This is stored in `$_SESSION['admin_view_role']` and affects navigation, dashboard access, and which features are visible — without changing the admin's actual permissions for admin-only operations.

---

## 6. User Journeys (with Graphic Logic)

### 6.1 Journey: Visitor Discovers and Registers

```mermaid
flowchart TD
    A["🌐 Visitor arrives at landing page"] --> B{"Already registered?"}
    B -->|No| C["Clicks 'Register'"]
    B -->|Yes| D["Clicks 'Login'"]
    C --> E["Fills registration form<br/>(name, email, password, role)"]
    E --> F{"Client-side validation passes?"}
    F -->|No| G["Shows inline errors<br/>(HTML5 validation + JS)"]
    G --> E
    F -->|Yes| H["POST → register.php"]
    H --> I{"Server validation passes?"}
    I -->|No| J["Flash error + old input preserved<br/>Redirect back to form"]
    J --> E
    I -->|Yes| K{"Email already exists?"}
    K -->|Yes| L["Flash: 'Account exists'<br/>Redirect to register"]
    L --> E
    K -->|No| M["password_hash() → INSERT user"]
    M --> N["Auto-login via login_user()"]
    N --> O{"Selected role?"}
    O -->|Attendee| P["Redirect → Event Feed 🎉"]
    O -->|Organizer| Q["Redirect → Dashboard 🎉"]

    style A fill:#e8f5e9
    style P fill:#e3f2fd
    style Q fill:#fff3e0
```

### 6.2 Journey: Attendee RSVPs to an Event

```mermaid
flowchart TD
    A["👤 Attendee browses Event Feed"] --> B["Clicks event card"]
    B --> C["Views Event Detail page"]
    C --> D{"Event approved<br/>(is_verified=1)?"}
    D -->|No| E["RSVP button hidden<br/>'Pending approval' badge shown"]
    D -->|Yes| F{"Already RSVP'd?"}
    F -->|Yes| G["Shows current RSVP status<br/>(pending/approved/rejected)"]
    F -->|No| H{"Seats remaining?"}
    H -->|No| I["'Event Full' badge shown"]
    H -->|Yes| J["Clicks 'RSVP' button"]
    J --> K["POST → rsvp.php"]
    K --> L{"CSRF + auth valid?"}
    L -->|No| M["Flash error → redirect"]
    L -->|Yes| N{"Rate limit OK?"}
    N -->|No| O["Flash: 'Too many attempts'"]
    N -->|Yes| P{"Time conflict with<br/>other RSVPs?"}
    P -->|Yes, not confirmed| Q["Redirect with conflict modal<br/>Shows overlapping events"]
    Q --> R{"User confirms<br/>despite conflict?"}
    R -->|No| S["Cancel — no RSVP"]
    R -->|Yes| T["Re-POST with conflict_confirmed=1"]
    P -->|No conflict| U["INSERT RSVP (status='pending')"]
    T --> U
    U --> V["Flash: 'RSVP submitted!<br/>Pending organizer approval'"]

    style A fill:#e8f5e9
    style V fill:#e3f2fd
```

### 6.3 Journey: Organiser Creates and Manages an Event

```mermaid
flowchart TD
    A["🎯 Organiser logs in → Dashboard"] --> B["Sees 'Create Event' form"]
    B --> C["Fills: title, description, location,<br/>category, dates, capacity, image"]
    C --> D["POST → create_event.php"]
    D --> E{"All validations pass?<br/>(required, length, dates, duration)"}
    E -->|No| F["ALL errors collected at once<br/>Flash each + preserve form data<br/>Redirect back"]
    F --> C
    E -->|Yes| G{"Start < End?<br/>Duration ≥ 30 min?"}
    G -->|No| H["Flash cross-field error"]
    H --> C
    G -->|Yes| I{"Overlaps organiser's<br/>other events?"}
    I -->|Yes| J["Warning flash (non-blocking)<br/>Event created anyway"]
    I -->|No| K["handle_image_upload()"]
    J --> K
    K --> L["INSERT event (is_verified=0)"]
    L --> M["Flash: 'Event submitted,<br/>pending admin approval'"]

    M --> N["Event appears on Dashboard<br/>with 'Pending' badge"]
    N --> O["Admin approves event"]
    O --> P["Event visible in public feed ✅"]

    P --> Q["Attendees start RSVPing"]
    Q --> R["Organiser sees pending<br/>RSVPs on Dashboard"]
    R --> S{"Approve or Reject?"}
    S -->|Approve| T["rsvp_approve() with<br/>row lock + capacity check"]
    S -->|Reject| U["rsvp_reject()"]
    T --> V["RSVP status = 'approved'"]
    U --> W["RSVP status = 'rejected'"]

    style A fill:#fff3e0
    style P fill:#e8f5e9
```

### 6.4 Journey: Admin Manages the Platform

```mermaid
flowchart TD
    A["🔑 Admin logs in → Admin Events page"] --> B{"What to do?"}

    B -->|Approve Events| C["Views pending events list"]
    C --> D["Clicks 'Approve' on an event"]
    D --> E["POST → toggle_approval.php"]
    E --> F["event_set_verified(id, true)"]
    F --> G["Event now public ✅"]

    B -->|Manage Users| H["Navigates to User Management"]
    H --> I["Views all users with stats<br/>(event count, RSVP count)"]
    I --> J{"Action?"}
    J -->|Add User| K["Fills add-user form<br/>(name, email, role, password)"]
    K --> L["POST → add_user.php<br/>Full validation + duplicate check"]
    J -->|Change Role| M["Select new role from dropdown"]
    M --> N["POST → update_user_role.php<br/>Cannot change own role"]
    J -->|Delete User| O["Clicks delete with confirmation"]
    O --> P["POST → delete_user.php<br/>Cascade: feedback, comments,<br/>RSVPs, events, then user"]

    B -->|Switch View| Q["Switches to Organizer view"]
    Q --> R["Sees organizer dashboard<br/>Can create events as admin"]
    R --> S["Switch to Attendee view"]
    S --> T["Sees event feed<br/>as attendee would"]
    T --> U["Switch back to Admin"]

    style A fill:#fce4ec
    style G fill:#e8f5e9
```

### 6.5 Journey: Post-Event Feedback

```mermaid
flowchart TD
    A["📅 Event has ended<br/>(event_end datetime passed)"] --> B["Approved attendee visits<br/>Event Detail page"]
    B --> C{"Already submitted<br/>feedback?"}
    C -->|Yes| D["Shows 'Already submitted' message<br/>Displays all feedback below"]
    C -->|No| E["Feedback form appears<br/>(star rating 1-5 + optional comment)"]
    E --> F["Submits feedback"]
    F --> G["POST → submit_feedback.php"]
    G --> H{"Checks pass?"}
    H -->|"Event not ended"| I["Flash: 'Wait until event ends'"]
    H -->|"Not approved attendee"| J["Flash: 'Only confirmed attendees'"]
    H -->|"Already submitted"| K["Flash: 'Already submitted'"]
    H -->|"Rating invalid"| L["Flash: 'Select 1-5'"]
    H -->|All pass ✅| M["INSERT INTO feedback"]
    M --> N["Flash: 'Thank you!'"]
    N --> O["Feedback summary updates<br/>(avg rating + count)"]

    style A fill:#f3e5f5
    style O fill:#e8f5e9
```

### 6.6 Journey: Comment & Reply System

```mermaid
flowchart TD
    A["User on Event Detail page"] --> B{"Event has ended?"}
    B -->|"No + user is regular attendee"| C["Comment form disabled<br/>'Available after event ends'"]
    B -->|"No + user is organiser/admin"| D["Can post comments<br/>(pre-event announcements)"]
    B -->|Yes| E["Comment form visible"]

    D --> F["Writes comment"]
    E --> F
    F --> G["POST → comment.php"]
    G --> H{"Validation passes?"}
    H -->|No| I["Flash error → redirect"]
    H -->|Yes| J{"Is it a reply?<br/>(parent_comment_id set?)"}
    J -->|"No — top-level comment"| K["comment_add(eventId, userId, body)"]
    J -->|"Yes — reply"| L{"User is organiser/admin<br/>of this event?"}
    L -->|No| M["Flash: 'Only organiser can reply'"]
    L -->|Yes| N["Verify parent comment exists<br/>and belongs to same event"]
    N --> O["comment_add_reply(eventId, userId, parentId, body)"]

    K --> P["Comment appears in thread ✅"]
    O --> P

    style P fill:#e8f5e9
```

### 6.7 Event Editing & Re-Approval Flow

```mermaid
flowchart TD
    A["Organiser edits an approved event"] --> B["POST → update_event.php"]
    B --> C{"Full validation passes?"}
    C -->|No| D["All errors collected + old input preserved"]
    C -->|Yes| E["Row locked with SELECT ... FOR UPDATE"]
    E --> F{"Can edit?<br/>(owner or admin)"}
    F -->|No| G["403 Forbidden"]
    F -->|Yes| H{"New image uploaded?"}
    H -->|Yes| I["Upload new → delete old file"]
    H -->|No| J["Keep existing image_path"]
    I --> K{"Editor is admin?"}
    J --> K
    K -->|Yes| L["Event stays approved<br/>(admin edits are trusted)"]
    K -->|No| M["is_verified reset to 0<br/>Event needs re-approval!"]
    L --> N["UPDATE events SET ..., edited_at=NOW(), edited_by=actorId"]
    M --> N
    N --> O["Flash: success message"]

    style M fill:#fff3e0
    style L fill:#e8f5e9
```

**Key design decision**: When an organiser edits an already-approved event, the event is automatically sent back for admin re-approval. This prevents organizers from getting approval for one event and then changing it to something different. Admin edits bypass this restriction since admins are trusted.

---

> **Continue to [Part 3 →](PROJECT_ARCHITECTURE_PART3.md)** for Core Logic breakdown, Security analysis, Performance considerations, and Setup instructions.
