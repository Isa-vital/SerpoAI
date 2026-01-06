# 📊 Technical Structure & Momentum Features

## Advanced Technical Analysis Tools

SerpoAI now includes professional-grade technical analysis tools for traders who need precise entry/exit points and momentum confirmation.

---

## 🎯 `/sr` – Smart Support & Resistance

**AI-powered multi-timeframe S/R analysis**

### What It Does:
- Analyzes **1H, 4H, and 1D** timeframes simultaneously
- Identifies **confluent levels** (appear in multiple timeframes)
- Detects **liquidity zones** (high volume areas)
- Uses **AI to assess level strength**
- Pinpoints **nearest key levels** to current price

### How It Works:
```
/sr BTCUSDT
/sr ETHUSDT
/sr AAPL
```

### Example Output:
```
🎯 SMART SUPPORT & RESISTANCE
━━━━━━━━━━━━━━━━━━━━

Symbol: BTCUSDT
Current Price: $43,250

🔺 Resistance Levels
1. $44,500 (+2.89%)
2. $45,200 (+4.51%)
3. $46,800 (+8.21%)
4. $48,000 (+10.98%)
5. $50,000 (+15.61%)

🔻 Support Levels
1. $42,000 (-2.89%)
2. $41,200 (-4.74%)
3. $40,000 (-7.51%)
4. $38,500 (-10.98%)
5. $37,000 (-14.45%)

⭐ Key Levels
Nearest Support: $42,000
Nearest Resistance: $44,500

💡 AI Insight
Strong confluence at $44,500 resistance zone across multiple timeframes. Current positioning suggests bullish momentum building toward this key level.
```

### Key Features:
- **Confluent Levels**: Shows levels that appear in multiple timeframes (stronger levels)
- **Distance Calculation**: Shows % distance from current price
- **AI Analysis**: OpenAI analyzes level strength and positioning
- **Liquidity Zones**: High-volume areas where price may react

---

## 📊 `/rsi` – Multi-Timeframe RSI Heatmap

**Visual RSI analysis across 5 timeframes**

### What It Does:
- Monitors RSI on **15m, 1H, 4H, 1D, 1W**
- Color-coded status indicators
- Provides specific signals for each timeframe
- Calculates **overall sentiment**
- Gives actionable **recommendation**

### Usage:
```
/rsi BTCUSDT
/rsi ETHUSDT
/rsi TSLA
```

### Example Output:
```
📊 RSI HEATMAP
━━━━━━━━━━━━━━━━━━━━

Symbol: BTCUSDT
Price: $43,250

🔴 15m: 72 - Overbought
   🔴 Sell Signal

🟡 1h: 65 - Strong
   🟡 Bullish

🟡 4h: 58 - Neutral
   🟡 Bullish

⚪ 1d: 52 - Neutral
   ⚪ Neutral

🟢 1w: 38 - Weak
   🟡 Bearish

━━━━━━━━━━━━━━━━━━━━
📈 Overall: Bullish

💡 Recommendation
Some overbought signals on lower timeframes - Monitor for short-term pullback while higher timeframes remain healthy
```

### RSI Status Indicators:
| RSI Value | Status | Emoji | Meaning |
|-----------|--------|-------|---------|
| ≥ 70 | Overbought | 🔴 | Potential sell |
| 60-69 | Strong | 🟡 | Bullish momentum |
| 40-59 | Neutral | ⚪ | No clear bias |
| 30-39 | Weak | 🟠 | Bearish pressure |
| ≤ 30 | Oversold | 🟢 | Potential buy |

---

## 🔍 `/divergence` – RSI Divergence Scanner

**Detects bullish and bearish divergences for reversal signals**

### What It Does:
- Scans **1H, 4H, 1D** timeframes
- Detects **bullish divergences** (lower low in price, higher low in RSI)
- Detects **bearish divergences** (higher high in price, lower high in RSI)
- Rates **signal strength**
- Provides interpretation

### Usage:
```
/divergence BTCUSDT
/divergence ETHUSDT
/divergence EURUSD
```

### Example Output (Divergence Found):
```
🔍 RSI DIVERGENCE SCANNER
━━━━━━━━━━━━━━━━━━━━

Symbol: BTCUSDT
Price: $43,250

⚠️ Divergences Detected

🟢 4h: Bullish Divergence
   Strength: Moderate

🟢 1d: Bullish Divergence
   Strength: Moderate

━━━━━━━━━━━━━━━━━━━━
Signal Strength: Strong

💡 Bullish divergence suggests potential reversal to upside
```

### Example Output (No Divergence):
```
🔍 RSI DIVERGENCE SCANNER
━━━━━━━━━━━━━━━━━━━━

Symbol: BTCUSDT
Price: $43,250

✅ No significant divergences detected
Market price and RSI are aligned
```

### Divergence Types:
- **Bullish Divergence** 🟢: Price makes lower low, RSI makes higher low → Potential bottom
- **Bearish Divergence** 🔴: Price makes higher high, RSI makes lower high → Potential top

### Trading Implications:
| Divergence | Signal | Action |
|------------|--------|--------|
| Bullish (Multi-TF) | Very Strong | Consider long entry |
| Bullish (Single TF) | Moderate | Watch for confirmation |
| Bearish (Multi-TF) | Very Strong | Consider taking profits |
| Bearish (Single TF) | Moderate | Watch for weakness |

---

## 📈 `/cross` – Moving Average Cross Monitor

**Tracks Golden Cross and Death Cross events**

### What It Does:
- Monitors **20/50 MA** crosses
- Monitors **50/200 MA** crosses (Golden/Death Cross)
- Checks **1H, 4H, 1D** timeframes
- Identifies **recent crosses**
- Provides **trend confirmation**

### Usage:
```
/cross BTCUSDT
/cross ETHUSDT
/cross SPY
```

### Example Output:
```
📈 MOVING AVERAGE CROSS MONITOR
━━━━━━━━━━━━━━━━━━━━

Symbol: BTCUSDT
Price: $43,250

🔔 Recent Crosses
🟡 Golden Cross (50/200) - 1d
🟡 Golden Cross (20/50) - 4h

📊 Current Status

1h
  MA20/50: 🟢 Bullish
  MA50/200: 🟢 Bullish

4h
  MA20/50: 🟢 Bullish
  MA50/200: 🟢 Bullish

1d
  MA20/50: 🟢 Bullish
  MA50/200: 🟢 Bullish

━━━━━━━━━━━━━━━━━━━━
Trend: Bullish trend confirmed
```

### MA Cross Types:
- **Golden Cross** 🟡: Fast MA crosses above Slow MA → Bullish signal
- **Death Cross** ⚫: Fast MA crosses below Slow MA → Bearish signal

### Monitored Crosses:
| Cross Type | Timeframe | Significance |
|------------|-----------|--------------|
| 20/50 | All | Short-term trend change |
| 50/200 | Daily+ | **Major** trend change (most important) |

### Trading Strategy:
1. **Golden Cross on 50/200 Daily**: Strong buy signal - enter long positions
2. **Death Cross on 50/200 Daily**: Strong sell signal - exit or short
3. **Multiple timeframe alignment**: Strongest signal - all TFs show same cross
4. **Diverging timeframes**: Choppy market - wait for clarity

---

## 🎯 Best Practices

### For Day Traders:
1. Use `/rsi BTCUSDT` to check momentum across timeframes
2. Use `/sr BTCUSDT` for precise entry/exit levels
3. Monitor `/cross BTCUSDT` on 1H/4H for short-term trends

### For Swing Traders:
1. Start with `/cross BTCUSDT` to confirm trend direction
2. Use `/divergence BTCUSDT` to spot reversals early
3. Use `/sr BTCUSDT` for position sizing and stop-loss placement

### For Position Traders:
1. Focus on Daily and Weekly timeframes in `/rsi`
2. Wait for Golden Cross on 50/200 Daily in `/cross`
3. Use `/sr` to identify major support zones for accumulation

---

## 💡 Pro Tips

1. **Multi-Timeframe Confirmation**: Best signals occur when multiple timeframes align
2. **Divergences + S/R**: Divergence near strong S/R = high probability setup
3. **Golden Cross + RSI**: Golden Cross with RSI 40-60 = healthy sustainable trend
4. **Volume Confirmation**: Check volume when price tests S/R levels
5. **Risk Management**: Always use stop-losses below support or above resistance

---

## 🔄 Update Frequency

- **RSI Heatmap**: Updates every 3 minutes
- **S/R Levels**: Updates every 5 minutes
- **Divergence Scanner**: Updates every 5 minutes
- **MA Cross Monitor**: Updates every 5 minutes

---

## ⚠️ Important Notes

1. **No Holy Grail**: These tools are indicators, not guarantees
2. **Combine Signals**: Use multiple tools together for best results
3. **Market Context**: Consider overall market conditions
4. **False Signals**: Can occur in ranging/choppy markets
5. **Always DYOR**: Do Your Own Research before trading

---

## 🆕 Coming Soon

- [ ] Volume Profile Analysis
- [ ] Order Flow Heatmap
- [ ] Fibonacci Retracement Auto-Detection
- [ ] Elliott Wave Pattern Recognition
- [ ] Ichimoku Cloud Analysis
- [ ] Custom Indicator Builder

---

## 📚 Learn More

Type `/learn technical analysis` to get educational content about:
- How to read S/R levels
- Understanding RSI divergences
- Trading MA crosses profitably
- Multi-timeframe analysis strategies

---

**SerpoAI** - Professional Technical Analysis at Your Fingertips 📊

*Trade smarter, not harder*
