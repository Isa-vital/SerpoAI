# SerpoAI Webhook Setup Script
# Usage: Edit the BOT_TOKEN and NGROK_URL, then run this script

# STEP 1: Replace with your actual Telegram bot token from .env
$BOT_TOKEN = "YOUR_BOT_TOKEN_HERE"

# STEP 2: Replace with your ngrok HTTPS URL (e.g., https://abc123.ngrok.io)
$NGROK_URL = "https://YOUR_NGROK_URL.ngrok.io"

# Construct webhook URL
$WEBHOOK_URL = "$NGROK_URL/api/telegram/webhook"

Write-Host "Setting webhook..." -ForegroundColor Yellow
Write-Host "Webhook URL: $WEBHOOK_URL" -ForegroundColor Cyan

# Set webhook
$response = Invoke-RestMethod -Uri "https://api.telegram.org/bot$BOT_TOKEN/setWebhook" -Method Post -Body @{
    url = $WEBHOOK_URL
}

Write-Host "`nResponse:" -ForegroundColor Green
$response | ConvertTo-Json

Write-Host "`nChecking webhook info..." -ForegroundColor Yellow
$info = Invoke-RestMethod -Uri "https://api.telegram.org/bot$BOT_TOKEN/getWebhookInfo"
$info | ConvertTo-Json

Write-Host "`n✅ Done! Now test your bot in Telegram!" -ForegroundColor Green
Write-Host "Send /start to your bot" -ForegroundColor Cyan
