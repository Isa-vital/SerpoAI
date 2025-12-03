# SerpoAI v1.1.0 - New Features Documentation

## 🎉 What's New in v1.1.0

SerpoAI has been massively upgraded from a basic alert bot to a **full-featured AI trading assistant** with 20+ new commands, advanced market analytics, and premium features.

---

## 📊 Core Analysis Commands

### `/scan` - Market Deep Scan
**Full market analysis in one command**

Returns:
- **Market Overview**: Total pairs, gainers/losers count, sentiment, average change
- **Top Gainers**: Top 10 coins with highest 24h gains
- **Top Losers**: Top 10 coins with biggest 24h losses  
- **Volume Leaders**: Highest volume coins
- **Volatility Alerts**: Most volatile coins
- **Trend Analysis**: Market-wide trend distribution

**Usage:**
```
/scan
```

**Example Output:**
```
📊 MARKET DEEP SCAN

🌐 Market Overview
Total Pairs: 1,247
Gainers: 🟢 645 | Losers: 🔴 602
Sentiment: 🐂 Bullish
Avg Change: +2.3%
24h Volume: 45.2B USDT

🚀 Top Gainers
1. BTCUSDT: +12.5% | Vol: 8.2B
2. ETHUSDT: +8.3% | Vol: 4.5B
...
```

---

### `/analyze [pair]` - Pair Analytics
**Deep dive into any trading pair**

Analyzes:
- Price action (current, 24h high/low, change %)
- Volume analysis with volume change
- Trend bias (Bullish/Bearish/Neutral) with strength
- RSI levels across 1H, 4H, 1D timeframes
- Moving averages (MA20, MA50, EMA20)
- Support & resistance levels
- Volatility (ATR %)
- Risk assessment

**Usage:**
```
/analyze BTCUSDT
/analyze ETHUSDT
/analyze BNB/USDT
```

**Example Output:**
```
📊 PAIR ANALYTICS: BTCUSDT

💰 Price Action
Current: $42,150.25
24h Change: 🟢 +5.2%
24h High: $42,800
24h Low: $39,950

📈 Trend Analysis
Bias: 🐂 Bullish
Strength: Strong
MA20: $41,200 | MA50: $40,100

📊 RSI Levels
1H: 65 | 4H: 58 | 1D: 52
Signal: Bullish Momentum

🎯 Key Levels
Nearest Support: $41,000
Nearest Resistance: $43,500

⚠️ Risk Assessment
Mid-Range - Neutral Zone
Volatility (ATR): 2.8%
```

---

### `/radar` - Market Radar
**Quick snapshot of top movers**

Shows:
- Top 5 gainers with price and volume
- Top 5 losers with price and volume
- Quick market sentiment overview

**Usage:**
```
/radar
```

---

## 🔔 Alert Management

### `/alerts` - Subscription Management
**Control which trading alerts you receive**

Alert Types:
- `buy` - Large buy transactions
- `whale` - Whale movements
- `price` - Price threshold alerts
- `liquidity` - Liquidity changes

**Commands:**
```
/alerts                    # Show current status
/alerts on                 # Enable all alerts
/alerts off                # Disable all alerts
/alerts status             # Check subscription status
/alerts buy on             # Subscribe to buy alerts
/alerts whale on           # Subscribe to whale alerts
/alerts price on           # Subscribe to price alerts
/alerts liquidity on       # Subscribe to liquidity alerts
/alerts buy off            # Unsubscribe from buy alerts
```

---

## 📰 News & Calendar

### `/news` - Crypto News & Listings
**Latest market news and exchange listings**

Displays:
- Top crypto headlines
- Recent exchange listings (Binance, Coinbase, etc.)
- Token categories (DeFi, AI, Meme, Gaming)
- Risk assessments

**Usage:**
```
/news
```

---

### `/calendar` - Economic Events Calendar
**Important economic events affecting crypto markets**

Shows:
- Fed interest rate decisions
- CPI & inflation reports
- Unemployment claims
- Non-farm payrolls
- High-impact macro events

**Usage:**
```
/calendar
```

**Example Output:**
```
📅 ECONOMIC CALENDAR

⚠️ High Impact Events This Week

🗓️ Wednesday, Dec 4
• 🇺🇸 Fed Interest Rate Decision (2:00 PM EST)
  Impact: Very High | Watch for volatility

🗓️ Friday, Dec 6
• 🇺🇸 Non-Farm Payrolls (8:30 AM EST)
  Impact: Very High | Major crypto volatility expected
```

---

## 🤖 AI & Learning

### `/learn` - Learning Center
**Educational content for traders**

Topics:
1. **Trading Basics** - Orders, charts, candlesticks, S&R
2. **Technical Indicators** - RSI, MACD, Moving Averages, Bollinger Bands
3. **Futures Trading** - Leverage, margin, funding rates, positions
4. **Risk Management** - Position sizing, stop-loss, diversification
5. **On-Chain Analysis** - Whale tracking, token metrics, exchange flows

**Usage:**
```
/learn              # Show all topics
/learn 1            # Read Trading Basics
/learn 3            # Read Futures Trading
```

---

### `/glossary [term]` - Crypto Dictionary
**Explains trading and crypto terms**

Available Terms:
- `fud` - Fear, Uncertainty, and Doubt
- `fomo` - Fear Of Missing Out
- `rsi` - Relative Strength Index
- `oi` - Open Interest
- `funding` - Funding Rate
- `liquidation` - Forced position closure
- `slippage` - Price execution difference
- `whale` - Large holder
- `degen` - High-risk trader
- `ath` - All-Time High

**Usage:**
```
/glossary           # Show all terms
/glossary fomo      # Explain FOMO
/glossary rsi       # Explain RSI
```

---

## 👤 User Profile & Premium

### `/profile` - Your Trading Dashboard
**View your profile and usage statistics**

Shows:
- Risk level & trading style
- Favorite pairs & watchlist
- Subscription tier (Free/Basic/Pro/VIP)
- Daily scans used/remaining
- Active alerts count/limit
- Recent activity history

**Usage:**
```
/profile
```

**Example Output:**
```
👤 YOUR TRADING PROFILE

🎯 Trading Preferences
Risk Level: Moderate
Style: Day Trader

⭐ Subscription Status
Tier: FREE
Daily Scans: 3/10
Active Alerts: 2/5

⭐ Favorite Pairs
BTCUSDT, ETHUSDT, BNBUSDT

📊 Recent Activity
• market_scan: Market (2 hours ago)
• pair_analysis: BTCUSDT (5 hours ago)

💡 Type /premium to upgrade your plan!
```

---

### `/premium` - Upgrade Your Plan
**View premium tiers and features**

#### 🆓 FREE Tier
- 10 daily scans
- 5 active alerts
- Basic market scans
- Price alerts
- Basic charts

#### ⭐ BASIC - $9.99/month
- 50 daily scans
- 20 active alerts
- All scans & analytics
- Advanced charts
- News feed

#### 💫 PRO - $24.99/month
- 200 daily scans
- 50 active alerts
- Everything in Basic
- Whale activity tracking
- AI-powered signals
- Priority support

#### 👑 VIP - $49.99/month
- **Unlimited** scans & alerts
- Everything in Pro
- VIP community channel
- Copy trading insights
- Custom alert conditions
- 24/7 priority support
- Early access to features

**Payment Options:**
- Crypto (TON, USDT, BTC, ETH)
- Telegram Stars ⭐
- Credit/Debit Card

**Usage:**
```
/premium
```

---

## 🗄️ Database Schema

### New Tables Created

#### `user_profiles`
- User trading preferences
- Risk level: conservative, moderate, aggressive, degen
- Trading style: scalper, day_trader, swing_trader, hodler
- Favorite pairs & watchlist
- Timezone & notification settings

#### `user_alerts`
- Custom price/indicator alerts
- Alert types: price, rsi, volume, divergence, cross, funding, whale
- Conditions: above, below, crosses_above, crosses_below, equals
- Repeat/one-time triggers

#### `scan_history`
- Logs all user scans
- Caches scan results
- Track usage for premium limits

#### `signal_history`
- Trading signals generated
- Direction, confidence score, style, risk level
- Indicators used & AI reasoning
- View count tracking

#### `premium_subscriptions`
- User subscription tiers
- Feature access control
- Scan & alert limits
- Expiration tracking

#### `market_cache`
- Caches expensive API calls
- TTL-based expiration
- Reduces API rate limits

---

## 🔧 Technical Implementation

### New Services

1. **BinanceAPIService** - Real-time market data
   - Price feeds
   - 24h tickers
   - Kline/candlestick data
   - RSI calculation
   - Moving averages (SMA, EMA)
   - Support/resistance detection
   - Futures open interest
   - Funding rates
   - Long/short ratios

2. **MarketScanService** - Market-wide analysis
   - Deep market scans
   - Top movers identification
   - Volume leaders
   - Volatility detection
   - Trend analysis

3. **PairAnalyticsService** - Individual pair analysis
   - Multi-timeframe RSI
   - Trend bias determination
   - Volume analysis
   - ATR volatility
   - Support/resistance levels
   - Risk zone identification

4. **UserProfileService** - User management
   - Profile dashboard
   - Preferences tracking
   - Activity history

5. **PremiumService** - Subscription management
   - Tier definitions
   - Feature access control
   - Usage limits enforcement

6. **NewsService** - Market news (placeholder)
   - Crypto news aggregation
   - Exchange listings
   - Economic calendar

7. **EducationService** - Learning content
   - Trading tutorials
   - Glossary terms
   - Educational resources

---

## 📡 API Integrations

### Binance API
- **Purpose**: Real-time market data for all USDT pairs
- **Endpoints Used**:
  - `/api/v3/ticker/24hr` - 24h statistics
  - `/api/v3/klines` - Candlestick data
  - `/fapi/v1/openInterest` - Futures OI
  - `/fapi/v1/fundingRate` - Funding rates
- **Rate Limits**: 1200 requests/minute (weight-based)
- **Setup**: Optional, add `BINANCE_API_KEY` to `.env`

### CoinGecko API
- **Purpose**: Additional market data & metadata
- **Rate Limits**: 10-50 calls/minute (free tier)
- **Setup**: Optional, add `COINGECKO_API_KEY` to `.env`

### DexScreener API
- **Purpose**: SERPO token data from DEX
- **Existing**: Already configured

### TON API
- **Purpose**: TON blockchain data, wallet tracking
- **Existing**: Already configured

---

## 🚀 What's Next (Phase 2)

### Advanced Features Coming Soon

1. **RSI Heatmap** (`/rsi`)
   - Multi-timeframe RSI across top coins
   - Overbought/oversold zones
   - Loading zone identification

2. **Divergence Scanner** (`/divergence`)
   - Bullish & bearish RSI divergences
   - Automatic detection across watchlist

3. **Moving Average Crosses** (`/cross`)
   - Golden Cross & Death Cross tracking
   - Multi-timeframe detection (1h, 4h, 1d)

4. **Open Interest Monitor** (`/oi`)
   - Rising OI + price = strong trend
   - Rising OI + falling price = squeeze setup
   - Falling OI = cooling down

5. **Funding Rates** (`/rates`)
   - Overcrowded longs/shorts detection
   - Squeeze zone identification

6. **Money Flow** (`/flow`)
   - Spot & futures inflows/outflows
   - Real money movement tracking

7. **Smart Signals** (`/signals` - enhanced)
   - Combines all metrics
   - AI confidence scoring
   - Trade style suggestions (scalp/intraday/swing/avoid)
   - Risk labeling

8. **Trend Coins** (`/trendcoins`)
   - Sustained trend filtering
   - Swing trading candidates

9. **Charts** (`/charts`, `/supercharts`)
   - Live chart generation
   - Preset layouts: scalp, intraday, swing
   - Derivatives charts with OI, liquidations

10. **Heatmap** (`/heatmap`)
    - Global crypto heatmap visualization

11. **Whale Alerts** (`/whale`)
    - Large transfers
    - Huge buy/sell orders
    - Liquidation clusters

12. **Copy Trading** (`/copy`)
    - Top trader aggregation
    - Performance metrics
    - Drawdown tracking

---

## 🧪 Testing Guide

### Test Each New Command

```bash
# Market Analysis
/scan
/analyze BTCUSDT
/analyze ETHUSDT
/radar

# News & Calendar
/news
/calendar

# Learning
/learn
/learn 1
/glossary
/glossary fomo

# Profile & Premium
/profile
/premium

# Alerts (test in private chat)
/alerts
/alerts on
/alerts status
/alerts buy on
/alerts off
```

### Expected Behavior

- **Without Binance API Key**: Commands work with mock/fallback data
- **With Binance API Key**: Real-time market data from Binance
- **Free Tier**: Limited to 10 scans/day, 5 alerts
- **Premium Tier**: Higher limits based on subscription

---

## ⚙️ Configuration

### Required Environment Variables

```env
# Already configured
TELEGRAM_BOT_TOKEN=your_bot_token
OPENAI_API_KEY=your_openai_key
DB_CONNECTION=mysql
DB_DATABASE=serpoai_db

# New optional variables
BINANCE_API_KEY=                    # Optional for real market data
BINANCE_API_SECRET=                 # Optional
COINGECKO_API_KEY=                  # Optional for enhanced data
```

### Service Registration

All new services are automatically injected into `CommandHandler` via Laravel's service container. No manual registration needed.

---

## 🐛 Known Limitations

1. **News Service**: Currently shows placeholder data. Real-time news integration coming in Phase 2.
2. **Economic Calendar**: Placeholder data. Will integrate with economic calendar API.
3. **Charts**: `/charts` command not yet implemented (Phase 2).
4. **Binance API**: Rate limits apply. Consider caching strategy for high traffic.
5. **Premium Payments**: Payment processing not implemented. Manual subscription management for now.

---

## 📈 Usage Statistics

Track your usage:
- Daily scan count: Check with `/profile`
- Active alerts: Check with `/alerts status`
- Recent activity: View in `/profile`
- Upgrade needs: Monitor limits and upgrade with `/premium`

---

## 💡 Pro Tips

1. **Use `/analyze` before trading** - Get comprehensive pair analysis
2. **Set up alerts** - Use `/alerts buy on` to catch buy opportunities
3. **Check `/news` daily** - Stay informed on market events
4. **Learn with `/glossary`** - Understand trading terminology
5. **Monitor `/scan` regularly** - Catch market-wide trends early
6. **Upgrade to Pro/VIP** - Unlock unlimited scans and advanced features

---

## 🆘 Support

- **Bot Issues**: Type `/help` for command list
- **Premium Support**: Contact @SerpoSupport
- **Community**: Join our official channel
- **Documentation**: This file and `/learn` command

---

## 🎯 Changelog

### v1.1.0 (December 3, 2025)

**New Features:**
- 20+ new trading commands
- Full market scanning with `/scan`
- Pair analytics with `/analyze`
- User profiles and premium tiers
- News and economic calendar
- Learning center and glossary
- Advanced alert management

**Technical:**
- 6 new database tables
- 7 new service classes
- Binance API integration
- Market data caching system
- Premium subscription system

**Database:**
- user_profiles
- user_alerts
- scan_history
- signal_history
- premium_subscriptions
- market_cache

---

Made with 💚 by SerpoAI Team
