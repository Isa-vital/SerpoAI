#!/bin/bash

# Create deployment user for SerpoAI
# Run this script as root on the production server

set -e

echo "🔧 Creating deployment user..."

# Create user (you can change 'deploy' to any username you prefer)
adduser deploy

echo "✅ User 'deploy' created"

# Add to sudoers group for admin tasks
usermod -aG sudo deploy

echo "✅ User 'deploy' added to sudo group"

# Set up SSH directory for the new user
mkdir -p /home/deploy/.ssh
chmod 700 /home/deploy/.ssh

echo "✅ SSH directory created"

# Optional: Copy authorized_keys from root (if you want to login with same key)
if [ -f /root/.ssh/authorized_keys ]; then
    cp /root/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys
    chown -R deploy:deploy /home/deploy/.ssh
    chmod 600 /home/deploy/.ssh/authorized_keys
    echo "✅ SSH keys copied from root"
fi

# Set proper ownership for web directory
chown -R deploy:www-data /var/www/SerpoAI
chmod -R 755 /var/www/SerpoAI

echo "✅ Web directory ownership set"

echo ""
echo "🎉 Deployment user setup complete!"
echo ""
echo "Next steps:"
echo "1. Set a password for the user: passwd deploy"
echo "2. Test login: su - deploy"
echo "3. Or from local machine: ssh deploy@72.62.43.137"
echo ""
echo "⚠️  For better security, consider:"
echo "   - Disabling root SSH login"
echo "   - Using SSH keys instead of password"
echo "   - Setting up a firewall (ufw)"
