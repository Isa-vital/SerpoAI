# 🚀 SerpoAI Quick Start

Get your Telegram bot running in **10 minutes**!

## 1️⃣ Create Telegram Bot (2 min)

1. Open Telegram → Search **@BotFather**
2. Send `/newbot`
3. Name: `SerpoAI`
4. Username: `SerpoAI_bot`
5. **Copy the token** (looks like: `1234567890:ABC...`)

## 2️⃣ Setup Database (2 min)

```bash
# Open MySQL
mysql -u root -p

# Create database
CREATE DATABASE serpoai_db;
exit
```

## 3️⃣ Configure Project (3 min)

```bash
# Navigate to project
cd c:\xampp\htdocs\SerpoAI

# Copy environment file
copy .env.example .env

# Generate key
php artisan key:generate
```

**Edit `.env`** file:
```env
DB_DATABASE=serpoai_db
DB_USERNAME=root
DB_PASSWORD=

TELEGRAM_BOT_TOKEN=paste_your_token_here
SERPO_CONTRACT_ADDRESS=your_contract_address
```

## 4️⃣ Run Migrations (1 min)

```bash
php artisan migrate
```

## 5️⃣ Start Server (1 min)

```bash
php artisan serve
```

Server running at: `http://127.0.0.1:8000`

## 6️⃣ Set Webhook (1 min)

**For local testing** (using ngrok):
```bash
# Install ngrok: https://ngrok.com
ngrok http 8000

# Copy the HTTPS URL (e.g., https://abc123.ngrok.io)
# Set webhook:
curl -X POST "https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook" \
  -d "url=https://abc123.ngrok.io/api/telegram/webhook"
```

**For production**:
```bash
curl -X POST "https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook" \
  -d "url=https://yourdomain.com/api/telegram/webhook"
```

## 7️⃣ Test Bot! ✅

1. Open Telegram
2. Search for your bot: `@SerpoAI_bot`
3. Send: `/start`
4. You should see welcome message! 🎉

---

## Available Commands

```
/start   - Welcome message
/help    - Show commands
/price   - Get SERPO price (needs contract address)
/signals - Trading signals
/about   - About SerpoAI
```

---

## Next Steps

1. **Add SERPO contract address** to `.env`
2. **Setup n8n workflows** (see `n8n/README.md`)
3. **Deploy to production** (see `SETUP_GUIDE.md`)
4. **Configure OpenAI** for AI features

---

## Troubleshooting

### Bot not responding?

**Check webhook status**:
```bash
curl "https://api.telegram.org/bot<YOUR_TOKEN>/getWebhookInfo"
```

**Check logs**:
```bash
tail -f storage/logs/laravel.log
```

### Database error?

- Make sure MySQL is running
- Verify `.env` credentials
- Check if database exists:
  ```bash
  mysql -u root -p -e "SHOW DATABASES;"
  ```

### Need help?

- Read full guide: `SETUP_GUIDE.md`
- Check README: `README.md`
- Review code: `app/Services/CommandHandler.php`

---

## Development Tips

**View routes**:
```bash
php artisan route:list
```

**Clear cache**:
```bash
php artisan cache:clear
php artisan config:clear
```

**Check database**:
```bash
mysql -u root -p serpoai_db
SELECT * FROM users;
```

---

**Happy Building! 🐍**
