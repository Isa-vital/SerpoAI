# SerpoAI Production Deployment Guide

## Prerequisites on Server
- Ubuntu 20.04+ or Debian 11+
- PHP 8.2+
- MySQL 8.0+
- Nginx
- Composer
- Node.js 18+
- Git

## Step 1: Initial Server Setup

SSH into your server as the deploy user:
```bash
ssh deploy@72.62.43.137
```

## Step 2: Clone Repository

```bash
cd /var/www
sudo mkdir -p serpoai
sudo chown -R deploy:deploy serpoai
git clone https://github.com/Isa-vital/SerpoAI.git serpoai
cd serpoai
```

## Step 3: Run Deployment Script

```bash
chmod +x deploy.sh
./deploy.sh
```

This will install dependencies, build assets, and set up basic configuration.

## Step 4: Configure Environment

Edit the `.env` file with production settings:
```bash
nano .env
```

**Important settings to configure:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://serpoai.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=serpoai
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_COMMUNITY_CHANNEL_ID=your_channel_id
TELEGRAM_OFFICIAL_CHANNEL_ID=your_official_channel_id

API_KEY_TON=your_ton_api_key
SERPO_CONTRACT_ADDRESS=EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw
SERPO_DEX_PAIR_ADDRESS=EQAKSRrtI6SNsGeQ0N4d7DQtt7GPfMH72QQ4K1SLCorG0Dwc

COINGECKO_API_KEY=your_coingecko_key
```

## Step 5: Setup Database

Create the database:
```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE serpoai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'serpoai_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON serpoai.* TO 'serpoai_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Run migrations:
```bash
php artisan migrate --force
```

## Step 6: Setup Nginx

Copy the Nginx configuration:
```bash
sudo cp deployment/nginx-serpoai.conf /etc/nginx/sites-available/serpoai
sudo ln -s /etc/nginx/sites-available/serpoai /etc/nginx/sites-enabled/
```

Test Nginx configuration:
```bash
sudo nginx -t
```

Reload Nginx:
```bash
sudo systemctl reload nginx
```

## Step 7: Setup Monitor Service

Copy the systemd service file:
```bash
sudo cp deployment/serpoai-monitor.service /etc/systemd/system/
```

Enable and start the service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable serpoai-monitor.service
sudo systemctl start serpoai-monitor.service
```

Check status:
```bash
sudo systemctl status serpoai-monitor.service
```

View logs:
```bash
sudo journalctl -u serpoai-monitor.service -f
```

## Step 8: Setup SSL (Optional but Recommended)

Install Certbot:
```bash
sudo apt install certbot python3-certbot-nginx -y
```

Get SSL certificate:
```bash
sudo certbot --nginx -d serpoai.com -d www.serpoai.com
```

## Step 9: Set Correct Permissions

```bash
sudo chown -R deploy:www-data /var/www/serpoai
sudo chmod -R 775 /var/www/serpoai/storage
sudo chmod -R 775 /var/www/serpoai/bootstrap/cache
```

## Step 10: Verify Deployment

1. **Check website**: Visit https://serpoai.com
2. **Check monitor**: `sudo systemctl status serpoai-monitor.service`
3. **Check logs**: `tail -f storage/logs/laravel.log`
4. **Test whale alerts**: Monitor should detect 50+ TON buys

## Future Deployments

To update the site:
```bash
cd /var/www/serpoai
git pull origin main
./deploy.sh
sudo systemctl restart serpoai-monitor.service
```

## Troubleshooting

### Monitor not starting
```bash
sudo journalctl -u serpoai-monitor.service -n 50
```

### Permission errors
```bash
sudo chown -R deploy:www-data /var/www/serpoai
sudo chmod -R 775 storage bootstrap/cache
```

### Nginx 502 error
```bash
sudo systemctl status php8.2-fpm
sudo tail -f /var/log/nginx/serpoai-error.log
```

### Database connection error
Check `.env` database credentials and ensure MySQL is running:
```bash
sudo systemctl status mysql
```

## Monitoring & Maintenance

**View application logs:**
```bash
tail -f /var/www/serpoai/storage/logs/laravel.log
```

**View monitor service logs:**
```bash
sudo journalctl -u serpoai-monitor.service -f
```

**Restart monitor:**
```bash
sudo systemctl restart serpoai-monitor.service
```

**Clear caches:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```
