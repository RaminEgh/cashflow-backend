#!/bin/bash

# phpMyAdmin Auto-Installation Script for Ubuntu 22.04
# This script automates the installation and configuration of phpMyAdmin

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration variables
PMA_DOMAIN="${1:-pma.abc.ir}"  # First argument or default
PMA_PASSWORD="${2:-PhpMyAdmin2024!@#}"  # Second argument or default
DB_ROOT_PASSWORD=""  # Will be prompted

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}phpMyAdmin Auto-Installation Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

# Prompt for MariaDB root password
read -sp "Enter MariaDB root password: " DB_ROOT_PASSWORD
echo ""

# Validate inputs
if [ -z "$PMA_DOMAIN" ]; then
    echo -e "${RED}Error: Domain name is required${NC}"
    echo "Usage: $0 <domain> [password]"
    echo "Example: $0 pma.abc.ir PhpMyAdmin2024!@#"
    exit 1
fi

echo -e "${YELLOW}Configuration:${NC}"
echo "  Domain: $PMA_DOMAIN"
echo "  Password: [HIDDEN]"
echo ""

# Step 1: Install phpMyAdmin non-interactively
echo -e "${GREEN}[1/7] Installing phpMyAdmin...${NC}"
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y phpmyadmin

# Step 2: Create phpMyAdmin database and user manually
echo -e "${GREEN}[2/7] Creating phpMyAdmin database and user...${NC}"
mysql -u root -p"$DB_ROOT_PASSWORD" <<EOF
CREATE DATABASE IF NOT EXISTS phpmyadmin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'phpmyadmin'@'localhost' IDENTIFIED BY '$PMA_PASSWORD';
GRANT ALL PRIVILEGES ON phpmyadmin.* TO 'phpmyadmin'@'localhost';
FLUSH PRIVILEGES;
EOF

# Step 3: Import phpMyAdmin tables
echo -e "${GREEN}[3/7] Importing phpMyAdmin tables...${NC}"
if [ -f /usr/share/phpmyadmin/sql/create_tables.sql ]; then
    mysql -u root -p"$DB_ROOT_PASSWORD" phpmyadmin < /usr/share/phpmyadmin/sql/create_tables.sql
else
    echo -e "${YELLOW}Warning: create_tables.sql not found, skipping...${NC}"
fi

# Step 4: Configure phpMyAdmin database connection
echo -e "${GREEN}[4/7] Configuring phpMyAdmin database connection...${NC}"
cat > /etc/phpmyadmin/config-db.php <<EOF
<?php
\$dbuser='phpmyadmin';
\$dbpass='$PMA_PASSWORD';
\$basepath='';
\$dbname='phpmyadmin';
\$dbserver='localhost';
\$dbport='3306';
\$dbtype='mysql';
EOF

# Step 5: Create symbolic link
echo -e "${GREEN}[5/7] Creating symbolic link...${NC}"
ln -sf /usr/share/phpmyadmin /var/www/phpmyadmin
chown -R www-data:www-data /var/www/phpmyadmin

# Step 6: Configure Nginx
echo -e "${GREEN}[6/7] Configuring Nginx...${NC}"
cat > /etc/nginx/sites-available/phpmyadmin <<EOF
server {
    listen 80;
    server_name $PMA_DOMAIN;
    root /var/www/phpmyadmin;

    index index.php index.html index.htm;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.ht {
        deny all;
    }

    # Deny access to configuration files
    location ~ ^/libraries {
        deny all;
    }
    
    location ~ ^/setup {
        deny all;
    }

    # Deny access to sensitive files
    location ~ \.(htaccess|htpasswd|ini|log|sh|inc|bak|save|sql)\$ {
        deny all;
    }
}
EOF

# Disable default Nginx site if it exists
if [ -f /etc/nginx/sites-enabled/default ]; then
    rm /etc/nginx/sites-enabled/default
    echo -e "${YELLOW}Disabled default Nginx site${NC}"
fi

# Enable phpMyAdmin site
ln -sf /etc/nginx/sites-available/phpmyadmin /etc/nginx/sites-enabled/phpmyadmin

# Test Nginx configuration
echo -e "${GREEN}Testing Nginx configuration...${NC}"
if nginx -t; then
    systemctl reload nginx
    echo -e "${GREEN}Nginx configuration is valid and reloaded${NC}"
else
    echo -e "${RED}Nginx configuration test failed!${NC}"
    exit 1
fi

# Step 7: Setup SSL (optional)
echo -e "${GREEN}[7/7] SSL Setup${NC}"
read -p "Do you want to setup SSL with Let's Encrypt? (y/n): " setup_ssl

if [ "$setup_ssl" = "y" ] || [ "$setup_ssl" = "Y" ]; then
    if command -v certbot &> /dev/null; then
        echo -e "${GREEN}Setting up SSL certificate...${NC}"
        certbot --nginx -d "$PMA_DOMAIN" --non-interactive --agree-tos --email admin@"${PMA_DOMAIN#*.}" || {
            echo -e "${YELLOW}SSL setup failed. You can run manually later:${NC}"
            echo "  sudo certbot --nginx -d $PMA_DOMAIN"
        }
    else
        echo -e "${YELLOW}Certbot not found. Installing...${NC}"
        apt-get install -y certbot python3-certbot-nginx
        certbot --nginx -d "$PMA_DOMAIN" --non-interactive --agree-tos --email admin@"${PMA_DOMAIN#*.}" || {
            echo -e "${YELLOW}SSL setup failed. You can run manually later:${NC}"
            echo "  sudo certbot --nginx -d $PMA_DOMAIN"
        }
    fi
else
    echo -e "${YELLOW}Skipping SSL setup${NC}"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Installation Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}phpMyAdmin Access Information:${NC}"
echo "  URL: http://$PMA_DOMAIN"
if [ "$setup_ssl" = "y" ] || [ "$setup_ssl" = "Y" ]; then
    echo "  URL (SSL): https://$PMA_DOMAIN"
fi
echo ""
echo -e "${YELLOW}Login Credentials:${NC}"
echo "  Username: root (or your MariaDB user)"
echo "  Password: [Your MariaDB root password]"
echo ""
echo -e "${YELLOW}Important Notes:${NC}"
echo "  1. Make sure DNS is configured: $PMA_DOMAIN -> $(hostname -I | awk '{print $1}')"
echo "  2. phpMyAdmin database user: phpmyadmin"
echo "  3. phpMyAdmin database password: [HIDDEN]"
echo ""
echo -e "${GREEN}Done!${NC}"









