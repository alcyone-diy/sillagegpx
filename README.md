# SillageGPX

SillageGPX is a modern, elegant, and fast web application designed for sailors to record, manage, and visualize their sailing logbooks. It parses GPX tracks, generates interactive maps and speed charts, and provides fine-grained privacy controls for sharing your journeys.

## Features

- **Interactive Maps & Charts**: Visualize your sailing tracks using Leaflet.js. The backend automatically downsamples GPX data (1 point/min for maps, 1 point/10min for speed charts) to ensure smooth rendering even for long ocean crossings.
- **Trip Organization**: Group multiple GPX tracks into a single "Trip" with separate "Steps" (e.g., "Outward Crossing", "Return").
- **Privacy Controls**: Finely manage the visibility of your trips:
  - **Public**: Visible on your public profile.
  - **Private**: Visible only to you when logged in.
  - **Unlisted**: Accessible only via a unique secret link (token can be regenerated at any time to revoke access).
- **Passwordless Authentication**: Secure and fast login using WebAuthn (Passkeys) — supports TouchID, FaceID, and hardware security keys.
- **Modern UI**: A premium, bilingual (English & French) interface featuring dark mode and glassmorphism design elements, built entirely with Vanilla CSS.
- **Zero-Config Database**: Powered by SQLite for extreme portability and easy deployment.

## Technology Stack

- **Backend**: PHP 8+ (Custom object-oriented micro-framework)
- **Database**: SQLite 3
- **Frontend**: HTML5, Vanilla JavaScript, Vanilla CSS
- **Mapping & Charts**: Leaflet.js
- **Dependencies**: Composer (for `lbuchs/webauthn`)

## Installation

The project includes an automated installation script for Unix-based systems.

1. **Clone the repository**:
   ```bash
   git clone https://github.com/alcyone-diy/SillageGPX.git
   cd SillageGPX
   ```

2. **Run the installation script**:
   ```bash
   ./install.sh
   ```
   This script will:
   - Create the necessary `data/` and `db/` directories.
   - Configure directory permissions (`chmod 777` for web server access).
   - Install PHP dependencies using Composer.
   - Initialize the SQLite database from `db/schema.sql`.
   - Create a template `src/config.local.php` file if it doesn't exist.

3. **Configure the application**:
   Open `src/config.local.php` and configure your local settings (e.g., Cloudflare Turnstile keys and admin email):
   ```php
   <?php
   // Local configuration - DO NOT COMMIT TO GIT
   define('TURNSTILE_SITE_KEY', 'your-site-key');
   define('TURNSTILE_SECRET_KEY', 'your-secret-key');
   define('ADMIN_EMAIL', 'admin@example.com');
   ```

4. **Web Server Configuration**:
   Point your web server's (Apache/Nginx) document root to the `public/` directory of the project. The `.htaccess` file handles URL rewriting for the front controller.

## Data Processing Details

When you upload a GPX file, the backend extracts the following data:
- Start and end times.
- Cumulative distance calculated via the Haversine formula.
- Instantaneous speeds, average speed, and maximum speed.

The raw GPX file is safely archived, while the optimized downsampled JSON data is served to the frontend for maximum performance.

## License

This project is licensed under the MIT License.
