# SerpoAI - AI-Powered Multi-Market Trading Platform

**SerpoAI** is an AI-powered trading platform for the Serpocoin ecosystem, featuring a full web platform (CoinGecko-style), real-time market analysis across crypto, forex, and stocks, adaptive multi-market grid bots, trading signals, and custom alerts via Telegram and web.

## Features

- **Full Web Platform** - Modern dark-theme UI at ai.serpocoin.io — 12 sections, 25 pages, all 40+ features on web (CoinGecko-style)
- **Telegram Login** - Web authentication via Telegram Login Widget — same user identity as Telegram bot
- **Real-time Price Tracking** - Live SERPO token prices from DexScreener
- **Technical Analysis** - RSI, MACD, EMA, S/R, divergence indicators
- **Custom Alerts** - Price-based and universal alerts across 40+ commands
- **AI-Powered Insights** - Market analysis via OpenAI GPT-4o-mini, Google Gemini, and Groq
- **Multi-Market Grid Bot** - Adaptive grid trading on Binance, KuCoin, Bybit (crypto), OANDA, FXCM, MT5 (forex), Interactive Brokers, Alpaca (stocks)
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
- **Frontend**: Inertia.js 2.0 + React 19 (integrated with Laravel)
- **Styling**: Tailwind CSS 4.0 + Vite 7.0
- **Charts**: Lightweight Charts (TradingView), Recharts
- **Crypto APIs**: Binance, KuCoin, Bybit, DexScreener, CoinGecko
- **Forex APIs**: OANDA v20, FXCM REST, MetaTrader 5 Python
- **Stock APIs**: Interactive Brokers, Alpaca v2
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

### Phase 3: Web Platform Redesign (Weeks 1–3)
- [ ] Inertia.js + React setup, Telegram Login integration
- [ ] 12 web sections: Dashboard, Price Tracking, Portfolio, Alerts, AI Analysis, Charts, Whale Tracker, Token Verify, Signals, Research, Settings, Admin
- [ ] ~30 API controller endpoints backed by 42 existing services

### Phase 4: Smart Contracts (Weeks 4–5)
- [ ] Subscription smart contract (Tact on TON)
- [ ] Fee distribution contract
- [ ] Vault contract
- [ ] Collateral vault contract
- [ ] Testnet deployment + testing

### Phase 5: Backend + Frontend New Features (Weeks 6–7)
- [ ] Backend services, DB migrations, API endpoints
- [ ] Telegram bot commands (/subscribe, /referral, /collateral, /grid)
- [ ] TON Connect wallet integration
- [ ] Subscription & referral dashboards on web

### Phase 6: Crypto Grid Bot (Weeks 8–9)
- [ ] Crypto exchange connectors (Binance, KuCoin, Bybit)
- [ ] Adaptive Grid Engine (ATR-based spacing)
- [ ] AI Signal Layer (trend/volatility detection)

### Phase 7: Forex & Stock Connectors (Weeks 10–11)
- [ ] Forex connectors (OANDA, FXCM, MetaTrader 5)
- [ ] Stock connectors (Interactive Brokers, Alpaca)
- [ ] Multi-market adapter layer

### Phase 8: Grid Bot Completion (Week 12)
- [ ] Risk management & backtesting engine (all 3 markets)
- [ ] Grid bot monitoring dashboard

### Phase 9: Testing + Launch (Weeks 13–14)
- [ ] End-to-end testing across all markets
- [ ] Mainnet deployment
- [ ] Grid bot production launch

**Weeks 15–16** are reserved as buffer.

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

**4.0.0** - Web Platform, Smart Contracts & Multi-Market Grid Bot System

---

Built with ❤️ for the Serpocoin Community
