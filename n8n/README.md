# n8n Workflows for SerpoAI

This directory contains n8n workflow templates for automating SerpoAI tasks.

## Workflows

### 1. Price Fetcher (`price-fetcher.json`)
**Purpose**: Fetches SERPO token price from DexScreener every 5 minutes

**Nodes**:
- Schedule Trigger (Every 5 minutes)
- HTTP Request to DexScreener API
- Code node to parse response
- MySQL Insert to store data

**Setup**:
1. Import workflow to n8n
2. Configure MySQL credentials
3. Set environment variables:
   - `DEXSCREENER_API_URL`
   - `SERPO_CONTRACT_ADDRESS`
4. Activate workflow

### 2. Alert Checker (`alert-checker.json`)
**Purpose**: Checks active alerts and sends Telegram notifications

**Nodes**:
- Schedule Trigger (Every 2 minutes)
- MySQL Select to get active alerts
- HTTP Request to get current price
- Code node to check conditions
- HTTP Request to send Telegram message
- MySQL Update to mark alert as triggered

**Setup**:
1. Import workflow to n8n
2. Configure MySQL credentials
3. Set environment variables:
   - `TELEGRAM_BOT_TOKEN`
   - `DEXSCREENER_API_URL`
   - `SERPO_CONTRACT_ADDRESS`
4. Activate workflow

## Installation

### n8n Setup

1. **Install n8n**:
   ```bash
   npm install -g n8n
   ```

2. **Start n8n**:
   ```bash
   n8n start
   ```

3. **Access n8n**:
   Open `http://localhost:5678`

4. **Import workflows**:
   - Click "Workflows" → "Import from File"
   - Select workflow JSON files
   - Configure credentials

### Environment Variables

Set these in n8n settings or `.env`:

```env
DEXSCREENER_API_URL=https://api.dexscreener.com/latest
SERPO_CONTRACT_ADDRESS=your_contract_address
TELEGRAM_BOT_TOKEN=your_bot_token
```

### MySQL Credentials

Create MySQL credential in n8n:
- Host: `127.0.0.1`
- Database: `serpoai_db`
- User: `root`
- Password: your_password

## Future Workflows

### Signal Generator (Coming Soon)
- Calculate technical indicators (RSI, MACD, EMA)
- Generate buy/sell signals
- Store in signals table

### Sentiment Analyzer (Coming Soon)
- Scrape Twitter/Reddit mentions
- Calculate sentiment score
- Store sentiment data

### Portfolio Tracker (Coming Soon)
- Track user portfolio values
- Calculate P&L
- Send daily summaries

## Workflow Diagram

```
┌─────────────────┐
│  Price Fetcher  │ ─── Every 5 min
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Market Data DB  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Alert Checker   │ ─── Every 2 min
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Telegram Alerts │
└─────────────────┘
```

## Troubleshooting

### Workflow not triggering
- Check if workflow is activated
- Verify schedule trigger settings
- Check n8n logs: `n8n start --verbose`

### Database connection errors
- Verify MySQL credentials
- Check if database exists
- Test connection from n8n

### API errors
- Verify API URLs and keys
- Check rate limits
- Monitor n8n execution logs

## Resources

- [n8n Documentation](https://docs.n8n.io/)
- [n8n Community](https://community.n8n.io/)
- [Workflow Templates](https://n8n.io/workflows/)
