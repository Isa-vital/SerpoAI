#!/bin/bash
# SerpoAI Deployment Script
# Run this on your server: bash deploy-server.sh

set -e  # Exit on any error

echo "========================================="
echo "SerpoAI Deployment Script"
echo "========================================="
echo ""

# Step 1: Install Composer
echo "Step 1: Installing Composer..."
if ! command -v composer &> /dev/null; then
    sudo apt update
    sudo apt install composer -y
    echo "✓ Composer installed"
else
    echo "✓ Composer already installed"
fi

# Step 2: Navigate to Laravel app
echo ""
echo "Step 2: Navigating to Laravel app..."
cd ~/laravel_app
echo "✓ Current directory: $(pwd)"

# Step 3: Install PHP dependencies
echo ""
echo "Step 3: Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader
echo "✓ Dependencies installed"

# Step 4: Create .env file
echo ""
echo "Step 4: Setting up .env file..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ .env file created from .env.example"
else
    echo "⚠ .env file already exists, skipping..."
fi

# Step 5: Generate application key
echo ""
echo "Step 5: Generating application key..."
php artisan key:generate --force
echo "✓ Application key generated"

# Step 6: Set permissions
echo ""
echo "Step 6: Setting permissions..."
chmod -R 775 storage bootstrap/cache
echo "✓ Permissions set"

# Step 7: Clear caches
echo ""
echo "Step 7: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✓ Caches cleared"

echo ""
echo "========================================="
echo "✓ Basic deployment completed!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Edit .env file with your credentials:"
echo "   nano .env"
echo ""
echo "2. Required .env variables:"
echo "   - TELEGRAM_BOT_TOKEN"
echo "   - DB_DATABASE, DB_USERNAME, DB_PASSWORD"
echo "   - API_KEY_TON"
echo "   - COMMUNITY_CHANNEL_ID"
echo ""
echo "3. Run migrations:"
echo "   php artisan migrate --force"
echo ""
echo "4. Check web server configuration"
echo "5. Set up webhook"
echo "6. Start monitoring service"
