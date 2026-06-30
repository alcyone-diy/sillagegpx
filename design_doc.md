# Design Document : SillageGPX

## 1. Introduction and Objectives
The objective is to create a web application in PHP to record and visualize a sailing logbook (SillageGPX).
The system is centered around the concept of a **Trip**. A trip is a container that regroups several pieces of information (title, dates, comments, links) as well as **GPX Tracks**.
From the design phase, the application is intended to be multi-user and finely manages the privacy of trips (public, unlisted, private) with the ability to revoke/modify shared links.

## 2. Technological Choices
*   **Backend**: PHP 8+ (Object-Oriented, custom micro-framework with native router).
*   **Database**: **SQLite**. Extremely portable, fast, and sufficient to store metadata with zero-configuration deployment. The database is stored locally in the `db/` folder.
*   **Frontend**: HTML5, Vanilla JavaScript, and Vanilla CSS for a custom, elegant, and performant design (without overloading with Tailwind).
*   **Mapping**: **Leaflet.js** with the `leaflet-gpx` plugin (or conversion to GeoJSON by the backend) for interactive rendering of tracks on a map background (e.g., OpenStreetMap / OpenSeaMap).

## 3. Data Model (Relational Schema)

### `users` Table
*   `id` (PK, INT, Auto-increment)
*   `username` (VARCHAR, Unique)
*   `email` (VARCHAR, Unique)
*   `password_hash` (VARCHAR)
*   `created_at` (DATETIME)

### `trips` Table
*   `id` (PK, INT, Auto-increment)
*   `user_id` (FK, INT) -> `users.id`
*   `title` (VARCHAR)
*   `start_date` (DATE, Nullable)
*   `end_date` (DATE, Nullable)
*   `boat_name` (VARCHAR, Nullable) - Name of the boat
*   `comment` (TEXT)
*   `visibility` (ENUM: 'public', 'unlisted', 'private') - Default: 'private'
*   `unlisted_token` (VARCHAR, Unique, Nullable) - Random token used for unlisted URLs.
*   `views_count` (INT) - Visit counter (useful for the owner). Default: 0.
*   `created_at` (DATETIME)
*   `updated_at` (DATETIME)

### `trip_steps` Table (Navigation steps)
*   `id` (PK, INT)
*   `trip_id` (FK, INT) -> `trips.id` (ON DELETE CASCADE)
*   `title` (VARCHAR) - e.g.: "Tuesday" or "Outward Crossing"
*   `order_index` (INT) - Order of the step
*   `created_at` (DATETIME)

### `trip_links` Table (Links associated with the trip)
*   `id` (PK, INT)
*   `trip_id` (FK, INT) -> `trips.id` (ON DELETE CASCADE)
*   `url` (VARCHAR)
*   `label` (VARCHAR)

### `gpx_tracks` Table (Associated tracks)
*   `id` (PK, INT)
*   `trip_step_id` (FK, INT) -> `trip_steps.id` (ON DELETE CASCADE)
*   `file_path` (VARCHAR) - File path stored on the server.
*   `start_time` (DATETIME) - Extracted during upload.
*   `end_time` (DATETIME) - Extracted during upload.
*   `distance_meters` (FLOAT) - Calculated during upload.
*   `duration_seconds` (INT) - Calculated during upload.
*   `avg_speed_knots` (FLOAT) - Calculated average speed.
*   `max_speed_knots` (FLOAT) - Calculated max speed.
*   `created_at` (DATETIME)

### `login_attempts` Table (Brute-force protection)
*   `id` (PK, INT)
*   `ip_address` (VARCHAR)
*   `attempt_time` (DATETIME)

### `user_passkeys` Table (WebAuthn / Passkeys)
*   `id` (PK, INT)
*   `user_id` (FK, INT) -> `users.id` (ON DELETE CASCADE)
*   `credential_id` (TEXT)
*   `public_key` (TEXT)
*   `user_handle` (TEXT)
*   `sign_count` (INT)
*   `created_at` (DATETIME)

## 4. Processing Logic and Features

### A. Security & Authentication
1.  **Authentication**: Uses modern standard passwords + **WebAuthn (Passkeys)** support for passwordless login using TouchID, FaceID, or hardware keys (via `lbuchs/webauthn`).
2.  **Brute-Force Protection**: IP-based rate limiting (max 5 failed attempts per 15 minutes) tracked in the `login_attempts` table.
3.  **Bot Protection**: Cloudflare Turnstile CAPTCHA integration during registration to prevent spam.
4.  **Visibility Management**:
    *   **Public**: The trip appears on the user's public profile. Accessible via the classic trip ID (e.g., `/trip.php?id=123`).
    *   **Private**: Only the owner (logged in) can see it. Anyone else gets a 403 error.
    *   **Unlisted**: The trip does not appear on public lists. It is only accessible via a unique token (e.g., `/trip.php?token=abc123xyz`). 
        *   *Revocation*: The user can click on "Generate a new share link". The backend regenerates the `unlisted_token` in the database, making the old link instantly invalid.

### B. GPX Files Processing
When a user uploads one or more GPX files for a trip:
1.  The PHP script parses the GPX file XML (via `simplexml_load_file`).
2.  It analyzes the `<trkpt>` tags to extract the start date, end date, calculate the cumulative distance (Haversine formula), the actual sailing time, and instantaneous speeds (to deduce average and max).
3.  These statistics are saved in the `gpx_tracks` table.
4.  **Downsampling**: The script generates two lightweight datasets in JSON format to optimize frontend rendering:
    *   **Map points**: Sampled at **1 point per minute** for smooth and fast rendering on the map, even for long crossings.
    *   **Speed points**: Sampled at **1 point every 10 minutes** to generate a readable and smoothed speed curve.
5.  The raw file is saved in a secure folder as an archive.

### C. Visualization (Mapping and Charts)
The visualization interface for a trip will offer a map (Leaflet), a speed chart (e.g., Chart.js), and a timeline by **Steps** (and not strictly by calendar day).
*   **Global View**: Loads the lightweight data to draw the path on the map and displays the speed curve over the entire duration of the trip.
*   **Step View**: The user selects a specific step (which can regroup several close navigations or a continuous crossing). The frontend dynamically filters the map and the speed curve (1 point every 10 min) to target only this step.
*   **Manual Step Creation**: The navigator has the choice to split and group their GPX tracks into "Steps" as they wish when editing the trip.
*   **Audience Tracking**: Display of the number of people who have viewed the page (via `views_count`), data visible exclusively to the trip owner.

## 5. User Interface (UI/UX) & i18n
*   **Theme and Design**: Modern, premium interface, with native Dark Mode support. Use of harmonious palettes (e.g., marine themes, deep blues, slate grays, "Glassmorphism" effects for modals).
*   **Internationalization (i18n)**: The application fully supports bilingual views (English and French). Language dictionaries are managed centrally, and user preferences are saved via cookies.
*   **User Dashboard**: List of their trips in the form of elegant cards with visibility badges (Public/Private/Shared link).
*   **Trip Management & Editing**: Smooth form with drag & drop for GPX track uploads, and the ability to entirely delete a trip (with automatic cleanup of associated files and data).
*   **Micro-interactions**: Subtle animations on hover over timeline elements and map markers.

## 6. Deployment & Architecture
*   **Zero-Config Installation**: The project includes an automated `install.sh` script that handles folder creation (`data/`, `db/`), permission management, Composer dependency installation, and SQLite schema initialization.
*   **Dependency Management**: `composer.json` tracks dependencies like `lbuchs/webauthn` for secure and reproducible environments.

## 7. Possible Future Evolutions
*   **Historical Weather Overlay**: Integrate an API to display historical winds/currents corresponding to the dates and locations of the GPX track.
*   **GPX Export**: Offer visitors the possibility to download the raw GPX track if the user has made the trip public or shared it.

*(Note: For simplicity, photos will be managed via external links (e.g., Google Photos) added to the trip.)*
