# SerpoAI - AI-Powered Crypto Trading Platform

**SerpoAI** is an AI-powered trading platform for the Serpocoin ecosystem, providing real-time crypto market analysis, adaptive grid bots, trading signals, and custom alerts via Telegram, website, and mobile app.

## Features

- **Real-time Price Tracking** - Live SERPO token prices from DexScreener
- **Technical Analysis** - RSI, MACD, EMA, S/R, divergence indicators
- **Custom Alerts** - Price-based and universal alerts across 40+ commands
- **AI-Powered Insights** - Market analysis via OpenAI GPT-4o-mini, Google Gemini, and Groq
- **Crypto Grid Bot** - Adaptive grid trading on Binance, KuCoin, and Bybit
- **AI Signal Layer** - Trend detection, volatility analysis, grid activation/pause
- **Collateral Vault** - SERPO deposits to guarantee performance fees on CEX trades
- **DEX Trading** - On-chain trades on STON.fi/DeDust with automatic fee deduction
- **Subscription System** - Monthly SERPO-based subscription via smart contract
- **Referral System** - 50% subscription / 3% trading profit sharing
- **Whale Alert Tracking** - Real-time blockchain monitoring via TonAPI
- **Token Verification** - Across 20+ chains (Degen Scanner)
- **Telegram Interface** - Easy-to-use bot with 40+ commands

## Tech Stack

- **Backend**: PHP Laravel 12 (PHP 8.2+)
- **Database**: PostgreSQL (primary) / MySQL 5.7+
- **Cache**: Redis
- **AI**: OpenAI GPT-4o-mini, Google Gemini, Groq
- **Grid Bot**: Python 3.12+ (pandas, NumPy, TA-Lib, PyTorch/TensorFlow)
- **Backtesting**: Backtrader, vectorbt
- **Real-Time**: WebSocket streams (Python asyncio, FastAPI)
- **Smart Contracts**: Tact (TON blockchain)
- **Blockchain**: TonWeb, ton-core, TonAPI v2
- **Wallet**: TON Connect 2.0 (Tonkeeper, MyTonWallet, OpenMask)
- **Frontend**: React / Next.js
- **Crypto APIs**: Binance, KuCoin, Bybit, DexScreener, CoinGecko
- **DEX**: STON.fi, DeDust
- **Messaging**: Telegram Bot API
- **Deployment**: Docker, Nginx + PHP-FPM

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

### Phase 1: Foundation
- [x] Laravel setup
- [x] Database migrations
- [x] Telegram bot integration
- [x] Basic commands (40+)

### Phase 2: Market Data & AI
- [x] DexScreener integration
- [x] Price tracking
- [x] Technical indicators (RSI, MACD, EMA, S/R, divergence)
- [x] OpenAI / Gemini / Groq integration
- [x] Whale alert tracking
- [x] Token verification (20+ chains)

### Phase 3: Smart Contracts (Weeks 1–2)
- [ ] Subscription smart contract (Tact on TON)
- [ ] Fee distribution contract
- [ ] Vault contract
- [ ] Collateral vault contract
- [ ] Testnet deployment + testing

### Phase 4: Backend + Frontend (Weeks 3–4)
- [ ] Backend services, DB migrations, API endpoints
- [ ] Telegram bot commands (/subscribe, /referral, /collateral, /grid)
- [ ] TON Connect wallet integration
- [ ] Subscription & referral dashboards

### Phase 5: Crypto Grid Bot (Weeks 5–7)
- [ ] Exchange connectors (Binance, KuCoin, Bybit)
- [ ] Adaptive Grid Engine (ATR-based spacing)
- [ ] AI Signal Layer (trend/volatility detection)
- [ ] Risk management & backtesting engine
- [ ] Grid bot monitoring dashboard

### Phase 6: Testing + Launch (Weeks 8–9)
- [ ] End-to-end testing
- [ ] Mainnet deployment
- [ ] Grid bot production launch

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
- **Telegram**: https://t.me/serpocoinchannel
- **Documentation**: Coming soon

## Version

**3.0.0** - Smart Contract & Crypto Grid Bot System

---

Built with ❤️ for the Serpocoin Community
