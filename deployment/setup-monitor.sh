#!/bin/bash
# SERPO Monitor Deployment Script
# Run this on your production server (ai.serpocoin.io)

echo "🚀 Setting up SERPO Token Event Monitor as a system service..."

# Copy service file
sudo cp /var/www/SerpoAI/deployment/serpo-monitor.service /etc/systemd/system/

# Reload systemd
sudo systemctl daemon-reload

# Enable service to start on boot
sudo systemctl enable serpo-monitor

# Start the service
sudo systemctl start serpo-monitor

# Check status
sudo systemctl status serpo-monitor

echo ""
echo "✅ Monitor service installed!"
echo ""
echo "📝 Useful commands:"
echo "  Start:   sudo systemctl start serpo-monitor"
echo "  Stop:    sudo systemctl stop serpo-monitor"
echo "  Restart: sudo systemctl restart serpo-monitor"
echo "  Status:  sudo systemctl status serpo-monitor"
echo "  Logs:    sudo journalctl -u serpo-monitor -f"
echo ""
echo "📊 Monitor logs are also saved to:"
echo "  /var/www/SerpoAI/storage/logs/monitor.log"
echo "  /var/www/SerpoAI/storage/logs/monitor-error.log"
