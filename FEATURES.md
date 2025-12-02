# SerpoAI Bot - New Features Documentation

## Overview
SerpoAI bot now includes two major features: **Portfolio Tracking** and **Token Event Alerts** to provide comprehensive SERPO token monitoring for users and the community.

---

## FEATURE 1: Portfolio Tracking 📊

### Description
Allows users to track their SERPO token holdings directly in Telegram with real-time balance and USD value calculations.

### Commands

#### `/addwallet <address>`
Register a TON wallet address to track SERPO holdings.

**Usage:**
```
/addwallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw
```

**Features:**
- Validates TON address format
- Supports multiple wallets per user
- Saves to user profile securely
- Confirms successful addition

**Response:**
```
✅ Wallet added successfully!

Address: EQCPeUz...HQyw

Use /portfolio to view your holdings.
```

---

#### `/portfolio`
View all your registered wallets and SERPO holdings.

**Usage:**
```
/portfolio
```

**Features:**
- Shows all registered wallets
- Displays SERPO balance for each wallet
- Calculates current USD value
- Shows total portfolio worth
- Updates in real-time

**Response Example:**
```
💼 Your SERPO Portfolio

📍 Wallet 1
Address: EQCPeUz...HQyw
Balance: 50,000 SERPO
Price: $0.0012
Value: $60.00

📍 Wallet 2
Address: EQAB...xyz
Balance: 25,000 SERPO
Price: $0.0012
Value: $30.00

━━━━━━━━━━━━━━━━━━━━
💰 Total Portfolio Value: $90.00

Last updated: 2 minutes ago
```

**If no wallets added:**
```
💼 Portfolio Tracking

You haven't added any wallets yet.

Add a wallet with:
/addwallet <your_wallet_address>

Example:
/addwallet EQCP...HQyw
```

---

#### `/removewallet <address>`
Remove a wallet from your portfolio tracking.

**Usage:**
```
/removewallet EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw
```

**Features:**
- Validates wallet exists in your profile
- Removes wallet from tracking
- Confirms deletion

**Response:**
```
✅ Wallet removed successfully!

You can add it back anytime with /addwallet
```

---

### Technical Implementation

**Data Flow:**
1. User sends `/addwallet <address>`
2. Bot validates TON address format
3. Saves to `user_wallets` database table
4. Confirms addition to user

**Portfolio Calculation:**
1. User sends `/portfolio`
2. Bot fetches all user wallets from database
3. For each wallet:
   - Calls TON API to get SERPO balance
   - Fetches current price from DexScreener
   - Calculates USD value
4. Sums total portfolio value
5. Formats and sends message

**APIs Used:**
- **TonAPI**: `https://tonapi.io/v2/accounts/{address}/jettons`
- **DexScreener**: `https://api.dexscreener.com/latest/dex/tokens/{contract}`

**Database Schema:**
```sql
user_wallets
- id (bigint, primary key)
- user_id (bigint, foreign key -> users.id)
- wallet_address (string, unique per user)
- label (string, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

---

## FEATURE 2: Token Event Alerts 🚨

### Description
24/7 monitoring of SERPO token on-chain activity with automatic alerts sent to the community channel (@serpocoinchannel).

### Events Monitored

#### 1. **Buy/Sell Transactions**
Detects when someone buys or sells SERPO tokens.

**Alert Example:**
```
🟢 BUY ALERT

Amount: 10,000 SERPO
Value: $12.50
Buyer: EQAB...xyz
Price: $0.00125
Timestamp: 2 mins ago

#SerpoBuy #TON
```

```
🔴 SELL ALERT

Amount: 5,000 SERPO
Value: $6.25
Seller: EQCD...abc
Price: $0.00125
Timestamp: 1 min ago

#SerpoSell #TON
```

---

#### 2. **Liquidity Changes**
Monitors liquidity pool additions and removals.

**Add Liquidity Alert:**
```
💧 LIQUIDITY ADDED

Pool: SERPO/TON
Amount: 100,000 SERPO
Value: $125.00
Provider: EQEF...def

Pool TVL: $50,000

#Liquidity #DeFi
```

**Remove Liquidity Alert:**
```
⚠️ LIQUIDITY REMOVED

Pool: SERPO/TON
Amount: 50,000 SERPO
Value: $62.50
Provider: EQGH...ghi

Remaining TVL: $49,875

#Liquidity #Alert
```

---

#### 3. **Price Movements**
Alerts on significant price changes.

**Pump Alert:**
```
🚀 PUMP ALERT

Price increased 8.5% in 5 minutes!

Previous: $0.00120
Current: $0.00130
Change: +$0.00010 (+8.5%)

Volume (5m): $2,450

#Pump #Bullish
```

**Dump Alert:**
```
📉 DUMP ALERT

Price decreased 6.2% in 5 minutes!

Previous: $0.00130
Current: $0.00122
Change: -$0.00008 (-6.2%)

Volume (5m): $1,850

#Dump #Alert
```

---

#### 4. **Holder Activity**
Tracks new holders and large transfers.

**New Holder Alert:**
```
👋 NEW HOLDER

Wallet: EQIJ...jkl
First Purchase: 15,000 SERPO
Value: $18.75

Total Holders: 1,247 (+1)

#NewHolder #Community
```

**Large Transfer Alert:**
```
🐋 WHALE ALERT

Amount: 500,000 SERPO
Value: $625.00

From: EQKL...mno
To: EQMN...pqr

#WhaleAlert #BigTransfer
```

---

#### 5. **Contract Events**
Monitors smart contract interactions.

**Burn Event:**
```
🔥 TOKEN BURN

Amount: 100,000 SERPO burned
Value: $125.00

Total Supply: 9,900,000 SERPO
Burned: 100,000 (1.0%)

#Burn #Deflationary
```

**Mint Event:**
```
⚡ MINTING EVENT

Amount: 50,000 SERPO minted
Recipient: EQOP...qrs

Total Supply: 10,050,000 SERPO

#Mint #Alert
```

---

### Configuration

**Environment Variables:**
```env
# Community Channel
COMMUNITY_CHANNEL_ID=-1001900124613

# Token Contract
SERPO_CONTRACT_ADDRESS=EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw

# API Keys
API_KEY_TON=your_tonapi_key_here
API_KEY_DEXSCREENER=optional_key

# Alert Settings
PRICE_CHANGE_THRESHOLD=5.0
LARGE_TRANSFER_THRESHOLD=100
CHECK_INTERVAL=30
```

**Configuration Options:**
- `PRICE_CHANGE_THRESHOLD`: Percentage change to trigger pump/dump alerts (default: 5%)
- `LARGE_TRANSFER_THRESHOLD`: USD value to trigger whale alerts (default: $100)
- `CHECK_INTERVAL`: Seconds between monitoring checks (default: 30s)

---

### Running the Monitor

**Start the monitoring service:**
```bash
php artisan monitor:token-events
```

**Run in background (production):**
```bash
# Using nohup
nohup php artisan monitor:token-events > storage/logs/monitor.log 2>&1 &

# Using screen
screen -dmS token-monitor php artisan monitor:token-events

# Using systemd (recommended)
sudo systemctl start serpo-monitor
```

**Stop the monitor:**
```bash
# Find process
ps aux | grep "monitor:token-events"

# Kill process
kill <PID>

# Or if using systemd
sudo systemctl stop serpo-monitor
```

---

### Technical Implementation

**Monitoring Loop:**
1. Every 30 seconds, fetch latest blockchain data
2. Query TON API for recent transactions
3. Query DexScreener for price/volume changes
4. Compare with last check (stored in database)
5. Identify new events
6. Format and send alerts to channel
7. Update `token_events` table with processed events
8. Sleep and repeat

**Event Detection Logic:**

**Buy/Sell:**
- Monitors token transfer transactions
- Identifies DEX router addresses
- Determines direction (buy vs sell)
- Calculates amounts and values

**Liquidity:**
- Tracks LP token mint/burn events
- Monitors pool reserve changes
- Calculates TVL impact

**Price Changes:**
- Fetches current price every check
- Compares with previous price
- Calculates percentage change
- Triggers alert if exceeds threshold

**Holders:**
- Tracks unique wallet addresses
- Identifies first-time SERPO holders
- Monitors large transfers (>threshold)

**Duplicate Prevention:**
- Stores event hash in `token_events` table
- Checks if event already processed
- Skips duplicate events

**Database Schema:**
```sql
token_events
- id (bigint, primary key)
- event_type (string: 'buy', 'sell', 'liquidity_add', etc.)
- event_hash (string, unique)
- transaction_hash (string)
- wallet_address (string, nullable)
- amount (decimal)
- value_usd (decimal)
- price (decimal)
- metadata (json)
- processed_at (timestamp)
- created_at (timestamp)
```

---

## API Integrations

### TON API
**Endpoint:** `https://tonapi.io/v2/`

**Used for:**
- Wallet balance queries
- Transaction history
- Token transfers
- Contract events

**Authentication:**
```
Header: Authorization: Bearer YOUR_API_KEY
```

---

### DexScreener API
**Endpoint:** `https://api.dexscreener.com/latest/dex/`

**Used for:**
- Real-time token prices
- Volume data
- Liquidity information
- Price history

**No authentication required** (rate limited)

---

## Testing

### Local Testing with Ngrok

1. **Start ngrok:**
```bash
ngrok http 8000
```

2. **Set webhook to ngrok URL:**
```powershell
Invoke-WebRequest -Method POST -Uri "https://api.telegram.org/bot{TOKEN}/setWebhook" -Body @{url="https://YOUR-NGROK-URL.ngrok.io/api/telegram/webhook"}
```

3. **Start Laravel server:**
```bash
php artisan serve
```

4. **Test portfolio commands:**
```
/addwallet <test_address>
/portfolio
/removewallet <test_address>
```

5. **Test monitoring (dry run):**
```bash
php artisan monitor:token-events --dry-run
```

---

## Production Deployment

### 1. Update Environment Variables
```bash
# On server
nano /var/www/SerpoAI/.env

# Add:
COMMUNITY_CHANNEL_ID=-1001900124613
SERPO_CONTRACT_ADDRESS=EQCPeUzKknneMlA1UbivELxd8lFUA_oaOX9m9PPc4d6lHQyw
API_KEY_TON=your_tonapi_key
PRICE_CHANGE_THRESHOLD=5.0
LARGE_TRANSFER_THRESHOLD=100
```

### 2. Run Migrations
```bash
cd /var/www/SerpoAI
php artisan migrate --force
```

### 3. Fix Nginx Timeouts
```bash
# Remove duplicate directives
sed -i '/fastcgi_read_timeout/d' /etc/nginx/nginx.conf

# Add timeout once
sed -i '/http {/a \    fastcgi_read_timeout 300;' /etc/nginx/nginx.conf

# Increase PHP timeouts
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini
sed -i 's/^;*request_terminate_timeout = .*/request_terminate_timeout = 300/' /etc/php/8.3/fpm/pool.d/www.conf

# Restart services
systemctl restart php8.3-fpm nginx
```

### 4. Set Production Webhook
```bash
curl -X POST "https://api.telegram.org/bot{TOKEN}/deleteWebhook?drop_pending_updates=true"
curl -X POST "https://api.telegram.org/bot{TOKEN}/setWebhook" -d "url=https://ai.serpocoin.io/api/telegram/webhook"
```

### 5. Start Token Monitor
```bash
# Create systemd service
sudo nano /etc/systemd/system/serpo-monitor.service
```

```ini
[Unit]
Description=SerpoAI Token Event Monitor
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/SerpoAI
ExecStart=/usr/bin/php artisan monitor:token-events
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start
sudo systemctl enable serpo-monitor
sudo systemctl start serpo-monitor

# Check status
sudo systemctl status serpo-monitor
```

---

## Troubleshooting

### Portfolio Commands Not Working

**Check:**
1. Wallet address format is valid TON address
2. TonAPI key is set in `.env`
3. Database migrations ran successfully
4. User is registered in `users` table

**Debug:**
```bash
# Check logs
tail -f storage/logs/laravel.log

# Test API manually
curl "https://tonapi.io/v2/accounts/{address}/jettons" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

---

### Token Alerts Not Sending

**Check:**
1. Monitor service is running: `systemctl status serpo-monitor`
2. Channel ID is correct in `.env`
3. Bot is admin in channel
4. API keys are valid

**Debug:**
```bash
# Check monitor logs
tail -f storage/logs/laravel.log | grep "Token event"

# Test channel access
php artisan tinker
>>> app(\App\Services\TelegramBotService::class)->sendMessage(env('COMMUNITY_CHANNEL_ID'), 'Test');
```

---

### Bot Timing Out

**Solution:**
Already implemented! The synchronous processing with 300-second timeouts should handle all API calls.

**If still timing out:**
1. Check nginx config: `grep fastcgi_read_timeout /etc/nginx/nginx.conf`
2. Check PHP config: `grep max_execution_time /etc/php/8.3/fpm/php.ini`
3. Restart services: `systemctl restart php8.3-fpm nginx`

---

## Monitoring & Logs

**View all logs:**
```bash
tail -f /var/www/SerpoAI/storage/logs/laravel.log
```

**View monitor logs only:**
```bash
tail -f /var/www/SerpoAI/storage/logs/laravel.log | grep "Token event\|Portfolio"
```

**View systemd service logs:**
```bash
journalctl -u serpo-monitor -f
```

---

## Future Enhancements

- [ ] Price alerts: `/setalert <price>` to notify when price reaches target
- [ ] Portfolio analytics: Charts showing balance history
- [ ] Multi-chain support: Track SERPO on other chains
- [ ] Wallet labels: `/labelwallet <address> <name>`
- [ ] Export portfolio to CSV
- [ ] Webhook for real-time events (instead of polling)
- [ ] Historical data visualization

---

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review configuration: `.env` file
- Test APIs manually with curl
- Check systemd service status

**Contact:** Your Telegram channel @serpocoinchannel
