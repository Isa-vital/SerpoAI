#!/bin/bash

# SerpoAI Deployment Script
# Run this on the server as deploy user

set -e

echo "🚀 Starting SerpoAI Deployment..."

# Configuration
DEPLOY_DIR="/var/www/serpoai"
REPO_URL="https://github.com/Isa-vital/SerpoAI.git"
BRANCH="main"

# Check if directory exists
if [ ! -d "$DEPLOY_DIR" ]; then
    echo "📁 Creating deployment directory..."
    sudo mkdir -p $DEPLOY_DIR
    sudo chown -R deploy:deploy $DEPLOY_DIR
    
    echo "📦 Cloning repository..."
    git clone -b $BRANCH $REPO_URL $DEPLOY_DIR
    cd $DEPLOY_DIR
else
    echo "📂 Deployment directory exists, pulling latest changes..."
    cd $DEPLOY_DIR
    git fetch origin
    git reset --hard origin/$BRANCH
fi

echo "📋 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

echo "📋 Installing NPM dependencies..."
npm install

echo "🏗️  Building assets..."
npm run build

echo "⚙️  Setting up environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "⚠️  Please configure .env file with your settings!"
fi

echo "🔑 Generating application key..."
php artisan key:generate --force

echo "📦 Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🗄️  Running migrations..."
php artisan migrate --force

echo "🔐 Setting permissions..."
sudo chown -R deploy:www-data $DEPLOY_DIR
sudo chmod -R 775 $DEPLOY_DIR/storage
sudo chmod -R 775 $DEPLOY_DIR/bootstrap/cache

echo "🔄 Restarting services..."
sudo systemctl restart serpoai-monitor.service 2>/dev/null || echo "⚠️  Monitor service not set up yet"
sudo systemctl reload php8.2-fpm 2>/dev/null || sudo systemctl reload php-fpm 2>/dev/null || echo "⚠️  PHP-FPM reload skipped"

echo "✅ Deployment completed successfully!"
echo ""
echo "📝 Next steps:"
echo "1. Configure .env file: nano $DEPLOY_DIR/.env"
echo "2. Set up Nginx: sudo nano /etc/nginx/sites-available/serpoai"
echo "3. Set up monitoring service: sudo systemctl enable serpoai-monitor.service"
echo "4. Check logs: tail -f $DEPLOY_DIR/storage/logs/laravel.log"
