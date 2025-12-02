#!/bin/bash
# Docker Installation Script for Ubuntu Server
# Run with: bash install-docker.sh

echo "🐋 Installing Docker on Ubuntu Server..."
echo ""

# Update system
echo "📦 Updating system packages..."
apt update && apt upgrade -y

# Install prerequisites
echo "📦 Installing prerequisites..."
apt install -y ca-certificates curl gnupg

# Add Docker GPG key
echo "🔑 Adding Docker GPG key..."
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg

# Add Docker repository
echo "📦 Adding Docker repository..."
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null

# Update package list
echo "📦 Updating package list..."
apt update

# Install Docker
echo "🐋 Installing Docker Engine..."
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Start and enable Docker
echo "🚀 Starting Docker service..."
systemctl start docker
systemctl enable docker

# Verify installation
echo ""
echo "✅ Docker installation complete!"
echo ""
docker --version
docker compose version

# Test Docker
echo ""
echo "🧪 Testing Docker installation..."
docker run hello-world

echo ""
echo "✅ Docker is ready to use!"
echo ""
echo "📝 Useful commands:"
echo "  docker ps                  # List running containers"
echo "  docker images              # List images"
echo "  docker compose up -d       # Start services from docker-compose.yml"
echo "  systemctl status docker    # Check Docker service status"
