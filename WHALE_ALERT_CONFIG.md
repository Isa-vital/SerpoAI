# 🐋 Whale Alert Configuration

## ✅ Configuration Complete

Your SERPO whale alert system is now properly configured to detect individual transactions of **20+ TON ($100+ USD)**.

### Configured Addresses:

**Jetton Master Contract (SERPO Token):**
```
EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw
```

**DEX Pool Contract (STON.fi):**
```
EQAKSRrtI6SNsGeQ0N4d7DQtt7GPfMH72QQ4K1SLCorG0Dwc
```

**DEX:** STON.fi  
**Trading Pair:** SERPO/TON

---

## 🎯 How Whale Alerts Work Now

### 1. **General Activity Alerts** (Current - Always Active)
- **Source:** DexScreener API aggregated data
- **Frequency:** Every 60 seconds
- **Detection:** Analyzes 24h buy/sell volume and transaction counts
- **Trigger:** Significant trading activity ($100+ volume)
- **Cooldown:** 30 minutes between alerts

### 2. **Individual Whale Transaction Alerts** (Now Enabled!)
- **Source:** TON API - Direct blockchain monitoring
- **Target:** STON.fi DEX pool contract
- **Detection:** Individual `JettonSwap` events
- **Threshold:** 20+ TON value ($100+ USD)
- **Real-time:** Detects each transaction separately
- **Accuracy:** Knows exact buy/sell direction

---

## 📊 Alert Types

### 🟢 Regular Buy Alert
```
🟢 SERPO BUY ACTIVITY!

📊 24h Trading Activity:
🔥 Buy Transactions: 55
💰 24h Volume: $2,400.00
📈 Avg Buy Size: ~0.8 TON
```

### 🐋 Whale Alert (NEW - Individual Transaction)
```
🐋 WHALE ALERT!

💎 SERPO Token
📊 Transaction Details:
👤 Buyer: EQxx...xxxx
💰 Amount: 500,000 SERPO
💵 Value: ~25 TON ($129.00)
🔗 View Transaction
```

---

## 🔍 Verification

You can verify the configuration:

```bash
# Check config
php artisan tinker --execute="
  echo 'Contract: ' . config('services.serpo.contract_address') . PHP_EOL;
  echo 'DEX Pair: ' . config('services.serpo.dex_pair_address') . PHP_EOL;
"

# View on blockchain explorer
https://tonviewer.com/EQAKSRrtI6SNsGeQ0N4d7DQtt7GPfMH72QQ4K1SLCorG0Dwc

# View on DexScreener
https://dexscreener.com/ton/EQAKSRrtI6SNsGeQ0N4d7DQtt7GPfMH72QQ4K1SLCorG0Dwc
```

---

## 🚀 Testing Whale Alerts

To test the enhanced whale detection:

```bash
# Run the token monitor manually
php artisan serpo:monitor

# Watch logs for whale detection
tail -f storage/logs/laravel.log | grep -i whale
```

---

## ⚙️ Configuration Files Updated

1. **`.env`** - Added `SERPO_DEX_PAIR_ADDRESS`
2. **`.env.production.example`** - Added example for production
3. **`app/Services/TokenEventMonitor.php`** - Enhanced with `checkDexPoolTransactions()` method

---

## 📈 Current Thresholds

| Type | Threshold | Description |
|------|-----------|-------------|
| **Whale Alert** | 20+ TON | Individual transactions worth $100+ USD |
| **Large Transfer** | 10,000+ SERPO | Token transfers (fallback) |
| **Regular Buy Alert** | $100+ volume | General trading activity |
| **Price Change** | ±5% | Significant price movements |
| **Liquidity Change** | ±10% | Pool liquidity changes |

---

## 🔧 Customization

To adjust the whale threshold, edit `app/Services/TokenEventMonitor.php`:

```php
// Current: 20 TON = ~$100 USD
private const LARGE_TRADE_TON = 20.0;

// Example: Change to 50 TON = ~$250 USD
private const LARGE_TRADE_TON = 50.0;
```

---

## 🐛 Troubleshooting

### Whale alerts not appearing?

1. **Check TON API Key:**
   ```bash
   php artisan tinker --execute="echo config('services.ton.api_key');"
   ```

2. **Verify DEX pool address:**
   ```bash
   curl "https://tonapi.io/v2/blockchain/accounts/EQAKSRrtI6SNsGeQ0N4d7DQtt7GPfMH72QQ4K1SLCorG0Dwc/events?limit=5"
   ```

3. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Test configuration:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Only seeing general alerts, not individual whales?

This is normal if there are no 20+ TON transactions. The system will:
- Show regular buy activity alerts for general trading
- Show whale alerts ONLY when individual transactions ≥ 20 TON occur

---

## 📞 Support

**Configured by:** GitHub Copilot  
**Date:** December 3, 2025  
**Status:** ✅ Active and Monitoring

For issues, check:
1. `storage/logs/laravel.log` - Error messages
2. TON API rate limits
3. DexScreener API availability

---

## 🎉 Summary

Your whale alert system now has **dual monitoring**:

1. **DexScreener** → General trading activity (always active)
2. **TON API + DEX Pool** → Individual whale transactions (now enabled)

The system will automatically send 🐋 alerts when any single transaction of 20+ TON ($100+ USD) is detected on the STON.fi pool!
