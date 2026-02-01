# 🚀 Production Deployment Checklist

## Pre-Deployment Checklist

### 1. Environment Configuration ✅
- [x] APP_KEY generated
- [ ] APP_URL set to production domain
- [ ] APP_DEBUG=false
- [ ] LOG_LEVEL=error
- [ ] Database credentials configured
- [ ] All API keys added (see below)

### 2. Required API Keys

#### Essential APIs (Bot won't work without these):
```bash
# Telegram Bot
TELEGRAM_BOT_TOKEN=your_production_bot_token
TELEGRAM_WEBHOOK_URL=https://ai.serpocoin.io/api/telegram/webhook

# OpenAI (for AI responses)
OPENAI_API_KEY=your_openai_key
OPENAI_MODEL=gpt-4o-mini

# TON Blockchain (for monitoring)
API_KEY_TON=your_tonapi_key
TON_API_URL=https://tonapi.io
```

#### Optional APIs (enhance features):
```bash
# Solscan (for Solana token verification)
SOLSCAN_API_KEY=your_solscan_jwt_token

# Etherscan (for Ethereum verification)
ETHERSCAN_API_KEY=your_etherscan_key

# BscScan (for BSC verification)
BSCSCAN_API_KEY=your_bscscan_key

# BaseScan (for Base verification)
BASESCAN_API_KEY=your_basescan_key

# Alpha Vantage (for stock data, free tier)
ALPHA_VANTAGE_API_KEY=your_alphavantage_key

# Binance (for enhanced crypto data)
BINANCE_API_KEY=your_binance_key
BINANCE_API_SECRET=your_binance_secret
```

### 3. Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE serpoai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'serpoai'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON serpoai.* TO 'serpoai'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Update .env
DB_DATABASE=serpoai
DB_USERNAME=serpoai
DB_PASSWORD=secure_password_here
```

### 4. Server Requirements
- PHP 8.2+
- Composer 2.x
- Node.js 18+ & NPM
- MySQL 8.0+
- Nginx
- Git
- Certbot (for SSL)

---

## Deployment Steps

### Step 1: Server Setup (One-time)

```bash
# SSH into server
ssh your_user@your_server_ip

# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring \
    php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-cli php8.2-intl \
    mysql-server nginx git composer nodejs npm certbot python3-certbot-nginx

# Create deployment user
sudo adduser deploy
sudo usermod -aG www-data deploy

# Set up project directory
sudo mkdir -p /var/www/serpoai
sudo chown -R deploy:www-data /var/www/serpoai
```

### Step 2: Deploy Code

```bash
# Switch to deploy user
sudo su - deploy

# Clone repository
cd /var/www
git clone https://github.com/Isa-vital/SerpoAI.git serpoai
cd serpoai

# Run deployment script
chmod +x deploy.sh
./deploy.sh
```

### Step 3: Configure Environment

```bash
# Copy production environment
cp .env.production .env

# Edit with your API keys and database credentials
nano .env

# IMPORTANT: Update these values:
# - APP_KEY (already set)
# - DB_PASSWORD
# - TELEGRAM_BOT_TOKEN
# - OPENAI_API_KEY
# - API_KEY_TON
# - All optional API keys you have
```

### Step 4: Run Migrations

```bash
php artisan migrate --force
php artisan db:seed --force  # Optional: seed initial data
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

### Step 5: Configure Nginx

```bash
# Copy Nginx configuration
sudo cp deployment/nginx-serpoai.conf /etc/nginx/sites-available/serpoai
sudo ln -s /etc/nginx/sites-available/serpoai /etc/nginx/sites-enabled/

# Edit domain
sudo nano /etc/nginx/sites-available/serpoai
# Change: server_name ai.serpocoin.io;

# Test Nginx config
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### Step 6: Set Up SSL Certificate

```bash
# Get SSL certificate
sudo certbot --nginx -d ai.serpocoin.io

# Auto-renewal is set up automatically
# Test renewal:
sudo certbot renew --dry-run
```

### Step 7: Set Up Monitoring Service

```bash
# Copy service file
sudo cp deployment/serpoai-monitor.service /etc/systemd/system/

# Enable and start
sudo systemctl daemon-reload
sudo systemctl enable serpoai-monitor.service
sudo systemctl start serpoai-monitor.service

# Check status
sudo systemctl status serpoai-monitor.service
```

### Step 8: Set Up Telegram Webhook

```bash
# Set webhook (replace with your domain)
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://ai.serpocoin.io/api/telegram/webhook"}'

# Verify webhook
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

---

## Post-Deployment Checks

### 1. Test Bot Functionality
```bash
# Send these commands to your bot on Telegram:
/start        # Should show welcome message
/help         # Should list all commands
/about        # Should show bot version
/signals BTC  # Should return market signals
/verify 0x... # Should verify a token
```

### 2. Check Monitoring
```bash
# Check if monitor is running
sudo systemctl status serpoai-monitor.service

# View monitor logs
tail -f /var/www/serpoai/storage/logs/laravel.log | grep "token event"

# Test manual run
cd /var/www/serpoai
php artisan serpo:monitor --once --force
```

### 3. Check Application Logs
```bash
# Application errors
tail -f /var/www/serpoai/storage/logs/laravel.log

# Nginx access
sudo tail -f /var/log/nginx/serpoai-access.log

# Nginx errors
sudo tail -f /var/log/nginx/serpoai-error.log

# PHP-FPM errors
sudo tail -f /var/log/php8.2-fpm.log
```

### 4. Performance Checks
```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Check Nginx status
sudo systemctl status nginx

# Check MySQL status
sudo systemctl status mysql

# Check disk space
df -h

# Check memory usage
free -h
```

---

## Updating Production (Future Deployments)

```bash
# SSH into server
ssh your_user@your_server_ip

# Switch to deploy user
sudo su - deploy

# Navigate to project
cd /var/www/serpoai

# Pull latest changes
git pull origin main

# Run deployment script
./deploy.sh

# Restart services
sudo systemctl restart serpoai-monitor.service
```

---

## Rollback Procedure (If Something Goes Wrong)

```bash
# View recent commits
git log --oneline -10

# Rollback to previous commit
git reset --hard <commit_hash>

# Re-run deployment
./deploy.sh

# Restart services
sudo systemctl restart serpoai-monitor.service
```

---

## Monitoring & Maintenance

### Daily Checks
- [ ] Monitor logs for errors: `tail -f /var/www/serpoai/storage/logs/laravel.log`
- [ ] Check bot responsiveness: Send `/about` command
- [ ] Verify alerts are being sent to channel

### Weekly Checks
- [ ] Check disk space: `df -h`
- [ ] Review error logs for patterns
- [ ] Check database size: `du -sh /var/lib/mysql/serpoai`
- [ ] Verify SSL certificate expiry: `sudo certbot certificates`

### Monthly Checks
- [ ] Update system packages: `sudo apt update && sudo apt upgrade`
- [ ] Review and clean old logs: `find storage/logs -name "*.log" -mtime +30 -delete`
- [ ] Database optimization: `php artisan optimize:clear`
- [ ] Review API usage and costs

---

## Troubleshooting

### Bot Not Responding
```bash
# Check webhook
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"

# Check application logs
tail -100 /var/www/serpoai/storage/logs/laravel.log

# Check Nginx logs
sudo tail -100 /var/log/nginx/serpoai-error.log

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Monitor Not Sending Alerts
```bash
# Check service status
sudo systemctl status serpoai-monitor.service

# View service logs
sudo journalctl -u serpoai-monitor.service -f

# Test manually
cd /var/www/serpoai
php artisan serpo:monitor --once --force

# Restart service
sudo systemctl restart serpoai-monitor.service
```

### Database Connection Errors
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u serpoai -p serpoai

# Check Laravel config
php artisan config:clear
php artisan config:cache
```

### High Memory Usage
```bash
# Check processes
top

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Clear cache
php artisan cache:clear
php artisan view:clear
```

---

## Security Notes

1. **Never commit .env to git** - Use `.env.production` as template only
2. **Use strong database password** - Generate with: `openssl rand -base64 32`
3. **Keep API keys secure** - Never expose in logs or error messages
4. **Regular backups** - Set up daily database backups
5. **Monitor logs** - Watch for unauthorized access attempts
6. **Update dependencies** - Run `composer update` and `npm update` regularly
7. **SSL certificate** - Ensure auto-renewal is working

---

## Backup Strategy

### Database Backup (Automated)
```bash
# Create backup script
sudo nano /usr/local/bin/backup-serpoai-db.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/serpoai"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR
mysqldump -u serpoai -p'your_password' serpoai | gzip > $BACKUP_DIR/serpoai_$DATE.sql.gz
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-serpoai-db.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-serpoai-db.sh
```

---

## Support Contacts

- **Technical Issues**: Check GitHub Issues
- **API Questions**: Refer to API documentation
- **Server Issues**: Contact hosting provider

---

## Version History

- **v1.2.1** - Current production version
  - Multi-market signals (crypto/forex/stocks)
  - Solana blockchain support
  - Confidence scoring 1-5
  - Token verification (5 chains)
  - Monitoring with cooldown bypass

---

**Last Updated**: January 31, 2026
