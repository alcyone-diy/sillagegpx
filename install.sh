#!/bin/bash

echo "🚀 Starting SillageGPX installation..."

# 1. Check for required directories
echo "📂 Creating necessary directories..."
mkdir -p data
mkdir -p db

# 2. Manage permissions
echo "🔒 Configuring permissions..."
# The web server (www-data, apache, nobody...) needs write access to these folders
chmod 777 data
chmod 777 db

# 3. Install PHP dependencies via Composer
echo "📦 Installing PHP dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
    echo "✅ Dependencies installed."
else
    echo "⚠️ WARNING: 'composer' is not installed on this system."
    echo "Please install Composer, then manually run: composer install"
fi

# 4. Initialize the database
echo "🗄️ Checking database..."
if [ ! -f "db/sillage.sqlite" ]; then
    if command -v sqlite3 &> /dev/null; then
        echo "Creating SQLite database..."
        sqlite3 db/sillage.sqlite < db/schema.sql
        chmod 666 db/sillage.sqlite
        echo "✅ Database initialized."
    else
        echo "⚠️ WARNING: 'sqlite3' is not installed."
        echo "Please create the database manually from db/schema.sql"
    fi
else
    echo "ℹ️ Database already exists. No action required."
    chmod 666 db/sillage.sqlite
fi

# 5. Local configuration
echo "⚙️ Checking local configuration..."
if [ ! -f "src/config.local.php" ]; then
    echo "⚠️ The src/config.local.php file is missing."
    echo "Creating an empty config.local.php file..."
    cat > src/config.local.php << 'EOF'
<?php
// Local configuration - DO NOT COMMIT TO GIT
define('TURNSTILE_SITE_KEY', '');
define('TURNSTILE_SECRET_KEY', '');
define('ADMIN_EMAIL', 'admin@example.com');
EOF
    echo "Please edit src/config.local.php to add your Turnstile keys and email."
else
    echo "✅ Local configuration found."
fi

echo "🎉 Installation complete!"
echo "Do not forget to configure your web server (Apache/Nginx) to point to the 'public' directory."
