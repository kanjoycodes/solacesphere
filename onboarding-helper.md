# Solace Sphere — PHP Project Defense Document

---

## 1. ARCHITECTURAL OVERVIEW

### 1.1 Project Summary

**Solace Sphere** is a blended mental health platform with three portals: patient, professional (clinician), and admin. Patients access self-help tools (mood tracking, journaling, guided breathing, affirmations, resources, community, AI chatbot). Professionals monitor patients and onboard new ones. Admins verify professional registrations and manage all users.

### 1.2 Technology Stack

| Layer | Technology |
|---|---|
| Language | PHP 7.4+ (procedural) |
| Database | MySQL 5.7+ / MariaDB via PDO |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| AI | Google Gemini API (with local fallback) |
| Server | PHP built-in server or Apache |

### 1.3 Directory Structure

```
solacesphere/
├── landing page.html         # Entry point — public home page
├── index.html                # Duplicate landing page
├── styles.css                # Global stylesheet
├── auth.js                   # Client-side auth guard + role routing
├── .env                      # Environment variables (DB creds, API key)
├── onboarding.md             # Step-by-step user guide
├── onboarding-helper.md      # This document — code explanations
├── create_admin.php          # CLI script to seed an admin user
├── auth/
│   ├── login.html            # Login form with role dropdown
│   ├── signup.html           # Registration form with password strength
│   ├── forgot-password.html  # Password reset request form
│   ├── reset-password.html   # Token-based password reset
│   ├── send-reset-email.php  # Backend: sends password reset email
│   └── verify-reset-token.php# Backend: validates reset tokens
├── patient/
│   ├── dashboard.html        # Main patient landing after login
│   ├── mood.html             # Mood tracking tool
│   ├── journal.html          # Journal writing tool
│   ├── breathing.html        # Guided breathing exercise
│   ├── affirmations.html     # Positive affirmations
│   ├── resources.html        # Wellness resource library
│   ├── community.html        # Anonymous community posts
│   ├── chatbot.html          # AI chatbot (Gemini or local fallback)
│   ├── personal toolbox.html # Hub linking all self-help tools
│   ├── personal-progress.html# Charts for mood/journal/sleep trends
│   ├── profile.html          # User profile with chart exports
│   ├── edit-profile.html     # Edit profile form
│   ├── appointment.html      # Appointment scheduling (mock)
│   └── appointment.js        # Appointment mock data
├── professional/
│   ├── clinician-dashboard.html# Main clinician portal
│   ├── schedule-manager.html   # Weekly calendar (mock)
│   ├── patient-detail.html     # Patient detail view (mock)
│   └── clinical-notes.html     # Clinical notes (mock)
├── admin/
│   └── admin-dashboard.html    # Admin user management + verification
├── api/
│   ├── config.php            # Env loader + shared helpers (readJsonBody, sendJson)
│   ├── bootstrap.php         # Chatbot safety logic + Gemini integration
│   ├── db.php                # Database connection (PDO)
│   ├── login.php             # Login endpoint
│   ├── signup.php            # Registration endpoint
│   ├── setup.php             # Database schema importer
│   ├── migrate.php           # Legacy migration (creates users table)
│   ├── migrate-status.php    # Adds status column to users
│   ├── me.php                # Returns current logged-in user from session
│   ├── chat.php              # Chatbot API (Gemini + abuse detection)
│   ├── prompts.php           # Suggested prompts for chatbot
│   ├── admin/
│   │   ├── users.php         # GET list/search users, POST update role/status
│   │   └── verify.php        # POST approve/reject pending professionals
│   └── professional/
│       └── create-patient.php# POST create patient account
├── database/
│   ├── schema.sql            # Full database schema (21 tables)
│   └── seed.php              # Seeds admin + demo users
├── images/                   # All image assets
└── server/logs/              # Chatbot abuse logs
```

### 1.4 Data Flow Architecture

```
Browser Request (HTML page)
    ↓
HTML page loads → auth.js executes on DOMContentLoaded
    ↓
auth.js checks localStorage for "solaceCurrentUser"
    ├── No user → redirect to /auth/login.html (with ?next=currentPage)
    └── User found → checks role against PAGE_RULES
        ├── Wrong role → redirect to role's home page
        └── Correct role → show page, hide unauthorized nav links

API Request (form submission or fetch)
    ↓
PHP endpoint (e.g., api/login.php)
    ↓
config.php: loads .env into $_ENV
    ↓
db.php: PDO connection using credentials from $_ENV
    ↓
Business logic (validate input, query DB, hash passwords, etc.)
    ↓
sendJson() outputs JSON response → browser handles it
```

**Key architectural decisions:**

1. **Dual auth system**: The app has both server-side (PHP sessions + database) AND client-side (localStorage + auth.js) authentication. The server endpoints use PHP sessions (`session_start()`). The client-side auth guard (`auth.js`) reads from `localStorage` to decide whether to show/hide pages and nav links. This means the app works even when the PHP server is offline — localStorage provides a demo/fallback mode.

2. **config.php as shared bootstrap**: All PHP API files include `config.php` (or `db.php` which includes `config.php`). `config.php` calls `loadEnvFile()` to populate `$_ENV` from `.env`, and provides two critical helper functions: `readJsonBody()` (reads JSON from `php://input`) and `sendJson()` (outputs JSON with proper headers).

3. **Role-based routing in auth.js**: The `PAGE_RULES` array maps page filenames to allowed roles. `NAV_RULES` maps nav link hrefs to roles that can see them. `ROLE_HOME` maps each role to their default landing page. The `getCurrentFile()` function extracts just the filename from the URL (strips directory path) so that PAGE_RULES matches regardless of which directory the page lives in.

4. **Professional pending approval workflow**: When a professional signs up, `status` is set to `pending`. The login endpoint checks `status` and rejects pending professionals. An admin must approve via `api/admin/verify.php`. This follows the real-world credential verification process.

5. **Clinician-initiated patient onboarding**: Instead of patients self-registering (which is the normal flow), clinicians can create patient accounts via `api/professional/create-patient.php`. This models the blended care requirement where a patient must have an initial consultation before being given access.

6. **localStorage fallback**: All patient self-help tools (mood, journal, etc.) save data to `localStorage`, not the database. This keeps the frontend simple and functional even without backend integration. The PHP backend is used primarily for authentication, user management, and professional/admin features.

---

## 2. DATABASE SCHEMA (21 Tables)

| Table | Purpose |
|---|---|
| `users` | All users — patients, professionals, admins. Central identity table with `role` and `status` ENUMs. |
| `password_reset_tokens` | Stores one-time tokens for password resets with expiry. |
| `login_sessions` | Records every login with session token, IP, user agent, expiry. |
| `appointments` | Scheduled appointments between patients and professionals. |
| `journal_entries` | Patient journal entries with mood scores per date. |
| `mood_entries` | Patient mood logs with label and score. |
| `clinical_notes` | Professional notes about patients. |
| `community_posts` | Anonymous community posts. |
| `community_post_likes` | Likes on community posts. |
| `resource_bookmarks` | User bookmarks for wellness resources. |
| `affirmation_favorites` | User favorited affirmations. |
| `chat_threads` | AI chatbot conversation threads. |
| `chat_messages` | Individual messages within chat threads. |
| `progress_logs` | Daily progress summaries. |
| `invite_tokens` | One-time tokens for professional/admin signups. |
| `habits` | User-defined habit trackers. |
| `habit_completions` | Daily habit completion records. |
| `activity_logs` | Normalized timeline of user actions. |
| `notifications` | Clinician/patient alerts. |
| `user_settings` | Per-user key/value preferences. |
| `reports` | Metadata for generated reports/exports. |

**Key relationships:**
- All user-owned tables (journal_entries, mood_entries, etc.) have `user_id` FK → `users.id`
- `appointments.patient_user_id` + `appointments.professional_user_id` → `users.id`
- `clinical_notes.patient_user_id` + `clinical_notes.professional_user_id` → `users.id`
- `invite_tokens` enables one-time signup links for professional/admin role assignment

---

## 3. FILE BY FILE BREAKDOWN

### 3.1 Root Files

| File | What It Does |
|---|---|
| `landing page.html` | Public home page. Has nav links, hero section, cards linking to patient tools, and a floating chatbot button. No auth guard — anyone can see it. |
| `index.html` | Identical to landing page. Serves as fallback for servers that default to index.html. |
| `styles.css` | Global stylesheet. Controls layout, colors, fonts, responsive breakpoints, form styles, card styles, nav bar, badges, pills, alerts, tables, admin grid, and all component-level styling. |
| `auth.js` | Client-side auth guard. Runs on every page load. Checks localStorage for logged-in user, validates role against PAGE_RULES, hides unauthorized nav links, redirects to login if not authenticated, replaces profile link with a dropdown showing user's name and logout. |
| `.env` | Environment configuration. Holds DB credentials and Gemini API key. Loaded by `api/config.php`. |
| `create_admin.php` | CLI script. Run `php create_admin.php email password` to seed an admin user directly in the database. |

### 3.2 Auth Pages (auth/)

| File | What It Does |
|---|---|
| `login.html` | Login form with email, password, and role dropdown (patient/professional/admin). Has inline JavaScript that sends POST to `api/login.php`. Falls back to localStorage if server is offline. After login, redirects to role-specific home page. |
| `signup.html` | Registration form with name, email, password, confirm password. Has password strength meter with real-time validation (capital letter, number, special character checklist). Sends POST to `api/signup.php`. Creates patient accounts by default. |
| `forgot-password.html` | Email input form. Sends request to `send-reset-email.php` which generates a token, stores it in `reset_tokens.json`, and (in production) sends an email with a reset link. |
| `reset-password.html` | Reads `token` and `email` from URL query parameters. Verifies token via `verify-reset-token.php`. If valid, shows a new password form. Updates password in localStorage. |
| `send-reset-email.php` | Backend for forgot-password. Generates a cryptographically secure random token via `bin2hex(random_bytes(32))`, stores it with 1-hour expiry in `reset_tokens.json`, and attempts to send an email via PHP's `mail()` function. |
| `verify-reset-token.php` | Backend for reset-password. Reads token and email from GET params, looks up in `reset_tokens.json`, checks expiry. Returns JSON `{valid: true/false}`. |

### 3.3 Patient Pages (patient/)

| File | What It Does |
|---|---|
| `dashboard.html` | Main patient landing page. Shows welcome message, mood check-in card, quick tool pills (breathing, mood, journal, affirmations), recommended resources, and floating chatbot button. All patient tools are linked within the same directory. |
| `mood.html` | Mood tracking interface. Users select an emotional state (e.g., happy, anxious, calm, sad) and optionally add a note. Data saved to localStorage. |
| `journal.html` | Journal with rich text input. Users write entries and save them. Supports entry date tracking. Data saved to localStorage. |
| `breathing.html` | Guided breathing exercise with animated circle that expands (inhale) and contracts (exhale). Has configurable duration and pattern. |
| `affirmations.html` | Displays positive affirmations. Has a "New Affirmation" button to cycle through a preset list. Favorites can be saved to localStorage. |
| `resources.html` | Wellness resource library. Articles organized by category (Anxiety, Depression, Stress, Mindfulness, Sleep). Uses JavaScript objects for content. Supports bookmarking to localStorage. |
| `community.html` | Anonymous community board. Users can read posts, write their own, and like posts. Data saved to localStorage. |
| `chatbot.html` | AI chatbot interface. Sends messages to `api/chat.php`. If Gemini API is configured, uses Gemini model for responses. Otherwise falls back to local canned responses (defined in `bootstrap.php`). Has abuse detection and safety filtering. |
| `personal toolbox.html` | Hub page linking to all self-help tools (mood, journal, breathing, affirmations) with icon cards. |
| `personal-progress.html` | Charts and statistics page. Uses Chart.js library. Shows mood trends, journal streaks, sleep patterns. Reads from localStorage. Has PDF export via html2pdf.js. |
| `profile.html` | User profile page. Shows name, email, wellness focus, bio. Has chart.js mood visualization and PDF download. Also used by professionals to view patient profiles (via `?patient=ID` query param). |
| `edit-profile.html` | Form to edit profile fields (name, bio, wellness focus). Saves to localStorage. |
| `appointment.html` | Mock appointment booking page. Shows a list of clinicians with their availability. Uses `appointment.js` for mock data. |
| `appointment.js` | Mock data for appointment page. Contains clinician profiles (name, specialty, photo path, available slots). Data is loaded into the appointment.html DOM on page load. |

### 3.4 Professional Pages (professional/)

| File | What It Does |
|---|---|
| `clinician-dashboard.html` | Main clinician portal. Has three sections: (1) Onboard New Patient form that POSTs to `api/professional/create-patient.php`, (2) High-Priority Alerts showing patients with low mood or high chat activity (mock data), (3) Patient Status table with mood/activity/risk columns (mock data). Also has a Chart.js donut chart for mood distribution. Has a client-side session guard that checks `api/me.php` before showing content. |
| `schedule-manager.html` | Weekly calendar view (mock data). Shows appointments in time slots. |
| `patient-detail.html` | Patient detail view (mock data). Shows patient info, mood history, clinical notes. Used by clinicians to review individual patients. |
| `clinical-notes.html` | Clinical notes interface (mock data). Has a notes list and a form to add new notes. |

### 3.5 Admin Pages (admin/)

| File | What It Does |
|---|---|
| `admin-dashboard.html` | Admin control center. Has four sections: (1) User Management — search field + user directory fetched from `api/admin/users.php`. Supports updating roles and deactivating users via POST. (2) Professional Verification — lists pending professionals fetched from `api/admin/users.php`, with Approve/Reject buttons that POST to `api/admin/verify.php`. (3) Resource Library CMS — local mock form for adding resources. (4) System Health Monitor — static status info. All user/verification data is live from the database, not mocked. |

### 3.6 API Backend (api/)

| File | What It Does |
|---|---|
| `config.php` | Loads `.env` file into `$_ENV` and `putenv()`. Provides `readJsonBody()` (reads and decodes JSON request body) and `sendJson()` (outputs JSON response with proper headers and status code). Every other PHP file includes this. |
| `bootstrap.php` | Includes `config.php`. Defines chatbot safety system: `classifyInput()` detects crisis/harm/injection patterns using keyword matching. `queryGeminiChat()` calls the Google Gemini API with curl. `localFallbackReply()` provides canned responses when API is unavailable. `derivePrompts()` generates contextual follow-up questions based on user signals. The abuse detection system uses a scoring mechanism with decay over time. |
| `db.php` | Database connection using PDO. Reads credentials from `$_ENV` (loaded by config.php). Creates a PDO instance with `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, and `EMULATE_PREPARES = false` for SQL injection prevention. |
| `login.php` | Accepts POST with JSON body `{email, password, role}`. Looks up user by email, verifies password with `password_verify()`, checks if professional is not pending, creates PHP session, records login_session row, returns user data. |
| `signup.php` | Accepts POST with JSON body `{name, email, password, role}`. Checks for duplicate email, validates invite token if provided, hashes password with `password_hash()`, sets `status=pending` for professionals, inserts user, returns user data. |
| `me.php` | Reads `$_SESSION['user_id']`, queries user from database, returns user data. Used by clinician-dashboard.html to verify server-side session before allowing access. |
| `setup.php` | CLI/HTTP script that reads `database/schema.sql`, splits it by semicolons, and executes each statement. Creates all tables. |
| `migrate-status.php` | Adds the `status` column to the `users` table if it does not exist. Sets existing professionals to `active`. Run after schema import on databases created before the status feature. |
| `chat.php` | Chatbot endpoint. Reads message from POST, classifies input for safety (crisis/harm/injection), calls `queryGeminiChat()` or falls back to `localFallbackReply()`, applies `safeOutputFilter()`, returns response as JSON. Includes abuse state tracking with IP-based scoring. |
| `admin/users.php` | GET: lists all users with optional `search` and `role` query params. POST: updates a user's `role` and/or `status` by `user_id`. Used by the admin dashboard. |
| `admin/verify.php` | POST endpoint. Accepts `{user_id, action}` where action is `approve` or `reject`. Approve sets status to `active`. Reject sets status to `deactivated`. Only works for professional-role users. |
| `professional/create-patient.php` | POST endpoint. Accepts `{name, email, password}`. Creates a patient account with `role=patient`, `status=active`. Returns success with the new user's data. Used by the clinician dashboard's Onboard New Patient form. |

---

## 4. CORE PHP CONSTRUCTS AND PATTERNS

### 4.1 `declare(strict_types=1)` — Strict Typing

**What it is**: A PHP declaration at the top of every API file that enforces strict type checking for function parameters and return values. Without it, PHP would silently convert (cast) incompatible types — e.g., passing a string `"5"` where an `int` is expected would work. With it, PHP throws a `TypeError`.

**Why it is used**: Prevents subtle type coercion bugs. If `sendJson()` expects `int $statusCode` but receives a string like `"200"`, strict types would throw an error instead of silently accepting it. In the SolaceSphere codebase, this ensures that `readJsonBody()` returns `array` (not `null` or `false`), and that database IDs and status codes are always the correct type.

**Where it is used**:
- All PHP files in `api/`: `config.php:2`, `bootstrap.php:2`, `db.php:2`, `login.php:2`, `signup.php:2`, `setup.php:2`, `chat.php:2`, `me.php:2`, `admin/users.php:2`, `admin/verify.php:2`, `professional/create-patient.php:2`

### 4.2 `loadEnvFile()` — Custom Environment Loader

**What it is**: A function defined in `config.php:5-27` that reads a `.env` file line by line, splits each line at the first `=`, and stores the key-value pair into `$_ENV` and via `putenv()`. Skips empty lines and comments (lines starting with `#`).

**Why it is used**: PHP has no built-in `.env` file loader. This function provides one without requiring external libraries (like `vlucas/phpdotenv`). Using environment variables keeps database credentials and API keys out of source code — the `.env` file is listed in `.gitignore` and never committed. The `if (!array_key_exists($key, $_ENV))` guard prevents overwriting already-set environment variables, which allows server-level env vars to take precedence.

**Where it is used**:
- Defined in: `api/config.php:5-27`
- Called at: `api/config.php:50` (`loadEnvFile(__DIR__ . '/../.env')`)
- Consumed by: `api/db.php:8-12` (reads DB creds from `$_ENV`), `api/bootstrap.php:177` (reads Gemini API key from `$_ENV`), `api/setup.php:8-11` (reads DB creds from `$_ENV`)

### 4.3 `readJsonBody()` — JSON Request Body Reader

**What it is**: A function defined in `config.php:29-37` that reads the raw HTTP request body via `file_get_contents('php://input')`, decodes it as JSON with `json_decode($raw, true)`, and returns the resulting array. Returns an empty array if the body is empty or not valid JSON.

**Why it is used**: The frontend sends POST data as JSON (`Content-Type: application/json`), not as URL-encoded form data. Standard `$_POST` would be empty for JSON payloads. `php://input` is a read-only stream that gives access to the raw request body regardless of content type. The `trim($raw) === ''` check handles empty bodies gracefully.

**Where it is used**:
- Defined in: `api/config.php:29-37`
- Called at: `api/login.php:8` (`$data = readJsonBody()`), `api/signup.php:7`, `api/chat.php:16`, `api/admin/users.php:31`, `api/admin/verify.php:12`, `api/professional/create-patient.php:12`

### 4.4 `sendJson()` — JSON Response Helper

**What it is**: A function defined in `config.php:39-45` that sets the HTTP response code via `http_response_code()`, sets the `Content-Type: application/json` header, encodes the payload as JSON with `json_encode()`, and terminates execution via `exit`.

**Why it is used**: Every API endpoint needs to return JSON. Without this helper, each file would need to repeat `http_response_code()`, `header()`, `echo json_encode(...)`, and `exit`. Centralizing this ensures consistent JSON formatting (`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` flags ensure UTF-8 characters and `/` are not escaped). The `exit` after output prevents any accidental HTML or whitespace from being appended to the response.

**Where it is used**:
- Defined in: `api/config.php:39-45`
- Called at: Every API file — `login.php`, `signup.php`, `chat.php`, `me.php`, `admin/users.php`, `admin/verify.php`, `professional/create-patient.php`, `db.php`

### 4.5 PDO with Prepared Statements — SQL Injection Prevention

**What it is**: PHP Data Objects (PDO) is a database access layer. In `api/db.php:14-22`, a PDO connection is created using credentials from `$_ENV`. Prepared statements with named parameters (`:email`, `:id`) are used in all queries. `PDO::ATTR_EMULATE_PREPARES => false` (`db.php:18`) forces native server-side prepared statements.

**Why it is used**: This is the primary SQL injection defense. With native prepared statements, the SQL query structure and data values are sent separately to MySQL — user input CANNOT alter the query structure. The `ERRMODE_EXCEPTION` setting means any database error throws an exception that can be caught with `try/catch`, preventing SQL errors from being displayed to the user. The `FETCH_ASSOC` mode returns column-name-indexed arrays for cleaner code.

**Where it is used**:
- Connection setup: `api/db.php:14-22`
- Named parameters: `api/login.php:18-19`, `api/signup.php:23-24, 48-55`, `api/admin/verify.php:20-21, 29-30`
- All queries use `prepare()` + `execute()` — never `query()` with concatenated strings

### 4.6 `password_hash()` / `password_verify()` — Password Hashing

**What it is**: PHP's built-in bcrypt password hashing functions. `password_hash($password, PASSWORD_DEFAULT)` generates a 60-character hash with a random salt embedded. `password_verify($password, $hash)` performs constant-time comparison against the stored hash.

**Why it is used**: Never stores plaintext passwords. `PASSWORD_DEFAULT` is future-proof — it currently uses bcrypt (cost 10) but will automatically upgrade to stronger algorithms as PHP evolves. The constant-time comparison in `password_verify()` prevents timing attacks where an attacker could measure response times to guess hash character by character.

**Where it is used**:
- Hash creation: `api/signup.php:45`, `api/professional/create-patient.php:30`, `database/seed.php:15`
- Hash verification: `api/login.php:25`

### 4.7 `session_start()` / `$_SESSION` — Session Management

**What it is**: PHP's built-in session system. `session_start()` creates or resumes a session, making `$_SESSION` available for storing user state across requests. The session ID is stored in a cookie on the client; the session data lives on the server.

**Why it is used**: Sessions maintain login state across HTTP requests (which are stateless). After a successful login (`api/login.php:30`), `$_SESSION['user_id']` is set to the user's database ID. Subsequent requests (like `api/me.php:6`) check `$_SESSION['user_id']` to verify the user is authenticated. Without sessions, the user would need to send their email and password with every request.

**Where it is used**:
- Session start: `api/login.php:6`, `api/me.php:4`, `api/admin/users.php:6`, `api/admin/verify.php:6`, `api/professional/create-patient.php:6`
- Session data set: `api/login.php:30` (`$_SESSION['user_id'] = $user['id']`)
- Session data read: `api/me.php:6` (`$_SESSION['user_id']`)

### 4.8 `$_ENV` / `putenv()` — Environment Variables

**What it is**: `$_ENV` is a PHP superglobal array containing environment variables. `putenv()` sets an environment variable at the process level. In `config.php`, both are used: `$_ENV[$key] = $value` and `putenv("$key=$value")`.

**Why it is used**: Dual storage (`$_ENV` + `putenv()`) ensures compatibility: some PHP configurations populate `getenv()` from `putenv()`, while others populate `$_ENV` from the server environment. Setting both guarantees the value is available via either access method. This is important because `db.php:8-12` uses `$_ENV['DB_HOST'] ?? getenv('DB_HOST')` — the null coalescing chain tries `$_ENV` first, then `getenv()`, then a hardcoded default.

**Where it is used**:
- Set in: `api/config.php:21-22` (inside `loadEnvFile()` loop)
- Read in: `api/db.php:8-12` (DB credentials), `api/bootstrap.php:177, 182` (Gemini API key and model), `api/setup.php:8-11` (DB credentials)

### 4.9 `json_encode()` / `json_decode()` — JSON Serialization

**What it is**: `json_encode()` converts PHP arrays/objects to JSON strings. `json_decode()` converts JSON strings back to PHP data structures. When the second parameter of `json_decode()` is `true`, it returns associative arrays instead of objects.

**Why it is used**: JSON is the data exchange format between the PHP backend and the JavaScript frontend. Every API response uses `json_encode()` (via `sendJson()`). The frontend sends JSON in `fetch()` request bodies, and the backend reads it with `json_decode()` (via `readJsonBody()`). The `JSON_UNESCAPED_UNICODE` flag preserves UTF-8 characters (like emoji or non-Latin scripts) instead of escaping them as `\uXXXX`.

**Where it is used**:
- `json_encode()`: `config.php:43` (in `sendJson()`), `bootstrap.php` (Gemini API payload), `auth/send-reset-email.php` (token storage)
- `json_decode()`: `config.php:35` (in `readJsonBody()`), `bootstrap.php` (Gemini API response), `auth/send-reset-email.php` (token file reading)

### 4.10 `try` / `catch` — Exception Handling

**What it is**: PHP's structured exception handling. `try` wraps code that may throw; `catch` handles specific exception types. Every database operation in the API files is wrapped in `try/catch` blocks.

**Why it is used**: Separates error handling from business logic. When a database query fails (e.g., duplicate email, connection lost), PDO throws a `PDOException`. The `catch` block calls `sendJson()` with an error message and appropriate HTTP status code (400, 401, 404, 409, 500). This ensures the API always returns valid JSON — even on failure — rather than crashing with an unhandled exception.

**Where it is used**:
- `api/login.php:16-51` (login logic with DB queries)
- `api/signup.php:20-59` (signup with duplicate check + insert)
- `api/admin/users.php:13-53` (user listing and update)
- `api/admin/verify.php:16-39` (approve/reject logic)
- `api/professional/create-patient.php:22-41` (patient creation)

### 4.11 `header()` / `http_response_code()` — HTTP Response Control

**What it is**: `header()` sends a raw HTTP header. `http_response_code()` sets the response status code. Used together in `sendJson()` to control the HTTP response.

**Why it is used**: All API responses must be JSON with the correct content type and status code. `header('Content-Type: application/json; charset=utf-8')` tells the browser to parse the response as JSON. `http_response_code($statusCode)` sets the correct HTTP status (200 for success, 400 for bad request, 401 for unauthorized, 403 for forbidden, 404 for not found, 409 for conflict, 500 for server error). The frontend's `fetch()` uses these status codes in its `.then()` and `.catch()` chains.

**Where it is used**:
- `sendJson()` in `config.php:40-41` (all API responses)
- `api/login.php:13, 22, 26, 50` (individual error responses)
- `api/signup.php:13, 17, 26, 59`

### 4.12 Null Coalescing `??` and Ternary `?:` — Shorthand Conditionals

**What it is**: `$a ?? $b` returns `$a` if it is set and not null, otherwise `$b`. `$a ?: $b` returns `$a` if truthy, otherwise `$b`. The key difference: `??` checks existence+non-null, while `?:` checks truthiness.

**Why it is used**: `??` provides safe fallback values when reading array keys that might not exist. In `config.php:21` (`if (!array_key_exists($key, $_ENV))`), it prevents overwriting already-set env vars. In `db.php:8-12`, the chain `$_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1'` tries three fallback levels: environment variable from `.env`, system environment variable, and hardcoded default.

**Where it is used**:
- `??`: `api/db.php:8-12` (DB credentials), `api/setup.php:8-11` (DB credentials), `api/bootstrap.php:177, 182` (Gemini credentials)
- `?:`: `api/db.php:8-12` (fallback defaults)

### 4.13 `curl_init()` / `curl_exec()` — HTTP Client

**What it is**: PHP's cURL functions for making HTTP requests to external servers. `curl_init()` creates a cURL session, `curl_setopt_array()` configures options (URL, method, headers, body, timeout), `curl_exec()` executes the request, `curl_getinfo()` retrieves response metadata, and `curl_close()` closes the session.

**Why it is used**: The chatbot needs to call the Google Gemini API (`https://generativelanguage.googleapis.com/v1beta/models/...:generateContent`). cURL is used because PHP's `file_get_contents()` with stream context is less reliable for POST requests with custom headers and timeouts. The 25-second timeout (`CURLOPT_TIMEOUT => 25`) prevents the chatbot from hanging indefinitely if the API is slow. Error handling checks `curl_exec()` return value AND HTTP status code, falling back to local responses if the API call fails.

**Where it is used**:
- `api/bootstrap.php:332-403` (`queryGeminiChat()` function — full cURL lifecycle)
- Options set: POST method, JSON body, Content-Type header, 25s timeout, return transfer

### 4.14 `file_get_contents('php://input')` — Raw Request Body

**What it is**: PHP's stream wrapper `php://input` provides read-only access to the raw HTTP request body. `file_get_contents()` reads the entire stream into a string.

**Why it is used**: When the frontend sends JSON data via `fetch()` with `Content-Type: application/json`, PHP's `$_POST` superglobal is empty — it only parses `application/x-www-form-urlencoded` and `multipart/form-data`. `php://input` is the only way to access the raw JSON body regardless of content type.

**Where it is used**:
- `api/config.php:31` (inside `readJsonBody()`)

### 4.15 `password_hash()` Cost and Algorithm

**What it is**: The `PASSWORD_DEFAULT` constant in `password_hash()` uses bcrypt with a cost factor of 10 (current default). The cost factor determines how many iterations the hashing algorithm performs — cost 10 means 2^10 = 1,024 iterations. Each iteration makes the hash computation exponentially slower.

**Why it is used**: Higher cost factors make brute-force attacks more expensive. A bcrypt hash with cost 10 takes ~100ms to compute on modern hardware. While this is negligible for a single login, it makes cracking a database of hashes prohibitively slow. The `PASSWORD_DEFAULT` constant will automatically increase the cost as hardware improves, without requiring code changes.

**Where it is used**:
- `api/signup.php:45`, `api/professional/create-patient.php:30`, `database/seed.php:15`, `create_admin.php:11`

### 4.16 `bin2hex(random_bytes(32))` — Secure Random Token Generation

**What it is**: `random_bytes()` generates cryptographically secure pseudo-random bytes from the operating system's CSPRNG. `bin2hex()` converts binary bytes to a hexadecimal string. `random_bytes(32)` produces 32 bytes = 256 bits of entropy, resulting in a 64-character hex string.

**Why it is used**: Password reset tokens must be unpredictable. Using `rand()` or `mt_rand()` would be insecure because they are deterministic — given the seed, all outputs are predictable. `random_bytes()` draws entropy from `/dev/urandom` on Linux, which is the same source used by TLS/SSL for generating encryption keys. The 256-bit entropy makes brute-force prediction computationally infeasible.

**Where it is used**:
- `auth/send-reset-email.php:19` (`$resetToken = bin2hex(random_bytes(32))`)
- `api/login.php:35` (`$token = bin2hex(random_bytes(16))` for login session token)

---

## 5. CLIENT-SIDE ARCHITECTURE

### 5.1 `auth.js` — Client-Side Auth Guard

**What it is**: An IIFE (Immediately Invoked Function Expression) that runs on every page load. It implements a role-based access control system entirely on the client side using `localStorage`.

**Key components:**

| Component | What It Does |
|---|---|
| `ROLE_HOME` | Maps each role to their default landing page path (e.g., `patient: "/patient/dashboard.html"`). Uses absolute paths from root so redirects work from any directory. |
| `PUBLIC_PAGES` | Set of page filenames that do not require authentication (landing page, login, signup, etc.). |
| `PAGE_RULES` | Array of `{pages: [...], roles: [...]}` objects. Maps specific page filenames to the roles allowed to view them. Uses just filenames (not paths) so it works regardless of which subdirectory the page is served from. |
| `NAV_RULES` | Array of `{href: "...", roles: [...]}` objects. Maps nav link hrefs to the roles that can see them. Used by `hideUnauthorizedNavLinks()` to hide links the current user should not see. |
| `getCurrentFile()` | Extracts just the filename from `window.location.pathname` by splitting on `/` and taking the last segment. |
| `initRoleGuard()` | Main function called on `DOMContentLoaded`. Checks if current user's role matches the page's `PAGE_RULES`. Redirects to login or role home as needed. Updates nav visibility. |
| `updateAuthNav()` | Replaces the static "Profile" nav link with a dropdown showing the user's name, links to view/edit profile, personal progress, settings, and a logout button. |

**Data flow on page load:**
1. `auth.js` runs, calls `initRoleGuard()`
2. Reads `solaceCurrentUser` from localStorage
3. If user is logged in and on login/signup page → redirect to role home
4. If page is protected and no user → redirect to `/auth/login.html`
5. If page is protected and user has wrong role → redirect to role home
6. If all checks pass → show page, hide unauthorized nav links, update profile dropdown

### 5.2 localStorage Data Model

The application stores user data in two localStorage keys:

| Key | Format | Purpose |
|---|---|---|
| `solaceCurrentUser` | `{email, displayName, role, loggedInAt}` | Currently logged-in user. Read by `auth.js` on every page load. Set during login (server or localStorage fallback). |
| `solaceUserProfile` | `{name, displayName, email, password, role, ...}` | Full user profile including password. Set during signup. Used as fallback auth when server is offline. |

### 5.3 Password Toggle Buttons

Every password field on login, signup, and reset-password pages has a toggle button (eye icon) that switches the input between `type="password"` and `type="text"`. This is implemented with a click event listener on all `.password-toggle-button` elements. The button icon changes between 👁 (show) and 🙈 (hide), and `aria-label` and `title` attributes are updated for accessibility.

### 5.4 Password Strength Meter

On `signup.html` and `reset-password.html`, the password field has real-time validation:
- Three checklist items: capital letter, number, special character
- A strength bar that fills from 0% to 100% as requirements are met
- Visual color coding: red (weak), orange (medium), green (strong)
- The strength check runs on every `input` event for instant feedback

---

## 6. SECURITY PATTERNS

### 6.1 SQL Injection Prevention

**How it works**: Every database query uses PDO prepared statements with named parameters (`:email`, `:id`). The connection setting `PDO::ATTR_EMULATE_PREPARES => false` forces native server-side prepared statements. The SQL structure and parameter values are sent to MySQL in separate packets, making it impossible for user input to alter the query structure.

**Where it is applied**: `api/login.php:18`, `api/signup.php:23, 47-55`, `api/admin/users.php:37-43`, `api/admin/verify.php:20-29`, `api/professional/create-patient.php:25-37`, `api/me.php:15-16`

### 6.2 Password Storage

**How it works**: Passwords are hashed with `password_hash($password, PASSWORD_DEFAULT)` which uses bcrypt with a random salt. Never stored in plaintext. Verification uses `password_verify()` which performs constant-time comparison.

**Where it is applied**: `api/signup.php:45`, `api/login.php:25`, `api/professional/create-patient.php:30`

### 6.3 Professional Pending Approval

**How it works**: Professional accounts are created with `status = 'pending'`. The login endpoint checks `status` and rejects pending professionals. Only an admin can change the status to `active` via `api/admin/verify.php`. This prevents unverified professionals from accessing the system.

**Where it is applied**: `api/signup.php:46` (sets pending), `api/login.php:28-30` (checks status), `api/admin/verify.php` (approve/reject)

### 6.4 Input Validation

**How it works**: Every API endpoint validates input before processing. Email validation uses both `filter_var(FILTER_VALIDATE_EMAIL)` and regex. Required field checks use `trim() === ''`. Role/status values are validated against allowed ENUM values before database updates.

**Where it is applied**: `api/login.php:12-14`, `api/signup.php:12-17`, `api/admin/users.php:33-36`, `api/admin/verify.php:15-17`, `api/professional/create-patient.php:16-19`

### 6.5 Environment Variable Security

**How it works**: Database credentials and API keys are stored in `.env`, which is listed in `.gitignore` and never committed. The `config.php` loader populates `$_ENV` from the file. In case `.env` is missing, fallback values are provided, but the production system should always have `.env` configured.

**Where it is applied**: `.env` (credentials), `.gitignore` (prevents commits), `api/config.php` (loading), `api/db.php:8-12` (consumption)

### 6.6 CSRF Token for Password Reset

**How it works**: Password reset tokens are generated with `bin2hex(random_bytes(32))`, producing a 64-character unpredictable hex string. Tokens expire after 1 hour. The reset link includes the token as a query parameter, and `verify-reset-token.php` validates both the token value and its expiry.

**Where it is applied**: `auth/send-reset-email.php:19-20` (generation + expiry), `auth/verify-reset-token.php` (validation)

---

## 7. ANTICIPATED DEFENSE Q&A

### Q1: "Your `auth.js` stores the logged-in user in localStorage. What stops a user from editing localStorage to change their role and access admin pages?"

**Answer**: The client-side auth guard in `auth.js` is a convenience layer for UX — it hides nav links and redirects users to prevent confusing access-denied screens. The real security is on the server side. Every PHP API endpoint that handles sensitive operations checks the server-side session (`session_start()` + `$_SESSION['user_id']`) and queries the database for the actual role. For example, `api/admin/users.php` and `api/admin/verify.php` require the user to be authenticated via PHP session — manipulating localStorage would not create a valid PHP session. The client-side check is there to improve user experience, not as a security boundary.

### Q2: "Explain the professional approval workflow from signup to login."

**Answer**: The workflow has four stages:
1. **Signup** (`api/signup.php:46`): When a professional signs up (via invite token), the `status` column is set to `'pending'` instead of `'active'`.
2. **Login blocked** (`api/login.php:28-30`): When the professional tries to log in, the login endpoint checks `$user['status']`. If status is `'pending'`, the request is rejected with a 403 status and the message "Your account is pending verification."
3. **Admin approval** (`api/admin/verify.php`): An admin views the pending professionals list from the admin dashboard, which fetches data via `api/admin/users.php?role=professional`. The admin clicks "Approve", which sends a POST to `api/admin/verify.php` with `{user_id, action: 'approve'}`. The endpoint updates the user's status to `'active'`.
4. **Login allowed**: After approval, the professional can log in normally. The status check passes, `$_SESSION['user_id']` is set, and they are redirected to the clinical dashboard.

### Q3: "Your `config.php` loads `.env` with a custom function instead of using a library like `vlucas/phpdotenv`. Why?"

**Answer**: The project intentionally avoids external dependencies. Using Composer and third-party libraries would add complexity — the student would need to install Composer, run `composer install`, manage `vendor/` directories, and understand autoloading. The custom `loadEnvFile()` function is 40 lines of straightforward PHP that any beginner can read and understand. It reads a file line by line, splits on `=`, and populates `$_ENV` — there is no hidden behavior. This simplicity is a deliberate design choice: the code must be defensible by a beginner programmer in front of an academic panel.

### Q4: "Why does the signup form have a password strength meter with specific requirements (capital letter, number, special character)?"

**Answer**: The password strength requirements enforce a minimum level of password complexity to protect user accounts. The three rules (uppercase, digit, special character) are common password policies that prevent the most common weak passwords (like "password123" or "admin"). The real-time meter and checklist provide instant feedback so the user knows exactly which requirements they have met — this improves user experience compared to showing a generic "password too weak" error after form submission. The validation runs on both the client side (JavaScript in `signup.html`) and the server side (`isStrongPassword()` in `signup.html`'s inline JS) — the client provides immediate feedback, and the form submission enforces it before sending data to the server.

### Q5: "Walk through what happens from the moment a user clicks 'LOG IN' to the moment they see their dashboard."

**Answer**: The flow has eight phases:

1. **Frontend validation** (`login.html` inline JS): The click handler checks that email and password are not empty and that the email format is valid. If validation fails, an error message is displayed without making a network request.

2. **Server request**: A POST request is sent to `api/login.php` via `fetch()` with JSON body `{email, password, role}`.

3. **Input parsing** (`api/login.php:8-10`): `readJsonBody()` reads the raw request body from `php://input`, decodes it from JSON, and returns the data array. Email and password are extracted and trimmed.

4. **Database lookup** (`api/login.php:17-20`): A prepared statement `SELECT id, name, display_name, email, password_hash, role, status FROM users WHERE email = :email` is executed. If no user is found, a 401 error is returned.

5. **Password verification** (`api/login.php:25`): `password_verify($password, $user['password_hash'])` compares the submitted password against the stored bcrypt hash. If mismatch, a 401 error is returned.

6. **Status check** (`api/login.php:28-30`): If the user's role is `professional` and status is `pending`, the login is rejected with a 403 error.

7. **Session creation** (`api/login.php:30, 33-44`): `$_SESSION['user_id']` is set to the user's database ID. A `login_sessions` record is inserted with a random token, user agent, and IP address for audit logging.

8. **Response and redirect** (`api/login.php:48`, `login.html` inline JS): The server returns `{ok: true, user: {email, displayName, role}}`. The frontend stores this in localStorage as `solaceCurrentUser`, then redirects to the role-specific home page (e.g., `/patient/dashboard.html` for patients).

### Q6: "Your `api/chat.php` has an abuse detection system. How does it work?"

**Answer**: The abuse detection system in `api/bootstrap.php` has three layers:

1. **Input classification** (`classifyInput()`): The user's message is scanned for crisis keywords ("suicide", "kill myself", "self harm"), harm keywords ("hurt someone", "weapon"), prompt injection attempts ("ignore previous instructions", "jailbreak"), and clinical boundary violations ("diagnose", "prescribe"). Each category returns a boolean flag, and the overall threat level is set to `'crisis'`, `'high'`, or `'normal'`.

2. **Abuse state tracking** (`getClientState()` / `updateClientState()`): Each client IP (stored as a SHA-256 hash for privacy) has a numeric "abuse score" that decays by 1 point every 30 seconds of inactivity. Suspicious behavior (rapid repeated messages, trigger pattern matches) increases the score. The abuse state is persisted to `server/logs/abuse_state.json`.

3. **Response filtering** (`safeOutputFilter()`): The AI's response is checked for content violations. If the response contains diagnostic language ("you definitely have"), it is replaced with a boundary-enforcing response. Long responses are truncated to 1200 characters.

### Q7: "Your clinician dashboard has a server-side session check (`api/me.php`) in addition to the client-side `auth.js` guard. Why both?"

**Answer**: The client-side `auth.js` guard is fast and provides immediate UX feedback — it runs on every page load from `localStorage` without a network request. However, `localStorage` can be edited by the user via browser developer tools. The server-side check (`api/me.php`) verifies that the PHP session is valid — this requires a valid session cookie that cannot be forged without knowing the session ID. The dual approach means: (1) if `localStorage` has an expired/invalid user, `auth.js` redirects immediately without a server round-trip; (2) if someone manipulates `localStorage` to pretend to be a clinician, the server-side check catches it and redirects to login. This is defense-in-depth.

### Q8: "Why are patient tools (mood, journal, etc.) saving to localStorage instead of the database?"

**Answer**: This was a deliberate design decision to keep the frontend simple and functional. The core requirement of the application is the role-based access control and professional/admin workflows — the patient self-help tools are secondary features that should work reliably without backend dependency. localStorage persistence means: (1) patient data survives page refreshes, (2) the tools work even when the PHP server is down, (3) there is no need to write CRUD APIs for 10+ patient features, which would have doubled the backend complexity. The database is used for what matters: authentication, authorization, user management, and professional workflows. This separation also makes the project more defensible academically — the student can explain that localStorage is appropriate for client-only data, while the database stores authoritative user and role data.

### Q9: "Your `login.html` has three role-based redirects (lines 146, 163, 181). Why are there three separate redirect paths?"

**Answer**: The three redirect paths correspond to three different authentication scenarios: (1) successful server login (`result.ok === true`), (2) localStorage fallback when server responds but without a valid user (e.g., DB not configured), and (3) network error fallback when the server is completely unreachable. Each scenario redirects to the same `roleHome` path but uses different user data sources: the server response, the localStorage profile, or the form-selected role. The pattern ensures the user always ends up at the correct dashboard regardless of which authentication path succeeded.

### Q10: "The `onboarding-helper.md` exists alongside `onboarding.md`. Why two separate documents?"

**Answer**: The two documents serve different audiences and purposes:
- `onboarding.md` is a **user guide** for testers and evaluators. It tells them how to run the application, which URLs to visit, what buttons to click, and what to expect. It requires no programming knowledge.
- `onboarding-helper.md` (this document) is a **defense document** for the student presenting the project. It explains how the code works at a technical level — directory structure, PHP constructs, architectural decisions, and answers to anticipated questions. It is designed to help the student explain their code to an academic panel.

---

*Document generated from analysis of the Solace Sphere codebase at the time of restructure. All code references, line numbers, and examples are sourced directly from the project files. Database schema includes 21 tables covering authentication, patient data, professional workflows, and community features.*
