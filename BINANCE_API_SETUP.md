# Binance API Setup Guide (Optional)

## Why Add Binance API?

The Binance API enables **real-time market data** for all USDT trading pairs:
- Live price feeds
- 24h ticker statistics
- Kline/candlestick data for technical analysis
- RSI calculations across timeframes
- Moving averages (SMA, EMA)
- Futures open interest
- Funding rates
- Long/short ratios

**Without Binance API:**
- `/scan` works with fallback data (limited)
- `/analyze` only works for SERPO
- `/radar` uses cached/limited data

**With Binance API:**
- Full market scanning across 1000+ pairs
- Any pair analysis (BTCUSDT, ETHUSDT, etc.)
- Real-time top movers
- Advanced technical indicators

## Getting Your API Key

### Step 1: Create Binance Account
1. Go to [https://www.binance.com](https://www.binance.com)
2. Sign up or log in to your account

### Step 2: Create API Key
1. Go to **Account** (top right profile icon)
2. Click **API Management**
3. Or direct link: [https://www.binance.com/en/my/settings/api-management](https://www.binance.com/en/my/settings/api-management)

### Step 3: Create New API Key
1. Click **Create API** button
2. Choose **System Generated** (recommended for bot)
3. Give it a label: "SerpoAI Bot"
4. Complete 2FA verification

### Step 4: Configure Permissions
**IMPORTANT:** For SerpoAI, you only need **READ** permissions!

✅ Enable:
- **Enable Reading** (checked)

❌ Disable (for security):
- Enable Spot & Margin Trading (unchecked)
- Enable Futures (unchecked)  
- Enable Withdrawals (unchecked)

**Security Best Practice:**  
The bot only needs to read market data, NOT to trade or withdraw funds!

### Step 5: Save Your Keys
After creation, you'll see:
- **API Key**: `xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
- **Secret Key**: `xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

⚠️ **IMPORTANT:**
- Copy both keys immediately
- Secret Key is shown ONLY ONCE
- Keep them secure and never share publicly
- If lost, delete old key and create new one

### Step 6: Add to SerpoAI

1. Open your `.env` file:
   ```
   C:\xampp\htdocs\SerpoAI\.env
   ```

2. Find these lines (around line 60):
   ```env
   # Binance API (optional for enhanced market data)
   BINANCE_API_KEY=
   BINANCE_API_SECRET=
   ```

3. Add your keys:
   ```env
   BINANCE_API_KEY=your_api_key_here
   BINANCE_API_SECRET=your_secret_key_here
   ```

4. Clear config cache:
   ```bash
   php artisan config:clear
   ```

5. Test it:
   ```
   /scan       # Should now show real market data
   /analyze BTCUSDT   # Should work with full analysis
   ```

## Security Tips

1. **Never commit `.env` file to GitHub**
   - Already in `.gitignore`
   - Contains sensitive credentials

2. **Use IP whitelist** (optional but recommended)
   - Go back to Binance API Management
   - Click **Edit** on your API key
   - Under "IP Access Restrictions"
   - Add your server IP: `72.62.43.137` (production)
   - Or `Unrestricted` for testing (less secure)

3. **Monitor API usage**
   - Binance has rate limits: 1200 requests/minute
   - SerpoAI caches results to stay well below limits
   - Check usage in Binance API Management dashboard

4. **Rotate keys periodically**
   - Change API keys every 3-6 months
   - Delete old keys after creating new ones

## Rate Limits

Binance API has weight-based rate limiting:
- **1200 weight per minute** (rolling window)
- Most endpoints: 1-5 weight per request
- SerpoAI caching: 3-5 minutes TTL
- Estimated usage: ~50-100 weight/minute at peak

**You won't hit limits with normal bot usage.**

## Troubleshooting

### "Invalid API key"
- Double-check key copied correctly (no spaces)
- Ensure API key is enabled (not disabled/deleted)
- Check if IP whitelist includes your server

### "Timestamp mismatch"
- Server time might be out of sync
- Run: `net start w32time && w32tm /resync` (Windows)

### "IP not whitelisted"
- Add server IP to API whitelist
- Or set to "Unrestricted" for testing

### No data showing
- Check if config cache cleared: `php artisan config:clear`
- Verify keys in `.env` (no quotes needed)
- Check logs: `storage/logs/laravel.log`

## Testing Commands

After adding Binance API, test these:

```
# Market scan with real data
/scan

# Analyze Bitcoin
/analyze BTCUSDT

# Analyze Ethereum
/analyze ETHUSDT

# Analyze any pair
/analyze BNBUSDT
/analyze SOLUSDT
/analyze ADAUSDT

# Market radar
/radar
```

## Alternative: Use Without Binance API

If you prefer not to add Binance API (or testing first):
- All user management features work (profile, premium, alerts)
- Learning features work (learn, glossary)
- SERPO-specific commands work (via DexScreener)
- Market-wide features show limited/cached data

**You can always add the API later when ready!**

## Next Steps

1. ✅ Get API key from Binance
2. ✅ Add to `.env` file
3. ✅ Clear config cache
4. ✅ Test with `/scan` and `/analyze BTCUSDT`
5. ✅ Monitor rate limits (should be fine)
6. ✅ Set IP whitelist for production

---

**Ready to test?** Message the bot: `/scan` or `/analyze BTCUSDT`

Need help? Check the logs: `storage/logs/laravel.log`
