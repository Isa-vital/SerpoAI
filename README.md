# SerpoAI - Telegram Trading Bot

🐍 **SerpoAI** is an AI-powered trading assistant for the Serpocoin ecosystem, providing real-time market analysis, trading signals, and custom alerts via Telegram.

## Features

- 📊 **Real-time Price Tracking** - Live SERPO token prices from DexScreener
- 📈 **Technical Analysis** - RSI, MACD, EMA indicators
- 🔔 **Custom Alerts** - Set price-based alerts
- 🤖 **AI-Powered Insights** - Market explanations via OpenAI
- 💬 **Telegram Interface** - Easy-to-use bot commands
- 🔄 **n8n Automation** - Scheduled workflows for data collection

## Tech Stack

- **Backend**: PHP Laravel 10+
- **Database**: MySQL 5.7+
- **Automation**: n8n workflows
- **AI**: OpenAI GPT-4
- **APIs**: DexScreener, CoinGecko
- **Messaging**: Telegram Bot API

## Installation

### Prerequisites

- PHP 8.1+
- Composer
- MySQL 5.7+
- XAMPP (for local development)

### Quick Setup

1. **Navigate to project directory**:
   ```bash
   cd c:\xampp\htdocs\SerpoAI
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Configure environment**:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

4. **Update `.env` file**:
   ```env
   DB_DATABASE=serpoai_db
   DB_USERNAME=root
   DB_PASSWORD=

   TELEGRAM_BOT_TOKEN=your_telegram_bot_token
   SERPO_CONTRACT_ADDRESS=your_serpo_contract_address
   OPENAI_API_KEY=your_openai_api_key
   ```

5. **Create database**:
   ```bash
   mysql -u root -p
   CREATE DATABASE serpoai_db;
   exit
   ```

6. **Run migrations**:
   ```bash
   php artisan migrate
   ```

7. **Start server**:
   ```bash
   php artisan serve
   ```

## Telegram Bot Commands

| Command | Description |
|---------|-------------|
| `/start` | Welcome message and intro |
| `/help` | List all available commands |
| `/price` | Get current SERPO price |
| `/chart` | View price chart (coming soon) |
| `/signals` | Get trading signals |
| `/setalert [price]` | Create price alert |
| `/myalerts` | View your active alerts |
| `/settings` | Bot preferences |
| `/about` | About SerpoAI |

## API Endpoints

- `POST /api/telegram/webhook` - Telegram webhook receiver
- `GET /api/telegram/test` - Test bot connection
- `GET /api/health` - Health check

## Setting up Telegram Bot

1. Talk to [@BotFather](https://t.me/botfather) on Telegram
2. Create new bot: `/newbot`
3. Choose name and username
4. Copy the bot token
5. Update `.env`: `TELEGRAM_BOT_TOKEN=your_token`

### Configure Webhook

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook" \
  -d "url=https://yourdomain.com/api/telegram/webhook"
```

## Database Schema

### Tables

- **users** - Telegram user data
- **alerts** - Price/indicator alerts
- **market_data** - Historical price & indicators
- **signals** - Trading signals
- **bot_logs** - Interaction logs

## Project Structure

```
app/
├── Http/Controllers/
│   └── TelegramWebhookController.php  # Webhook handler
├── Models/
│   ├── User.php                       # Telegram users
│   ├── Alert.php                      # User alerts
│   ├── MarketData.php                 # Market data
│   ├── Signal.php                     # Trading signals
│   └── BotLog.php                     # Activity logs
└── Services/
    ├── TelegramBotService.php         # Telegram API wrapper
    ├── MarketDataService.php          # Market data fetching
    └── CommandHandler.php             # Bot command logic
```

## Development Roadmap

### Phase 1: Foundation ✅
- [x] Laravel setup
- [x] Database migrations
- [x] Telegram bot integration
- [x] Basic commands

### Phase 2: Market Data (Current)
- [x] DexScreener integration
- [x] Price tracking
- [ ] Technical indicators
- [ ] n8n workflows

### Phase 3: Advanced Features
- [ ] OpenAI integration
- [ ] Sentiment analysis
- [ ] Portfolio tracking
- [ ] Chart generation

## n8n Workflows

Create workflows in n8n for:

1. **Price Fetcher** - Every 5 minutes
2. **Signal Generator** - Calculate indicators
3. **Alert Checker** - Check alert conditions
4. **Notification Sender** - Push alerts to users

## Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature/name`
3. Commit changes: `git commit -m 'Add feature'`
4. Push to branch: `git push origin feature/name`
5. Open Pull Request

## License

MIT License - See LICENSE file

## Support

- **Website**: https://serpocoin.io
- **Telegram**: https://t.me/serpocoin
- **Documentation**: Coming soon

## Version

**1.0.0-beta** - Initial Release

---

Built with ❤️ for the Serpocoin Community
