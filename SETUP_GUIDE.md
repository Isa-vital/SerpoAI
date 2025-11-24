# SerpoAI Setup Guide

Complete step-by-step guide to set up and deploy SerpoAI Telegram bot.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Create Telegram Bot](#create-telegram-bot)
3. [Configure Environment](#configure-environment)
4. [Database Setup](#database-setup)
5. [Deploy Application](#deploy-application)
6. [Setup n8n Workflows](#setup-n8n-workflows)
7. [Testing](#testing)
8. [Production Deployment](#production-deployment)

---

## Prerequisites

### Required Software
- **PHP 8.1+** with extensions:
  - `php-mysql`
  - `php-mbstring`
  - `php-xml`
  - `php-curl`
- **Composer** (Dependency Manager)
- **MySQL 5.7+** or **MariaDB 10.3+**
- **XAMPP** (for local dev) or **VPS** (for production)
- **n8n** (for automation workflows)

### Required API Keys
- Telegram Bot Token (from @BotFather)
- SERPO Contract Address
- OpenAI API Key (optional, for AI features)
- CoinGecko API Key (optional)

---

## 1. Create Telegram Bot

### Step 1: Open Telegram
1. Search for **@BotFather** on Telegram
2. Start a conversation: `/start`

### Step 2: Create New Bot
```
/newbot
```

### Step 3: Set Bot Details
- **Bot Name**: `SerpoAI` (display name)
- **Bot Username**: `SerpoAI_bot` (must end with 'bot')

### Step 4: Copy Token
BotFather will provide a token like:
```
1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
```
**Save this token securely!**

### Step 5: Configure Bot (Optional)
```
/setdescription - Set bot description
/setabouttext - Set "About" text
/setuserpic - Upload bot profile picture
/setcommands - Set command list
```

**Recommended Commands**:
```
start - Welcome message
help - Show all commands
price - Get current SERPO price
signals - Trading signals
setalert - Set price alert
myalerts - View your alerts
settings - Bot settings
about - About SerpoAI
```

---

## 2. Configure Environment

### Step 1: Navigate to Project
```bash
cd c:\xampp\htdocs\SerpoAI
```

### Step 2: Copy Environment File
```bash
copy .env.example .env
```

### Step 3: Edit `.env` File
Open `.env` in text editor and update:

```env
APP_NAME=SerpoAI
APP_ENV=production
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=serpoai_db
DB_USERNAME=root
DB_PASSWORD=your_secure_password

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
TELEGRAM_BOT_USERNAME=SerpoAI_bot
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/api/telegram/webhook

# Serpocoin
SERPO_CONTRACT_ADDRESS=0x... # Your SERPO token contract
SERPO_CHAIN=ethereum
SERPO_DEX_PAIR_ADDRESS=0x...

# OpenAI (Optional)
OPENAI_API_KEY=sk-... # If using AI features
OPENAI_MODEL=gpt-4

# APIs
DEXSCREENER_API_URL=https://api.dexscreener.com/latest
COINGECKO_API_URL=https://api.coingecko.com/api/v3
COINGECKO_API_KEY= # Optional

# n8n
N8N_WEBHOOK_URL=http://localhost:5678/webhook
N8N_API_KEY=your_n8n_api_key
```

### Step 4: Generate App Key
```bash
php artisan key:generate
```

---

## 3. Database Setup

### Option A: Using MySQL Command Line

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE serpoai_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user (optional)
CREATE USER 'serpoai_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON serpoai_db.* TO 'serpoai_user'@'localhost';
FLUSH PRIVILEGES;

# Exit
exit
```

### Option B: Using phpMyAdmin

1. Open `http://localhost/phpmyadmin`
2. Click "New" to create database
3. Name: `serpoai_db`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

### Run Migrations

```bash
cd c:\xampp\htdocs\SerpoAI
php artisan migrate
```

Expected output:
```
Migration table created successfully.
Migrating: 2025_11_22_030337_create_users_table
Migrated: 2025_11_22_030337_create_users_table
Migrating: 2025_11_22_030352_create_alerts_table
Migrated: 2025_11_22_030352_create_alerts_table
...
```

---

## 4. Deploy Application

### Local Development

```bash
# Start Laravel server
php artisan serve

# Server running at:
# http://127.0.0.1:8000
```

### Production (VPS)

1. **Upload files** to server (FTP/SFTP)
2. **Configure web server** (Apache/Nginx)
3. **Set permissions**:
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```
4. **Configure domain** pointing to `/public` directory

---

## 5. Setup Telegram Webhook

### Set Webhook URL

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -d "url=https://yourdomain.com/api/telegram/webhook"
```

**Response**:
```json
{
  "ok": true,
  "result": true,
  "description": "Webhook was set"
}
```

### Verify Webhook

```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo"
```

### Test Bot

1. Open Telegram
2. Search for your bot: `@SerpoAI_bot`
3. Send: `/start`
4. You should receive welcome message!

---

## 6. Setup n8n Workflows

### Install n8n

```bash
npm install -g n8n
```

### Start n8n

```bash
n8n start
```

Access at: `http://localhost:5678`

### Import Workflows

1. Click **"Workflows"** → **"Import from File"**
2. Import `n8n/workflows/price-fetcher.json`
3. Import `n8n/workflows/alert-checker.json`

### Configure MySQL Connection

1. Go to **Credentials** → **New Credential**
2. Type: **MySQL**
3. Settings:
   - Host: `127.0.0.1`
   - Database: `serpoai_db`
   - User: `root`
   - Password: your_password
4. Save as: **"MySQL SerpoAI"**

### Activate Workflows

1. Open **"Price Fetcher"** workflow
2. Click **"Active"** toggle (top right)
3. Repeat for **"Alert Checker"** workflow

---

## 7. Testing

### Test API Endpoints

```bash
# Health check
curl http://localhost:8000/api/health

# Bot info
curl http://localhost:8000/api/telegram/test
```

### Test Bot Commands

In Telegram, send:
```
/start
/help
/price
/setalert 0.00001
/myalerts
/about
```

### Check Database

```bash
mysql -u root -p serpoai_db

SELECT * FROM users;
SELECT * FROM market_data ORDER BY recorded_at DESC LIMIT 10;
SELECT * FROM alerts WHERE is_active = 1;
```

### Check n8n Logs

1. Open n8n: `http://localhost:5678`
2. Click **"Executions"** (left sidebar)
3. View workflow execution history

---

## 8. Production Deployment

### Security Checklist

- [ ] Change `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Use strong database password
- [ ] Enable SSL/HTTPS
- [ ] Restrict database access
- [ ] Set proper file permissions
- [ ] Enable rate limiting
- [ ] Backup database regularly

### Performance Optimization

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### Monitoring

1. **Application Logs**: `storage/logs/laravel.log`
2. **n8n Logs**: n8n dashboard → Executions
3. **Database Size**: Monitor `market_data` table growth
4. **API Rate Limits**: Track API call frequency

### Backup Strategy

```bash
# Database backup
mysqldump -u root -p serpoai_db > backup_$(date +%Y%m%d).sql

# Application backup
tar -czf serpoai_backup_$(date +%Y%m%d).tar.gz /path/to/SerpoAI
```

---

## Troubleshooting

### Bot Not Responding

1. **Check webhook**:
   ```bash
   curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
   ```

2. **Check Laravel logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Test webhook manually**:
   ```bash
   curl -X POST http://yourdomain.com/api/telegram/webhook \
     -H "Content-Type: application/json" \
     -d '{"message":{"text":"/start"}}'
   ```

### Database Connection Failed

- Verify MySQL is running
- Check `.env` credentials
- Test connection:
  ```bash
  php artisan tinker
  DB::connection()->getPdo();
  ```

### n8n Workflows Not Running

- Check if workflows are **Active**
- Verify MySQL credentials in n8n
- Check schedule trigger settings
- View execution logs in n8n

---

## Next Steps

1. **Add SERPO Contract Address** to `.env`
2. **Configure OpenAI** for AI features
3. **Set up monitoring** and alerts
4. **Create backup schedule**
5. **Plan feature releases**

## Support

- **Documentation**: See `README.md`
- **GitHub Issues**: Create issue for bugs
- **Community**: Join Serpocoin Telegram

---

**Version**: 1.0.0
**Last Updated**: November 22, 2025
