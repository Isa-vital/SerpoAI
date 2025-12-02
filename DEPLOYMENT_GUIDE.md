# SerpoAI Bot - New Features Deployment Guide

## 🎉 What's New

### Feature 1: Portfolio Tracking 💼
Users can now track their SERPO token holdings across multiple wallets.

**Commands:**
- `/portfolio` - View all tracked wallets and total holdings
- `/addwallet <address>` - Add a TON wallet to track
- `/addwallet <address> MyLabel` - Add wallet with custom label
- `/removewallet <address>` - Stop tracking a wallet

**Features:**
- Real-time balance syncing from TON blockchain
- USD value calculation
- Support for multiple wallets per user
- Automatic balance refresh (5-minute cache)
- Short address display for readability

### Feature 2: Token Event Alerts 📊
Automated monitoring of SERPO token activity with alerts sent to community channel.

**Monitored Events:**
- 🟢 Large buy transactions ($1000+)
- 🔴 Large sell transactions ($1000+)  
- 💧 Liquidity additions (10%+ change)
- ⚠️ Liquidity removals (10%+ change)
- 📈 Significant price changes (5%+)
- 🐋 Whale transfers (100k+ SERPO)
- 👥 Holder count changes (10+ holders)

**Alert Command:**
```bash
php artisan serpo:monitor --interval=60
```

---

## 📋 Deployment Steps

### 1. Local Testing (Current Setup)

You're already testing locally with ngrok. Test the new commands:

```
/portfolio
/addwallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw TestWallet
/portfolio
/removewallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw
```

### 2. Configure Environment Variables

Update production `.env` file with:

```bash
# Community Channel for Alerts
COMMUNITY_CHANNEL_ID="@serpocoinchannel"

# TON Blockchain API (get free key from https://tonapi.io)
API_KEY_TON=your_tonapi_key_here
TON_API_URL=https://tonapi.io/v2

# DexScreener API (optional, for enhanced data)
API_KEY_DEXSCREENER=
DEXSCREENER_API_URL=https://api.dexscreener.com/latest

# Update chain to TON
SERPO_CHAIN=ton
```

### 3. Deploy to Production Server

```bash
# On server: Fix nginx config first
ssh root@YOUR_SERVER_IP

# Remove duplicate fastcgi_read_timeout
sed -i '/fastcgi_read_timeout/d' /etc/nginx/nginx.conf
sed -i '/http {/a \    fastcgi_read_timeout 300;' /etc/nginx/nginx.conf

# Set PHP timeouts to 300 seconds
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini
sed -i 's/^;*request_terminate_timeout = .*/request_terminate_timeout = 300/' /etc/php/8.3/fpm/pool.d/www.conf

# Test and restart services
nginx -t
systemctl restart nginx php8.3-fpm

# Pull latest code
cd /var/www/SerpoAI
git pull origin main

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:clear
php artisan cache:clear

# Set webhook back to production
BOT_TOKEN=$(grep TELEGRAM_BOT_TOKEN .env | cut -d '=' -f2 | tr -d ' "')
curl -X POST "https://api.telegram.org/bot${BOT_TOKEN}/deleteWebhook?drop_pending_updates=true"
curl -X POST "https://api.telegram.org/bot${BOT_TOKEN}/setWebhook" -d "url=https://ai.serpocoin.io/api/telegram/webhook"
```

### 4. Start Token Event Monitoring

Run the monitoring service in background:

```bash
# Option 1: Using screen (recommended for testing)
screen -dmS serpo-monitor php artisan serpo:monitor --interval=60

# Option 2: Using nohup
nohup php artisan serpo:monitor --interval=60 > /var/www/SerpoAI/storage/logs/monitor.log 2>&1 &

# Option 3: Using systemd (recommended for production)
# Create /etc/systemd/system/serpo-monitor.service
```

**Systemd Service File** (for production):

```ini
[Unit]
Description=SerpoAI Token Event Monitor
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/SerpoAI
ExecStart=/usr/bin/php /var/www/SerpoAI/artisan serpo:monitor --interval=60
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
systemctl enable serpo-monitor
systemctl start serpo-monitor
systemctl status serpo-monitor
```

### 5. Verify Everything Works

Test commands in Telegram:
```
/start
/help
/portfolio
/addwallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw
/price
/sentiment
```

Check monitoring is running:
```bash
# Check process
ps aux | grep "serpo:monitor"

# Check logs
tail -f /var/www/SerpoAI/storage/logs/laravel.log
tail -f /var/www/SerpoAI/storage/logs/monitor.log
```

---

## 🔧 Configuration

### TON API Setup

1. Go to https://tonapi.io
2. Sign up for free account
3. Get API key
4. Add to `.env`: `API_KEY_TON=your_key_here`

### Channel Alerts Setup

The bot needs to be an admin in `@serpocoinchannel` to send alerts:

1. Add bot to channel as administrator
2. Grant "Post Messages" permission
3. Test with: `php artisan serpo:monitor --once`

---

## 📊 Database Tables

### `user_wallets`
- Stores wallet addresses tracked by users
- Caches balance and USD value
- Supports custom labels
- Unique constraint: (user_id, wallet_address)

### `token_events`
- Stores all detected token events
- Tracks transaction hashes to avoid duplicates
- Records price changes, liquidity changes, transfers
- Flags whether alert was sent

---

## 🐛 Troubleshooting

### Portfolio Commands Not Working

```bash
# Check if migrations ran
php artisan migrate:status

# Verify services config
php artisan config:show services.ton
php artisan config:show services.serpo
```

### Monitoring Not Sending Alerts

```bash
# Run once to see errors
php artisan serpo:monitor --once

# Check bot is channel admin
# Check COMMUNITY_CHANNEL_ID is correct (use @username or -100123456789 format)

# Test manual alert
php artisan tinker
>>> app(\App\Services\TelegramBotService::class)->sendMessage(config('services.telegram.community_channel_id'), 'Test alert');
```

### Wallet Balance Shows 0

```bash
# Check TON API key is set
echo $API_KEY_TON

# Test API manually
curl -H "Authorization: Bearer YOUR_KEY" https://tonapi.io/v2/jettons/EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw

# Check contract address is correct
php artisan tinker
>>> config('services.serpo.contract_address')
```

---

## 🚀 Performance Notes

- Portfolio syncing caches for 5 minutes per wallet
- Token monitoring runs every 60 seconds (configurable)
- Large trades: $1000+ USD value
- Large transfers: 100k+ SERPO
- Price alerts: 5%+ change
- Liquidity alerts: 10%+ change

---

## 📝 Next Steps

1. ✅ Test portfolio commands locally
2. ✅ Deploy to production
3. ✅ Start monitoring service
4. ⏳ Get TON API key for full functionality
5. ⏳ Monitor logs for any issues
6. ⏳ Adjust alert thresholds if needed

---

## 🎯 Future Enhancements

- Add portfolio export (CSV/PDF)
- Support for multiple tokens
- Historical portfolio value tracking
- Profit/loss calculations
- Tax reporting features
- More granular alert settings
- Webhook-based real-time monitoring (instead of polling)
