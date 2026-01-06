# News API Setup Guide

## Overview
The bot fetches news from 4 sources. Each source is independent - if one fails, others continue working.

## API Keys Needed

### 1. CryptoPanic API (Recommended)
- **Website:** https://cryptopanic.com/developers/api/
- **Free Tier:** 20 requests/day
- **Steps:**
  1. Sign up at https://cryptopanic.com/
  2. Go to API settings
  3. Copy your API token
  4. Add to `.env`: `CRYPTOPANIC_API_KEY=your_token_here`

### 2. Twitter/X API v2 (Optional but recommended)
- **Website:** https://developer.twitter.com/
- **Free Tier:** 1500 tweets/month (enough for your bot)
- **Steps:**
  1. Apply for Twitter Developer account
  2. Create a new App
  3. Go to "Keys and tokens"
  4. Copy your Bearer Token
  5. Add to `.env`: `TWITTER_BEARER_TOKEN=your_bearer_token_here`

### 3. CoinGecko API (No key needed!)
- **Automatically works** - no registration required
- Free tier: 10-50 calls/minute

### 4. RSS Feeds (No key needed!)
- **Automatically works** - scrapes public RSS feeds
- Sources: CoinTelegraph, Decrypt, CoinDesk

## Testing

Without API keys, the bot will still work using:
- ✅ CoinGecko (no key needed)
- ✅ RSS Feeds (no key needed)

To test with all sources:
```bash
# Test the /news command in Telegram
/news
```

## Current Status

Without any API keys configured:
- **Working:** CoinGecko + RSS Feeds = 4 news items
- **Not working:** CryptoPanic + Twitter (need keys)

With CryptoPanic key:
- **Working:** CryptoPanic + CoinGecko + RSS = 6 news items
- **Not working:** Twitter (optional)

With all keys:
- **Working:** All 4 sources = 8 news items (2 from each)

## Error Handling

Each source works independently:
- If CryptoPanic fails → Other sources continue
- If Twitter fails → Other sources continue
- If RSS fails → Other sources continue
- If CoinGecko fails → Other sources continue

The bot will show whatever news it successfully fetched.

## Rate Limits

- **CryptoPanic:** 20 requests/day (1 per /news command)
- **Twitter:** 1500 tweets/month (~50 per day)
- **CoinGecko:** 10-50 calls/min (unlimited)
- **RSS Feeds:** Unlimited

## Recommendations

1. **Start with:** Just RSS + CoinGecko (no keys needed) ✅
2. **Add next:** CryptoPanic key (best quality news)
3. **Optional:** Twitter key (trending topics)

The system works perfectly even without any API keys!
