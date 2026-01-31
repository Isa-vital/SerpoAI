# Universal Alerts System Guide

## Overview

The Universal Alerts System allows users to set price alerts for **all three markets**:
- 💎 **Cryptocurrency** (Bitcoin, Ethereum, SERPO, etc.)
- 💱 **Forex** (EURUSD, GBPJPY, etc.)
- 📈 **Stocks** (AAPL, TSLA, GOOGL, etc.)

Users can set custom price targets and receive Telegram notifications when prices reach their thresholds.

---

## User Commands

### `/setalert [SYMBOL] [PRICE]`

Set a price alert for any symbol across all markets.

**Format:**
```
/setalert [SYMBOL] [PRICE]
```

**Examples:**

**Crypto:**
```
/setalert BTC 50000
/setalert ETH 3000
/setalert SERPO 0.00005
```

**Stocks:**
```
/setalert AAPL 180
/setalert TSLA 250
/setalert GOOGL 150
```

**Forex:**
```
/setalert EURUSD 1.10
/setalert GBPJPY 190.50
```

**Backward Compatibility:**
```
/setalert 0.00001
```
*Defaults to SERPO for backward compatibility*

---

### `/myalerts`

View all your active alerts with market icons.

**Response Format:**
```
🔔 Your Active Alerts

💎 BTC Above $50,000.00
📈 AAPL Above $180.00
💱 EURUSD Above $1.1000
💎 SERPO Above $0.00005000

Total: 4 alerts
```

---

### `/alerts`

Check alert system status and get usage examples.

**Response:**
```
🔔 Alert System Status

✅ System Active
📊 Supported Markets:
   💎 Crypto • 💱 Forex • 📈 Stocks

Set custom alerts:
/setalert [SYMBOL] [PRICE]

Examples:
• /setalert BTC 50000
• /setalert AAPL 180
• /setalert EURUSD 1.10
```

---

## Alert Monitoring

### Running the Monitor

The alert monitor continuously checks prices and triggers alerts.

**Start Monitor:**
```bash
php artisan alerts:monitor
```

**Options:**
```bash
# Set custom check interval (default: 60 seconds)
php artisan alerts:monitor --interval=30

# Run once instead of continuous loop
php artisan alerts:monitor --once

# Clean up old triggered alerts (7+ days)
php artisan alerts:monitor --cleanup
```

**Monitor Output:**
```
🔔 Starting Universal Alert Monitor...
📊 Monitoring: Crypto (💎) • Forex (💱) • Stocks (📈)
⏰ Check interval: 60 seconds

📈 Alert Statistics:
   Active Alerts: 15
   Triggered Today: 3
   By Market:
      • BTC: 5
      • AAPL: 3
      • EURUSD: 2
      • SERPO: 5

🔄 Starting continuous monitoring (Press Ctrl+C to stop)...

[Check #1] 2026-01-31 06:42:48
✅ Check completed

⏳ Next check in 60 seconds...
```

---

## Technical Implementation

### Service: `UniversalAlertMonitor`

**Location:** `app/Services/UniversalAlertMonitor.php`

**Key Methods:**

1. **`checkAllAlerts()`**
   - Fetches all active, untriggered alerts
   - Groups by symbol to minimize API calls
   - Checks each alert against current price
   - Triggers alerts that meet conditions

2. **`checkAlert(Alert $alert, float $currentPrice)`**
   - Evaluates alert condition
   - Supports: `above`, `below`, `crosses_above`, `crosses_below`
   - Triggers notification if condition met

3. **`triggerAlert(Alert $alert, float $currentPrice)`**
   - Formats alert message with market icon
   - Sends Telegram notification to user
   - Marks alert as triggered with timestamp
   - Logs event

4. **`cleanupOldAlerts(int $daysOld = 7)`**
   - Removes triggered alerts older than 7 days
   - Keeps database clean

5. **`getAlertStats()`**
   - Returns active alert count
   - Shows alerts triggered today
   - Groups by symbol/market

---

### Command: `MonitorAlerts`

**Location:** `app/Console/Commands/MonitorAlerts.php`

**Signature:** `alerts:monitor`

**Workflow:**
1. Display startup info and statistics
2. Optional: Clean up old alerts
3. Enter continuous monitoring loop
4. Every interval:
   - Check all active alerts
   - Trigger notifications
   - Log results
   - Sleep until next check

---

### Price Fetching: `MultiMarketDataService::getCurrentPrice()`

**Location:** `app/Services/MultiMarketDataService.php`

**Flow:**
1. Detect market type from symbol
2. Route to appropriate price fetcher:
   - **Crypto:** `getCryptoPrice()` → Binance API
   - **Forex:** `getForexPrice()` → Alpha Vantage API
   - **Stock:** `getStockPrice()` → Alpha Vantage API
3. Cache result for 60 seconds
4. Return price or null on error

**Special Handling:**
- **SERPO:** Fetches from DexScreener via `MarketDataService`
- **Crypto:** Auto-appends USDT if no quote currency
- **Forex:** Validates 6-character currency pair format

---

## Database Schema

### Table: `alerts`

```sql
CREATE TABLE alerts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    alert_type VARCHAR(255) NOT NULL,
    condition VARCHAR(255) NOT NULL,
    target_value DECIMAL(20,8) NOT NULL,
    coin_symbol VARCHAR(255) DEFAULT 'SERPO',
    is_active BOOLEAN DEFAULT TRUE,
    is_triggered BOOLEAN DEFAULT FALSE,
    triggered_at TIMESTAMP NULL,
    message TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_symbol_type (coin_symbol, alert_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Fields:**
- `user_id`: Telegram user ID (foreign key)
- `alert_type`: Type of alert (e.g., 'price')
- `condition`: Condition type ('above', 'below', 'crosses_above', 'crosses_below')
- `target_value`: Target price (up to 8 decimals for crypto precision)
- `coin_symbol`: Asset symbol (BTC, AAPL, EURUSD, SERPO)
- `is_active`: Alert is active and should be checked
- `is_triggered`: Alert has been triggered
- `triggered_at`: Timestamp when alert was triggered
- `message`: Formatted alert message sent to user

---

## Alert Message Format

When an alert triggers, users receive a formatted Telegram message:

```
🔔 *PRICE ALERT TRIGGERED*

💎 *BTC* went above your target!

🎯 Target: $82,746.97
💰 Current: $83,746.97
📈 Difference: $1,000.00 (1.21%)

_Alert ID: 7_
_Triggered at: 2026-01-31 06:42:48 UTC_
```

**Components:**
- Market icon (💎 crypto, 💱 forex, 📈 stocks)
- Symbol name
- Condition text ("went above", "went below", etc.)
- Target price
- Current price
- Difference (amount and percentage)
- Alert ID (for reference)
- Trigger timestamp (UTC)

---

## API Requirements

### Required APIs

1. **Binance API** (Crypto)
   - Endpoint: `https://api.binance.com/api/v3/ticker/24hr`
   - Rate Limit: 1200 requests/minute
   - No API key required for public endpoints

2. **Alpha Vantage API** (Forex & Stocks)
   - Endpoint: `https://www.alphavantage.co/query`
   - Rate Limit: 5 requests/minute (free tier)
   - API Key Required: Set in `.env`
   ```env
   ALPHA_VANTAGE_API_KEY=your_key_here
   ```

3. **DexScreener API** (SERPO)
   - Endpoint: `https://api.dexscreener.com/latest/dex/tokens/{address}`
   - Rate Limit: Public API, reasonable use
   - No API key required

### Configuration

**`.env` file:**
```env
# Alpha Vantage (for stocks & forex)
ALPHA_VANTAGE_API_KEY=your_key_here

# Polygon.io (alternative stock data)
POLYGON_API_KEY=your_key_here

# CoinGecko (additional crypto data)
COINGECKO_API_KEY=your_key_here
```

---

## Testing

### Test Script: `test-universal-alerts.php`

**Run:**
```bash
php test-universal-alerts.php
```

**What it does:**
1. Creates test user
2. Fetches current prices for BTC, ETH, AAPL, EURUSD, SERPO
3. Creates test alerts (some should trigger, some shouldn't)
4. Runs alert monitor
5. Checks which alerts triggered
6. Displays alert messages
7. Shows statistics

**Expected Output:**
```
🧪 Testing Universal Alert System
═══════════════════════════════════════════════════

1️⃣ Setting up test user...
✅ Test user ID: 50

2️⃣ Fetching current prices...
   💎 BTC: $83,746.97
   💎 ETH: $2,689.56
   📈 AAPL: $259.48
   💱 EURUSD: $1.1891
   💎 SERPO: $0.00002414

...

📊 Summary:
   ✅ Triggered: 4
   ❌ Not Triggered: 1

✅ Test completed!
```

---

## Deployment

### Production Setup

1. **Start Alert Monitor Service**

Create systemd service:

```bash
# /etc/systemd/system/serpoai-alerts.service
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

2. **Enable and Start Service**
```bash
sudo systemctl daemon-reload
sudo systemctl enable serpoai-alerts
sudo systemctl start serpoai-alerts
sudo systemctl status serpoai-alerts
```

3. **Monitor Logs**
```bash
# Service logs
sudo journalctl -u serpoai-alerts -f

# Laravel logs
tail -f storage/logs/laravel.log | grep -i alert
```

4. **Set Up Cleanup Cron**

Add to crontab:
```bash
# Clean up old alerts daily at 3 AM
0 3 * * * cd /var/www/serpoai && php artisan alerts:monitor --once --cleanup
```

---

## Performance Considerations

### Caching Strategy

- **Price Cache:** 60 seconds
  - Reduces API calls
  - Balances freshness vs. load
  - Cache key: `current_price_{SYMBOL}`

### API Rate Limits

**Binance (Crypto):**
- Limit: 1200 requests/minute
- Strategy: Batch check by grouping same-symbol alerts

**Alpha Vantage (Stocks & Forex):**
- Limit: 5 requests/minute (free tier)
- Strategy: Cache aggressively, consider upgrading for production

**Recommendations:**
- Monitor with 60-second intervals (safe for all APIs)
- Upgrade to Alpha Vantage Premium ($50/month) for higher limits
- Consider Polygon.io as alternative for stock data

---

## Monitoring & Logging

### Log Events

**Alert Check Started:**
```
[INFO] 🔔 Starting universal alert check...
```

**Price Fetched:**
```
[INFO] Current BTC price: $83746.97
```

**Alert Triggered:**
```
[INFO] Alert triggered and sent
{
    "alert_id": 7,
    "symbol": "BTC",
    "condition": "above",
    "target": 82746.97,
    "current": 83746.97
}
```

**Errors:**
```
[ERROR] Error checking universal alerts
{
    "error": "Could not fetch price for AAPL",
    "trace": "..."
}
```

### Metrics to Track

- Active alerts count
- Alerts triggered per day
- API call success rate
- Average response time
- Failed price fetches

---

## Troubleshooting

### Common Issues

**1. Alert not triggering**
- Check if alert is active: `Alert::where('id', X)->first()`
- Verify price fetch works: `php artisan tinker` → `app(MultiMarketDataService::class)->getCurrentPrice('BTC')`
- Check monitor is running: `sudo systemctl status serpoai-alerts`

**2. Wrong prices**
- Clear cache: `php artisan cache:clear`
- Check API keys are configured
- Verify API rate limits not exceeded

**3. No Telegram notification**
- Check bot token in `.env`
- Verify user_id exists in database
- Test bot directly: `/start` command

**4. Monitor crashes**
- Check logs: `sudo journalctl -u serpoai-alerts -n 100`
- Verify database connection
- Ensure all dependencies installed

---

## Future Enhancements

### Planned Features

1. **Multiple Conditions per Alert**
   - AND/OR logic
   - Range alerts (between X and Y)

2. **Alert Templates**
   - Common setups (breakout, support/resistance)
   - One-click creation

3. **Alert History**
   - Track past triggers
   - Performance analytics

4. **Delete/Pause Alerts**
   - `/deletealert [ID]`
   - `/pausealert [ID]`

5. **Advanced Conditions**
   - Percentage change (e.g., "alert if BTC moves 5% in 1 hour")
   - Volume alerts
   - Moving average crossovers

6. **Push Notifications**
   - Mobile app integration
   - Email notifications
   - SMS alerts (premium feature)

---

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Run test: `php test-universal-alerts.php`
- Contact: @SerpoAI_Support

---

**Version:** 1.0.0  
**Last Updated:** January 31, 2026  
**Author:** SerpoAI Development Team
