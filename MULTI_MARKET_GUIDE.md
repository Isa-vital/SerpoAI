# 🌍 Multi-Market Analysis Guide

## SerpoAI v2.0 - Universal Trading Assistant

SerpoAI now supports comprehensive analysis across **Crypto**, **Stocks**, and **Forex** markets with professional-grade data and insights.

---

## 🚀 Core Features

### 1️⃣ `/scan` - Full Market Deep Scan

**The most powerful command** - Scans ALL markets in one go!

#### What it includes:

**💎 Crypto Markets:**
- Market overview (sentiment, volume, dominance)
- Fear & Greed Index
- Top 10 gainers and losers (24h)
- Volume leaders
- Volatility alerts
- BTC dominance tracking

**📈 Stock Markets:**
- Major indices (S&P 500, Dow Jones, NASDAQ)
- Market status (Open/Closed/Pre-Market/After-Hours)
- Top gainers/losers
- Most active stocks

**💱 Forex Markets:**
- Major currency pairs (EUR/USD, GBP/USD, USD/JPY, etc.)
- 24/5 market status
- Current trading session (Asian/European/US)
- Real-time exchange rates

#### Data Sources:
- **Crypto**: Binance, CoinGecko, Fear & Greed Index
- **Stocks**: Alpha Vantage, Yahoo Finance, Polygon
- **Forex**: Alpha Vantage, OANDA

#### Usage:
```
/scan
```

#### Example Output:
```
🌍 FULL MARKET DEEP SCAN
━━━━━━━━━━━━━━━━━━━━

💎 CRYPTO MARKETS
━━━━━━━━━━━━━━━━━━━━
📊 Market Overview
• Pairs: 1,234
• Gainers: 🟢 567 | Losers: 🔴 445
• Sentiment: 🐂 Bullish
• 24h Volume: $45.2B
• Fear & Greed: 68/100 (Greed)
• BTC Dominance: 54.3%

🚀 Top Gainers (24h)
1. BTCUSDT +8.5%
   💰 $43,250 | Vol: $2.1B
...
```

---

### 2️⃣ `/analyze [symbol]` - Universal Pair Analytics

**Intelligent symbol detection** - Automatically identifies whether you're analyzing crypto, stocks, or forex!

#### Supported Markets:

**💎 Crypto Examples:**
```
/analyze BTCUSDT
/analyze ETH
/analyze SERPO
/analyze SOL/USDT
```

**📈 Stock Examples:**
```
/analyze AAPL    (Apple)
/analyze TSLA    (Tesla)
/analyze MSFT    (Microsoft)
/analyze SPY     (S&P 500 ETF)
/analyze QQQ     (NASDAQ ETF)
```

**💱 Forex Examples:**
```
/analyze EURUSD  (Euro/US Dollar)
/analyze GBPJPY  (British Pound/Japanese Yen)
/analyze AUDUSD  (Australian Dollar/US Dollar)
```

#### What You Get:

**For Crypto:**
- Current price & 24h change
- Volume analysis
- Technical indicators (RSI, MA20, MA50, EMA)
- Trend bias (Bullish/Bearish/Neutral)
- Support & resistance levels
- Risk assessment
- Volatility (ATR)

**For Stocks:**
- Current price & daily change
- Volume
- Market cap & P/E ratio
- Technical indicators
- Trend analysis
- Data from multiple sources

**For Forex:**
- Real-time exchange rate
- Change percentage
- Current trading session
- Technical indicators
- Trend analysis

#### Usage:
```
/analyze BTCUSDT
/analyze AAPL
/analyze EURUSD
```

#### Example Output:
```
📊 CRYPTO ANALYTICS: BTCUSDT
━━━━━━━━━━━━━━━━━━━━

💰 Price Action
Current: $43,250
Change: 🟢 +5.8% ($2,375)
Volume: 1.2B

📈 Technical Analysis
Trend: 🐂 Bullish
RSI: 1H: 67 | 4H: 71
MA20: $42,100
MA50: $40,850

🎯 Key Levels
Support: $42,000
Resistance: $44,500

⚠️ Risk Assessment
Mid-Range - Neutral Zone

━━━━━━━━━━━━━━━━━━━━
📡 Data: Binance, Technical Analysis

💡 Use /scan for full market overview
```

---

## 🎯 Smart Symbol Detection

SerpoAI automatically detects the market type:

| Pattern | Market | Example |
|---------|--------|---------|
| Ends with USDT/BTC | Crypto | BTCUSDT, ETHBTC |
| 1-5 characters | Stock | AAPL, TSLA, AMD |
| 6 characters (currency pair) | Forex | EURUSD, GBPJPY |

---

## 📊 Additional Commands

### Market Intelligence
- `/radar` - Quick market radar (top movers)
- `/price` - Current SERPO price
- `/chart` - Price chart
- `/signals` - Trading signals
- `/sentiment` - Market sentiment

### AI-Powered
- `/aisentiment [coin]` - Real social sentiment
- `/predict [coin]` - AI market predictions
- `/recommend` - Personalized advice
- `/query [question]` - Ask anything

### Analytics & Reports
- `/daily` - Daily market summary
- `/weekly` - Weekly performance
- `/trends [days]` - Volume & holder trends
- `/whales` - Whale activity tracking

### News & Events
- `/news` - Latest crypto news & listings
- `/calendar` - Economic events calendar

---

## 🔑 API Keys Setup (Optional)

For enhanced data quality, configure these APIs in `.env`:

```env
# Stock & Forex Markets
ALPHA_VANTAGE_API_KEY=your_key_here
POLYGON_API_KEY=your_key_here

# Enhanced Crypto Data
COINGECKO_API_KEY=your_key_here
BINANCE_API_KEY=your_key_here
BINANCE_API_SECRET=your_secret_here
```

### Where to Get API Keys:

1. **Alpha Vantage** (Free tier available)
   - URL: https://www.alphavantage.co/support/#api-key
   - Use: Stocks & Forex real-time data

2. **Polygon.io** (Free tier available)
   - URL: https://polygon.io/
   - Use: Advanced stock market data

3. **CoinGecko** (Free tier available)
   - URL: https://www.coingecko.com/en/api
   - Use: Enhanced crypto market data

4. **Binance** (Optional)
   - URL: https://www.binance.com/en/support/faq/how-to-create-api-360002502072
   - Use: Advanced crypto trading data

---

## 🎨 Best Practices

1. **Start with `/scan`** - Get the big picture first
2. **Deep dive with `/analyze`** - Focus on specific assets
3. **Set alerts** - Never miss important price movements
4. **Use AI features** - Get predictive insights
5. **Track portfolio** - Monitor your holdings in real-time

---

## 💡 Pro Tips

- **Crypto Trading**: Use `/analyze BTCUSDT` before major trades
- **Stock Investing**: Check `/analyze AAPL` for technical levels
- **Forex Trading**: Monitor `/analyze EURUSD` during active sessions
- **Market Overviews**: Start your day with `/scan`
- **Risk Management**: Pay attention to risk zones in analysis

---

## 🆘 Need Help?

- Type `/help` for complete command list
- Type `/query [your question]` to ask the AI
- Join our community for support and updates

---

## 📈 Coming Soon

- [ ] Futures market analysis
- [ ] Options flow data
- [ ] Institutional whale tracking
- [ ] Advanced chart patterns
- [ ] Automated trading signals
- [ ] Custom indicators

---

**SerpoAI** - Your Universal Trading Intelligence Platform 🚀

*Powered by Binance, Alpha Vantage, CoinGecko, and advanced AI*
