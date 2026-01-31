# Universal Alerts System

## Quick Start

The Universal Alerts System enables users to set price alerts for **crypto, forex, and stocks** via Telegram.

### User Commands

```
/setalert BTC 50000      → Alert when BTC goes above $50,000
/setalert AAPL 180       → Alert when AAPL goes above $180
/setalert EURUSD 1.10    → Alert when EUR/USD goes above $1.10
/myalerts                → View all active alerts
/alerts                  → Check system status
```

---

## For Developers

### Test the System

**Quick Test:**
```bash
php quick-alert-test.php
```

**Full Test:**
```bash
php test-universal-alerts.php
```

**Monitor Test:**
```bash
php artisan alerts:monitor --once
```

---

## Deployment

### Local/Development

Start the monitor manually:
```bash
php artisan alerts:monitor
```

Options:
```bash
php artisan alerts:monitor --interval=30    # 30-second checks
php artisan alerts:monitor --once           # Single check
php artisan alerts:monitor --cleanup        # Clean old alerts
```

---

### Production (Linux Server)

**Automated Deployment:**
```bash
chmod +x deploy-alerts.sh
sudo ./deploy-alerts.sh
```

**Manual Deployment:**

1. Create systemd service:
```bash
sudo nano /etc/systemd/system/serpoai-alerts.service
```

```ini
[Unit]
Description=SerpoAI Universal Alert Monitor
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/serpoai
ExecStart=/usr/bin/php /var/www/serpoai/artisan alerts:monitor --interval=60
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

2. Enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable serpoai-alerts
sudo systemctl start serpoai-alerts
sudo systemctl status serpoai-alerts
```

3. Set up daily cleanup:
```bash
crontab -e
```
Add:
```
0 3 * * * cd /var/www/serpoai && php artisan alerts:monitor --once --cleanup
```

---

## Configuration

### Required API Keys

**Alpha Vantage (for Forex & Stocks):**
1. Get free key: https://www.alphavantage.co/support/#api-key
2. Add to `.env`:
```env
ALPHA_VANTAGE_API_KEY=your_key_here
```

**Binance (Crypto):**
- No API key required ✓

**DexScreener (SERPO):**
- No API key required ✓

---

## Monitoring

### Check Status
```bash
sudo systemctl status serpoai-alerts
```

### View Logs
```bash
# Service logs
sudo journalctl -u serpoai-alerts -f

# Laravel logs
tail -f storage/logs/laravel.log | grep -i alert
```

### Get Statistics
```bash
php artisan alerts:monitor --once
```

---

## Supported Markets

### 💎 Cryptocurrency
- Major coins: BTC, ETH, BNB, ADA, XRP, etc.
- Tokens: SERPO, SHIB, DOGE, etc.
- 1000+ cryptocurrencies supported

### 📈 Stocks
- US stocks: AAPL, TSLA, GOOGL, MSFT, etc.
- All NYSE and NASDAQ stocks

### 💱 Forex
- Major pairs: EURUSD, GBPJPY, USDJPY, etc.
- 150+ currency pairs

---

## Architecture

### Components

1. **UniversalAlertMonitor** (`app/Services/UniversalAlertMonitor.php`)
   - Main monitoring service
   - Checks all active alerts
   - Triggers notifications

2. **MonitorAlerts** (`app/Console/Commands/MonitorAlerts.php`)
   - Console command
   - Continuous monitoring loop
   - Statistics display

3. **MultiMarketDataService** (`app/Services/MultiMarketDataService.php`)
   - Price fetching for all markets
   - Market type detection
   - API integration

4. **CommandHandler** (`app/Services/CommandHandler.php`)
   - Telegram command handling
   - User interaction
   - Alert management

### Data Flow

```
User → /setalert BTC 50000
         ↓
CommandHandler → Create Alert in DB
         ↓
Monitor Loop → Check Price Every 60s
         ↓
MultiMarketDataService → Fetch BTC Price
         ↓
Compare: Current vs Target
         ↓
If Triggered → Send Telegram Message
         ↓
Mark Alert as Triggered
```

---

## Database Schema

```sql
CREATE TABLE alerts (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    alert_type VARCHAR(255),
    condition VARCHAR(255),
    target_value DECIMAL(20,8),
    coin_symbol VARCHAR(255) DEFAULT 'SERPO',
    is_active BOOLEAN DEFAULT TRUE,
    is_triggered BOOLEAN DEFAULT FALSE,
    triggered_at TIMESTAMP NULL,
    message TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## API Rate Limits

| API | Market | Limit (Free) | Cost |
|-----|--------|--------------|------|
| Binance | Crypto | 1200/min | Free |
| Alpha Vantage | Forex/Stocks | 5/min | Free |
| DexScreener | Crypto | Reasonable use | Free |

**Recommendations:**
- Free tier: Monitor every 60 seconds (safe)
- Production: Upgrade Alpha Vantage to Premium ($50/month)
- Alternative: Use Polygon.io for stock data

---

## Troubleshooting

### Service won't start
```bash
# Check logs
sudo journalctl -u serpoai-alerts -n 50

# Test manually
cd /var/www/serpoai
sudo -u www-data php artisan alerts:monitor --once
```

### Prices not fetching
```bash
# Test specific symbol
php artisan tinker
>>> app(App\Services\MultiMarketDataService::class)->getCurrentPrice('BTC')
```

### Alerts not triggering
```bash
# Check active alerts
php artisan tinker
>>> App\Models\Alert::where('is_active', true)->count()

# Verify monitor is running
sudo systemctl status serpoai-alerts
```

---

## Performance

### Optimization Tips

1. **Cache Duration:** Prices cached for 60 seconds
2. **Check Interval:** 60 seconds recommended
3. **API Limits:** Group alerts by symbol
4. **Database:** Index on `user_id, is_active, coin_symbol`

### Scaling

For high volume:
- Upgrade to Alpha Vantage Premium
- Consider Redis for caching
- Use queue workers for notifications
- Implement alert priority levels

---

## Security

### Best Practices

1. **API Keys:** Store in `.env`, never commit
2. **Rate Limiting:** Respect API limits
3. **User Input:** Validated and sanitized
4. **Error Handling:** Graceful failures
5. **Logging:** No sensitive data in logs

---

## Support

### Documentation
- Full Guide: `UNIVERSAL_ALERTS_GUIDE.md`
- Implementation: `UNIVERSAL_ALERTS_IMPLEMENTATION.md`
- This README: Quick reference

### Testing
- Quick Test: `quick-alert-test.php`
- Full Test: `test-universal-alerts.php`

### Commands
```bash
php artisan alerts:monitor --help
```

---

## Version History

### v1.0.0 (January 31, 2026)
- Initial release
- Multi-market support (crypto, forex, stocks)
- Telegram integration
- Automated monitoring
- Statistics tracking

---

**Status:** ✅ Production Ready  
**Last Updated:** January 31, 2026  
**Author:** SerpoAI Development Team
