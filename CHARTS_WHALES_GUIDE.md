# 📈 Charts, Heatmaps & Whale Alerts Guide

## Overview

SerpoAI now includes professional-grade charting, market visualization, and whale tracking features using reliable data sources.

---

## 📊 Live Charts (`/charts`)

### Features
- **TradingView Integration**: Industry-standard charting platform
- **3 Preset Modes**: Optimized for different trading styles
- **Multi-Market Support**: Crypto, Stocks, Forex

### Usage
```
/charts [symbol] [mode]
```

### Chart Modes

#### 1. Scalp Mode (5-minute)
- **Timeframe**: 5 minutes
- **Indicators**: VWAP, Volume
- **Best For**: Day traders, scalpers
- **Example**: `/charts BTC scalp`

#### 2. Intraday Mode (15-minute) - Default
- **Timeframe**: 15 minutes
- **Indicators**: RSI, MACD, Bollinger Bands
- **Best For**: Intraday trading, swing entries
- **Example**: `/charts ETH intraday`

#### 3. Swing Mode (4-hour)
- **Timeframe**: 4 hours
- **Indicators**: Moving Averages, Volume
- **Best For**: Swing trading, position trading
- **Example**: `/charts AAPL swing`

### Supported Markets
- **Crypto**: `BTC`, `ETH`, `SOL`, `BNB`, etc.
- **Stocks**: `AAPL`, `TSLA`, `SPY`, etc.
- **Forex**: `EURUSD`, `GBPJPY`, etc.

### Quick Analysis
Each chart includes:
- Current trend (bullish/bearish/neutral)
- 24h price change
- 24h high/low
- Quick sentiment indicator

---

## 🔥 Derivatives Super Charts (`/supercharts`)

### Features
- **Open Interest (OI)**: Track futures contract volume
- **Funding Rates**: See long/short bias
- **Long/Short Ratios**: Account distribution
- **Liquidations**: Recent forced closures
- **CVD (Cumulative Volume Delta)**: Buy vs sell pressure

### Usage
```
/supercharts [symbol]
```

### Data Sources
- **Binance Futures API**: Official exchange data
- **Real-time Updates**: 2-minute cache
- **Professional Metrics**: Institution-grade analytics

### Metrics Explained

#### Open Interest (OI)
- Total open perpetual futures contracts
- Rising OI + Rising Price = Strong bullish momentum
- Rising OI + Falling Price = Strong bearish momentum
- Falling OI = Position unwinding

#### Funding Rate
- Fee paid between long/short traders every 8 hours
- **Positive** (>0.01%): Longs paying shorts (bullish bias)
- **Negative** (<-0.01%): Shorts paying longs (bearish bias)
- **Neutral** (-0.01% to 0.01%): Balanced market

#### Long/Short Ratio
- Ratio of long vs short positions by account count
- **>1.5**: Heavily long biased (potential reversal risk)
- **<0.67**: Heavily short biased (potential squeeze risk)
- **0.9-1.1**: Balanced

#### Liquidations
- Recent forced closures of leveraged positions
- **Long Liquidations**: Market selling (price dropping)
- **Short Liquidations**: Market buying (price pumping)
- High liquidation count = High volatility

#### CVD (Cumulative Volume Delta)
- Net difference between buy and sell volume
- **Positive**: More aggressive buying
- **Negative**: More aggressive selling
- Helps identify true market pressure

### Example Use Cases

**Scenario 1: Bullish Confirmation**
```
✅ Rising Open Interest
✅ Positive Funding Rate (longs paying)
✅ Long/Short Ratio > 1.2
✅ Positive CVD
→ Strong bullish sentiment
```

**Scenario 2: Reversal Warning**
```
⚠️ Falling Open Interest
⚠️ Extreme Funding Rate (>0.1%)
⚠️ Long/Short Ratio > 2
⚠️ Heavy Long Liquidations
→ Potential top, long squeeze risk
```

---

## 🎨 Market Heatmap (`/heatmap`)

### Features
- **Visual Market Overview**: See entire market at a glance
- **Performance Categories**: Organized by strength
- **Sentiment Analysis**: Overall market mood
- **Real-time Data**: Binance market snapshot

### Usage
```
/heatmap
```

### Categories

#### 🟢🟢 Strong Gainers (+10%+)
- Top performing assets
- Potential momentum plays
- Watch for exhaustion

#### 🟢 Gainers (+3% to +10%)
- Moderate uptrend
- Building momentum
- Good risk/reward

#### ⚪ Neutral (-3% to +3%)
- Range-bound
- Accumulation/distribution
- Wait for breakout

#### 🔴 Losers (-3% to -10%)
- Moderate downtrend
- Potential oversold
- Support levels critical

#### 🔴🔴 Strong Losers (-10%+)
- Heavy selling
- Capitulation possible
- High risk/high reward

### Market Sentiment Levels

**🚀 Very Bullish**
- 60%+ gainers
- Strong upward momentum
- High confidence

**📈 Bullish**
- 50-60% gainers
- Positive trend
- Most coins rising

**⚖️ Neutral**
- Balanced distribution
- Choppy market
- Low conviction

**📉 Bearish**
- 50-60% losers
- Negative trend
- Risk-off environment

**💥 Very Bearish**
- 60%+ losers
- Heavy selling
- Fear dominant

### Filters
- **Minimum Volume**: $1M+ (excludes low liquidity coins)
- **Update Frequency**: 3 minutes
- **Coverage**: Top 20-50 coins by volume

---

## 🐋 Whale Alerts (`/whale`)

### Features
- **Large Order Walls**: $100k+ orders on order book
- **Liquidation Clusters**: Price zones with heavy liquidations
- **Volume Spikes**: Unusual trading activity detection

### Usage
```
/whale [symbol]
```

### Components

#### 1. Large Order Walls

**Buy Walls (Support)**
- Large bid orders
- Price support levels
- Potential accumulation zones

**Sell Walls (Resistance)**
- Large ask orders
- Price resistance levels
- Potential distribution zones

**Threshold**: $100,000 per order

**Interpretation**:
- **Heavy Buy Wall**: Strong support, bullish
- **Heavy Sell Wall**: Strong resistance, bearish
- **Balanced**: No clear whale pressure

#### 2. Liquidation Clusters

**What They Show**:
- Price levels with many recent liquidations
- Dominant side (longs vs shorts liquidated)
- Total liquidation count

**Trading Signals**:
- **Many Long Liqs**: Bearish pressure, watch support
- **Many Short Liqs**: Bullish pressure, watch resistance
- **High Activity**: Volatile market, caution

#### 3. Volume Spikes

**Detection**:
- Compares last 5 minutes to 24h average
- **3x+ average**: High intensity spike
- **5x+ average**: Extreme intensity spike

**Causes**:
- Large institutional orders
- News/announcements
- Whale accumulation/distribution
- Liquidation cascades

**Trading Implications**:
- High volume = strong conviction
- Spike + price up = Buying pressure
- Spike + price down = Selling pressure
- Multiple spikes = High volatility

### Example Scenarios

**Scenario 1: Whale Accumulation**
```
✅ Large buy walls below price
✅ No volume spikes (stealth buying)
✅ Minimal liquidations
→ Potential bullish setup
```

**Scenario 2: Distribution Warning**
```
⚠️ Large sell walls above price
⚠️ Volume spikes on down moves
⚠️ Heavy long liquidations
→ Potential bearish breakdown
```

**Scenario 3: Squeeze Setup**
```
🚨 Heavy sell walls
🚨 Liquidation cluster just above
🚨 Sudden buy volume spike
→ Potential short squeeze
```

---

## 🔗 Data Sources

### Reliable & Trusted Sources

**TradingView**
- Industry-standard charting
- Professional-grade analysis
- Global exchange integration

**Binance API**
- World's largest exchange
- Real-time market data
- Official futures metrics

**Binance Futures**
- Open interest data
- Funding rates
- Long/short ratios
- Liquidation data

**Order Book Analysis**
- Live Binance order book
- 100-level depth
- Real-time updates

---

## 💡 Pro Tips

### Chart Analysis
1. **Start with higher timeframe** (swing) for big picture
2. **Zoom into intraday** for entry timing
3. **Use scalp mode** for precise execution
4. **Compare across modes** for confirmation

### Derivatives Trading
1. **High OI + High Funding** = Crowded trade (reversal risk)
2. **CVD divergence** = Price/volume mismatch (watch for reversal)
3. **Liquidation clusters** = Support/resistance zones
4. **Extreme ratios** = Contrarian opportunities

### Heatmap Usage
1. **Check heatmap first** for market mood
2. **Focus on categories** matching your strategy
3. **Watch sentiment shifts** for trend changes
4. **Use for risk management** (risk-off vs risk-on)

### Whale Tracking
1. **Large walls can move** (not always reliable)
2. **Volume spikes > order walls** for conviction
3. **Liquidation zones = key levels** to watch
4. **Combine with price action** for best results

---

## 🎯 Trading Workflows

### Day Trading Workflow
1. `/heatmap` - Check overall market
2. `/whale BTC` - Identify key levels
3. `/charts BTC scalp` - Find entry
4. `/supercharts BTC` - Confirm with derivatives

### Swing Trading Workflow
1. `/charts BTC swing` - Identify trend
2. `/supercharts BTC` - Check OI/funding
3. `/whale BTC` - Find support/resistance
4. `/heatmap` - Confirm sector strength

### Risk Management Workflow
1. `/heatmap` - Market sentiment
2. `/whale [position]` - Exit liquidity check
3. `/supercharts [position]` - Liquidation risk
4. Position sizing based on volatility

---

## 🔄 Update Frequencies

- **Charts**: Real-time (TradingView)
- **Super Charts**: 2 minutes
- **Heatmap**: 3 minutes
- **Whale Alerts**: 2 minutes

---

## ⚙️ Technical Details

### Performance Optimizations
- Intelligent caching (2-3 minute TTL)
- Lazy loading of heavy data
- Parallel API calls where possible
- Automatic timeout handling (10s)

### Rate Limit Protection
- Built-in rate limit detection
- Automatic fallback mechanisms
- Cache-first architecture
- User-friendly error messages

### Data Quality
- Multiple data source validation
- Outlier detection and filtering
- Minimum volume thresholds
- Real-time accuracy checks

---

## 🚀 Coming Soon

- [ ] Custom chart templates
- [ ] Alert creation from charts
- [ ] Historical heatmap playback
- [ ] Whale wallet tracking
- [ ] Multi-timeframe CVD
- [ ] Order flow imbalance detection
- [ ] Smart money indicators

---

## 📚 Related Commands

- `/analyze [symbol]` - Deep technical analysis
- `/scan` - Multi-market scanner
- `/sr [symbol]` - Support/resistance levels
- `/flow [symbol]` - Money flow analysis
- `/trendcoins` - Trending assets

---

**Note**: All data is for informational purposes only. Not financial advice. Trade at your own risk.

---

*Last Updated: January 2, 2026*
