# SERPO AI - Elite Features API Setup Guide

## 🎯 Overview

This guide covers all API integrations required for SERPO AI's elite features. APIs are organized by tier: **Basic (FREE)** and **Premium (PAID)**.

---

## 📊 Current Status

### ✅ Active APIs (Basic Tier)
- **Binance API** - Crypto OHLCV, orderbooks, funding
- **DexScreener API** - DEX pairs, liquidity
- **Alpha Vantage** - Forex rates, stocks
- **TON API** - TON blockchain data
- **CoinGecko** - Market cap, volume (optional)
- **Gemini AI** - Natural language processing (FREE 1500/day)
- **Groq AI** - Fast inference (FREE unlimited)

### 🔜 Premium APIs (Optional)
Premium features enhance accuracy but are not required for basic functionality.

---

## 1️⃣ SERPO DeepSearch™ APIs

### Basic Tier (FREE - Already Configured)

#### Binance API
**Purpose**: Crypto OHLCV, orderbooks, funding rates, open interest

```bash
# Already in .env
BINANCE_API_KEY=your_binance_key
BINANCE_API_SECRET=your_binance_secret
```

**Get Keys**: https://www.binance.com/en/my/settings/api-management
- ✅ No trading permissions needed
- ✅ Read-only access sufficient
- ✅ 1200 requests/minute

#### Alpha Vantage
**Purpose**: Forex rates, stock data, economic calendar

```bash
# Already in .env
ALPHA_VANTAGE_API_KEY=your_alpha_vantage_key
```

**Get Keys**: https://www.alphavantage.co/support/#api-key
- ✅ FREE tier: 500 requests/day
- ✅ Covers 150+ forex pairs
- ✅ All NYSE/NASDAQ stocks

### Premium Tier (OPTIONAL)

#### Glassnode
**Purpose**: On-chain flows, exchange netflow, whale activity

```bash
# Add to .env (optional)
GLASSNODE_API_KEY=your_glassnode_key
GLASSNODE_API_URL=https://api.glassnode.com/v1
```

**Pricing**: Starting at $29/month
**Get Keys**: https://studio.glassnode.com/settings/api

#### CryptoQuant
**Purpose**: Advanced derivatives data, whale tracking

```bash
# Add to .env (optional)
CRYPTOQUANT_API_KEY=your_cryptoquant_key
CRYPTOQUANT_API_URL=https://api.cryptoquant.com/v1
```

**Pricing**: Starting at $79/month
**Get Keys**: https://cryptoquant.com/settings/api

#### Nansen
**Purpose**: Wallet clustering, smart money tracking

```bash
# Add to .env (optional)
NANSEN_API_KEY=your_nansen_key
NANSEN_API_URL=https://api.nansen.ai/v1
```

**Pricing**: Starting at $150/month
**Get Keys**: https://nansen.ai/settings/api

#### OANDA
**Purpose**: Premium forex data, live spreads, economic calendar

```bash
# Add to .env (optional)
OANDA_API_KEY=your_oanda_key
OANDA_ACCOUNT_ID=your_account_id
OANDA_API_URL=https://api-fxpractice.oanda.com/v3
```

**Pricing**: FREE with practice account
**Get Keys**: https://www.oanda.com/demo-account/tpa/personal_token

---

## 2️⃣ SERPO Vision Backtest™ APIs

### Basic Tier (FREE - Already Configured)

#### Binance Historical Data
**Purpose**: Klines, historical funding, historical OI

```bash
# Already in .env (same keys as above)
BINANCE_API_KEY=your_binance_key
```

**Timeframes**: 1m, 5m, 15m, 1h, 4h, 1d
**History**: Up to 1000 candles per request

#### Polygon.io
**Purpose**: Stock bars, crypto historical data

```bash
# Add to .env
POLYGON_API_KEY=your_polygon_key
```

**Get Keys**: https://polygon.io/dashboard/api-keys
- ✅ FREE tier: 5 API calls/minute
- ✅ Covers stocks, options, crypto

### Premium Tier (OPTIONAL)

#### OpenAI Vision API
**Purpose**: Screenshot analysis, chart detection

```bash
# Add to .env (uses existing OpenAI key)
OPENAI_VISION_MODEL=gpt-4-vision-preview
```

**Pricing**: $0.01 per image
**Status**: Currently disabled (text-only backtesting works)

---

## 3️⃣ SERPO Degen Scanner™ APIs

### Basic Tier (FREE - Already Configured)

#### TON API
**Purpose**: TON blockchain contract analysis

```bash
# Already in .env
API_KEY_TON=your_ton_key
TON_API_URL=https://tonapi.io/v2
```

**Get Keys**: https://tonapi.io/
- ✅ FREE tier: 10 requests/second
- ✅ Contract verification
- ✅ Holder analysis

### Premium Tier (OPTIONAL)

#### Etherscan
**Purpose**: Ethereum contract intelligence

```bash
# Add to .env
ETHERSCAN_API_KEY=your_etherscan_key
ETHERSCAN_API_URL=https://api.etherscan.io/api
```

**Get Keys**: https://etherscan.io/myapikey
- ✅ FREE tier: 5 requests/second
- ✅ Contract verification
- ✅ ABI analysis

#### BSCScan
**Purpose**: Binance Smart Chain analysis

```bash
# Add to .env
BSCSCAN_API_KEY=your_bscscan_key
BSCSCAN_API_URL=https://api.bscscan.com/api
```

**Get Keys**: https://bscscan.com/myapikey
- ✅ FREE tier: 5 requests/second

#### BaseScan
**Purpose**: Base chain contract analysis

```bash
# Add to .env
BASESCAN_API_KEY=your_basescan_key
BASESCAN_API_URL=https://api.basescan.org/api
```

**Get Keys**: https://basescan.org/myapikey
- ✅ FREE tier: 5 requests/second

#### SolScan
**Purpose**: Solana token verification

```bash
# Add to .env
SOLSCAN_API_KEY=your_solscan_key
SOLSCAN_API_URL=https://api.solscan.io
```

**Get Keys**: https://pro.solscan.io/
- ⚠️ Partial support (work in progress)

---

## 4️⃣ SERPO Degen 101™ APIs

### Basic Tier (Already Configured)
- Uses internal knowledge base
- No additional APIs required

### Premium Tier (OPTIONAL)

#### Arkham Intelligence
**Purpose**: Entity tracking, fund flow analysis

```bash
# Add to .env
ARKHAM_API_KEY=your_arkham_key
ARKHAM_API_URL=https://api.arkhamintelligence.com/v1
```

**Pricing**: Custom pricing
**Get Keys**: https://www.arkhamintelligence.com/

---

## 🚀 Quick Start (Minimum Required)

**Current Setup Works!** You already have:
1. ✅ Binance API (crypto data)
2. ✅ Alpha Vantage (forex/stocks)
3. ✅ Gemini + Groq AI (natural language)
4. ✅ TON API (contract verification)

**Elite features are fully functional with basic tier.**

---

## 📈 Upgrade Path (Optional)

### Level 1: Enhanced Market Data ($0 - $50/month)
- Add Polygon.io (FREE tier)
- Add OANDA practice account (FREE)
- Add Etherscan/BSCScan (FREE)

### Level 2: Professional Intelligence ($50 - $200/month)
- Glassnode ($29/month)
- CryptoQuant ($79/month)
- Premium Polygon ($99/month)

### Level 3: Institutional Grade ($200+/month)
- Nansen ($150/month)
- Arkham Intelligence (custom)
- All premium data sources

---

## 🔧 Configuration Check

```bash
# Test current API configuration
php artisan tinker
>>> config('elite-features.deepsearch.crypto_sources.binance.enabled')
>>> config('elite-features.shared.security.rate_limiting')
```

---

## 📊 API Availability Matrix

| Feature | Basic (FREE) | Premium (PAID) |
|---------|--------------|----------------|
| DeepSearch | Binance, Alpha Vantage, DexScreener | Glassnode, Nansen, OANDA |
| Backtest | Binance historical, Polygon FREE | OpenAI Vision, Premium historical |
| Degen Scanner | TON API, Etherscan FREE | Multi-chain explorers |
| Degen 101 | Internal knowledge | Arkham, Nansen whale data |

---

## ⚠️ Rate Limits

### Current FREE Tier Limits:
- **Binance**: 1200 requests/minute ✅
- **Alpha Vantage**: 500 requests/day ✅
- **Gemini AI**: 1500 requests/day ✅
- **Groq AI**: Unlimited ✅
- **TON API**: 10 requests/second ✅

**Caching Strategy**:
- Market data: 5 minutes
- AI completions: 5 minutes
- Sentiment analysis: 30 minutes

This keeps all operations within FREE tier limits.

---

## 📝 Next Steps

1. **Current Setup**: Test elite features with basic tier
2. **Monitor Usage**: Check API consumption in logs
3. **Upgrade When Needed**: Add premium APIs if hitting rate limits
4. **Priority Order**:
   - Polygon.io (FREE upgrade)
   - OANDA (FREE practice)
   - Etherscan/BSCScan (FREE)
   - Glassnode (first paid upgrade)

---

## 🆘 Support

- Basic tier works out of the box
- Premium APIs are optional enhancements
- Elite features degrade gracefully without premium sources
- Contact API providers directly for key issues

**Happy Trading! 🚀**
