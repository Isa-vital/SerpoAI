#!/bin/bash

#############################################
# Universal Alerts System Deployment Script
#############################################

echo "╔════════════════════════════════════════╗"
echo "║  Universal Alerts System Deployment    ║"
echo "╚════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}✗ Please run as root (sudo)${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Running as root${NC}"
echo ""

# Configuration
APP_DIR="/var/www/serpoai"
SERVICE_NAME="serpoai-alerts"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"

echo "📁 Application Directory: $APP_DIR"
echo "🔧 Service Name: $SERVICE_NAME"
echo ""

# Step 1: Check if app directory exists
echo "Step 1: Checking application directory..."
if [ ! -d "$APP_DIR" ]; then
    echo -e "${RED}✗ Application directory not found: $APP_DIR${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Application directory exists${NC}"
echo ""

# Step 2: Check required files
echo "Step 2: Checking required files..."
REQUIRED_FILES=(
    "app/Services/UniversalAlertMonitor.php"
    "app/Console/Commands/MonitorAlerts.php"
    "app/Services/MultiMarketDataService.php"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$APP_DIR/$file" ]; then
        echo -e "${RED}✗ Missing file: $file${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ $file${NC}"
done
echo ""

# Step 3: Check .env configuration
echo "Step 3: Checking .env configuration..."
if [ ! -f "$APP_DIR/.env" ]; then
    echo -e "${RED}✗ .env file not found${NC}"
    exit 1
fi

# Check for Alpha Vantage API key
if grep -q "ALPHA_VANTAGE_API_KEY=" "$APP_DIR/.env"; then
    ALPHA_KEY=$(grep "ALPHA_VANTAGE_API_KEY=" "$APP_DIR/.env" | cut -d '=' -f2)
    if [ -z "$ALPHA_KEY" ] || [ "$ALPHA_KEY" = "your_key_here" ]; then
        echo -e "${YELLOW}⚠ Alpha Vantage API key not configured${NC}"
        echo -e "${YELLOW}  Forex and Stock alerts will not work!${NC}"
        echo -e "${YELLOW}  Get free key: https://www.alphavantage.co/support/#api-key${NC}"
    else
        echo -e "${GREEN}✓ Alpha Vantage API key configured${NC}"
    fi
else
    echo -e "${YELLOW}⚠ ALPHA_VANTAGE_API_KEY not found in .env${NC}"
fi
echo ""

# Step 4: Test artisan command
echo "Step 4: Testing artisan command..."
cd "$APP_DIR" || exit 1
sudo -u www-data php artisan alerts:monitor --once > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Artisan command works${NC}"
else
    echo -e "${RED}✗ Artisan command failed${NC}"
    echo -e "${YELLOW}  Run manually: cd $APP_DIR && sudo -u www-data php artisan alerts:monitor --once${NC}"
    exit 1
fi
echo ""

# Step 5: Create systemd service
echo "Step 5: Creating systemd service..."
cat > "$SERVICE_FILE" << EOF
[Unit]
Description=SerpoAI Universal Alert Monitor
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php $APP_DIR/artisan alerts:monitor --interval=60
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Service file created: $SERVICE_FILE${NC}"
else
    echo -e "${RED}✗ Failed to create service file${NC}"
    exit 1
fi
echo ""

# Step 6: Reload systemd
echo "Step 6: Reloading systemd..."
systemctl daemon-reload
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Systemd reloaded${NC}"
else
    echo -e "${RED}✗ Failed to reload systemd${NC}"
    exit 1
fi
echo ""

# Step 7: Enable service
echo "Step 7: Enabling service..."
systemctl enable "$SERVICE_NAME"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Service enabled (will start on boot)${NC}"
else
    echo -e "${YELLOW}⚠ Failed to enable service${NC}"
fi
echo ""

# Step 8: Start service
echo "Step 8: Starting service..."
systemctl start "$SERVICE_NAME"
sleep 2
if systemctl is-active --quiet "$SERVICE_NAME"; then
    echo -e "${GREEN}✓ Service started successfully${NC}"
else
    echo -e "${RED}✗ Service failed to start${NC}"
    echo -e "${YELLOW}  Check logs: sudo journalctl -u $SERVICE_NAME -n 50${NC}"
    exit 1
fi
echo ""

# Step 9: Create cleanup cron job
echo "Step 9: Setting up cleanup cron job..."
CRON_JOB="0 3 * * * cd $APP_DIR && /usr/bin/php artisan alerts:monitor --once --cleanup >> /var/log/serpoai-alerts-cleanup.log 2>&1"
(crontab -l 2>/dev/null | grep -v "alerts:monitor --once --cleanup"; echo "$CRON_JOB") | crontab -
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Cleanup cron job created (daily at 3 AM)${NC}"
else
    echo -e "${YELLOW}⚠ Failed to create cron job${NC}"
fi
echo ""

# Final status check
echo "═══════════════════════════════════════════════════"
echo "📊 Final Status Check"
echo "═══════════════════════════════════════════════════"
echo ""

# Service status
if systemctl is-active --quiet "$SERVICE_NAME"; then
    echo -e "${GREEN}✓ Service Status: RUNNING${NC}"
    
    # Get statistics
    echo ""
    echo "📈 Alert Statistics:"
    cd "$APP_DIR" || exit 1
    sudo -u www-data php artisan alerts:monitor --once 2>&1 | grep -A 10 "Alert Statistics"
else
    echo -e "${RED}✗ Service Status: STOPPED${NC}"
fi

echo ""
echo "═══════════════════════════════════════════════════"
echo "✅ Deployment Complete!"
echo "═══════════════════════════════════════════════════"
echo ""

# Show useful commands
echo "📝 Useful Commands:"
echo ""
echo "Check service status:"
echo "   sudo systemctl status $SERVICE_NAME"
echo ""
echo "View live logs:"
echo "   sudo journalctl -u $SERVICE_NAME -f"
echo ""
echo "Restart service:"
echo "   sudo systemctl restart $SERVICE_NAME"
echo ""
echo "Stop service:"
echo "   sudo systemctl stop $SERVICE_NAME"
echo ""
echo "View Laravel logs:"
echo "   tail -f $APP_DIR/storage/logs/laravel.log | grep -i alert"
echo ""
echo "Run manual check:"
echo "   cd $APP_DIR && php artisan alerts:monitor --once"
echo ""

# API key reminder
if grep -q "ALPHA_VANTAGE_API_KEY=" "$APP_DIR/.env"; then
    ALPHA_KEY=$(grep "ALPHA_VANTAGE_API_KEY=" "$APP_DIR/.env" | cut -d '=' -f2)
    if [ -z "$ALPHA_KEY" ] || [ "$ALPHA_KEY" = "your_key_here" ]; then
        echo "═══════════════════════════════════════════════════"
        echo -e "${YELLOW}⚠  IMPORTANT: Configure Alpha Vantage API Key${NC}"
        echo "═══════════════════════════════════════════════════"
        echo ""
        echo "To enable Forex and Stock alerts:"
        echo "1. Get free API key: https://www.alphavantage.co/support/#api-key"
        echo "2. Edit .env: nano $APP_DIR/.env"
        echo "3. Set: ALPHA_VANTAGE_API_KEY=your_actual_key"
        echo "4. Restart service: sudo systemctl restart $SERVICE_NAME"
        echo ""
    fi
fi

echo "🎉 Universal Alerts System is ready!"
echo ""
