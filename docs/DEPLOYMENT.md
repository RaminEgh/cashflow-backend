# Deployment Guide - Ubuntu 22.04 Server

This guide provides step-by-step instructions for deploying the Cashflow Backend application on an Ubuntu 22.04 server.

## Prerequisites

- Ubuntu 22.04 LTS server with root or sudo access
- Domain name pointing to your server (optional but recommended)
- SSH access to the server

## Table of Contents

1. [Server Preparation](#1-server-preparation)
2. [Install Required Software](#2-install-required-software)
3. [Create Application User](#3-create-application-user)
4. [Database Setup](#4-database-setup)
5. [Deploy Application](#5-deploy-application)
6. [Configure Web Server](#6-configure-web-server)
7. [Setup Queue Worker](#7-setup-queue-worker)
8. [Configure SSL (Let's Encrypt)](#8-configure-ssl-lets-encrypt)
9. [Final Configuration](#9-final-configuration)
10. [Maintenance & Updates](#10-maintenance--updates)

---

## 1. Server Preparation

### Update System Packages

```bash
sudo apt update
sudo apt upgrade -y
```

**Note:** If you see a dialog about a pending kernel upgrade after running `apt upgrade`, you can safely click "Ok" and continue. The new kernel will be loaded after you reboot the server. It's recommended to reboot after completing the deployment to ensure you're running the latest kernel version:

```bash
sudo reboot
```

### Install Basic Tools

```bash
sudo apt install -y git curl wget unzip software-properties-common
```

---

## 2. Install Required Software

### Remove Previous PHP Versions (if any)

Before installing PHP 8.4, remove any existing PHP versions and PHP-FPM packages to avoid conflicts:

```bash
# Check what PHP versions are currently installed
dpkg -l | grep php | grep -E "^ii"

# Stop PHP-FPM services if running
sudo systemctl stop php*-fpm.service 2>/dev/null || true
sudo systemctl disable php*-fpm.service 2>/dev/null || true

# Remove common PHP versions (adjust based on what's installed)
sudo apt remove --purge php8.0* php8.1* php8.2* php8.3* -y 2>/dev/null || true
sudo apt remove --purge php7.* -y 2>/dev/null || true

# Remove PHP-FPM packages
sudo apt remove --purge php*-fpm -y 2>/dev/null || true

# Clean up any remaining configuration files and dependencies
sudo apt autoremove -y
sudo apt autoclean -y
```

### Install PHP 8.4 and Required Extensions

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-common php8.4-mysql php8.4-zip \
    php8.4-gd php8.4-mbstring php8.4-curl php8.4-xml php8.4-bcmath \
    php8.4-sqlite3 php8.4-intl php8.4-readline
```

### Verify PHP Installation

```bash
php -v
# Should show PHP 8.4.x
```

### Install Composer

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
composer --version
```

### Install MariaDB (or PostgreSQL)

**Option A: MariaDB (Recommended)**

```bash
sudo apt install -y mariadb-server mariadb-client
sudo mysql_secure_installation
```

**During `mysql_secure_installation`, you'll be prompted with the following questions:**

1. **Setup VALIDATE PASSWORD component**:
   - Type `Y` and press `Enter` (recommended: yes for production)
   - This enforces password strength requirements
   - If you choose `Y`, you'll be asked to select a password validation policy:
     - `0` = LOW (minimum 8 characters)
     - `1` = MEDIUM (requires numeric, mixed case, special characters, minimum 8 characters)
     - `2` = STRONG (requires numeric, mixed case, special characters, dictionary file, minimum 8 characters)
   - For production, select `1` (MEDIUM) or `2` (STRONG)
   - If you choose `N`, you can set any password but it's less secure

2. **Enter current password for root**: Press `Enter` (no password set by default on fresh install)

3. **Switch to unix_socket authentication**: Type `n` and press `Enter` (recommended: no)

4. **Change the root password**: Type `Y` and press `Enter` (recommended: yes)
   - Enter a strong password for the root user
   - If VALIDATE PASSWORD is enabled, your password must meet the policy requirements
   - Re-enter the password to confirm

5. **Remove anonymous users**: Type `Y` and press `Enter` (recommended: yes)

6. **Disallow root login remotely**: Type `Y` and press `Enter` (recommended: yes)

7. **Remove test database**: Type `Y` and press `Enter` (recommended: yes)

8. **Reload privilege tables now**: Type `Y` and press `Enter` (recommended: yes)

After completing these steps, MariaDB will be secured and ready to use.

**Option B: PostgreSQL**

```bash
sudo apt install -y postgresql postgresql-contrib
sudo -u postgres psql -c "ALTER USER postgres PASSWORD 'your_password';"
```

### Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

### Install Redis (Optional but Recommended for Caching)

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### Install phpMyAdmin (Optional - Database Management Tool)

phpMyAdmin provides a web interface for managing your MariaDB databases.

**Install phpMyAdmin:**

**Option 1: Automated Installation (Recommended)**

Use the provided installation script for hassle-free setup:

```bash
# Make script executable
chmod +x install-phpmyadmin.sh

# Run the script
sudo ./install-phpmyadmin.sh <subdomain> [password]

# Example:
sudo ./install-phpmyadmin.sh pma.abc.ir PhpMyAdmin2024!@#
```

The script will:

- Install phpMyAdmin non-interactively
- Create database and user with proper password policy
- Configure Nginx automatically
- Optionally setup SSL with Let's Encrypt
- Handle all configuration files

**Option 2: Manual Installation**

```bash
sudo apt install -y phpmyadmin
```

**Alternative Installation (if password validation causes issues):**

If you encounter password policy errors during installation, you can install without automatic database configuration:

```bash
# Remove partial installation
sudo apt remove --purge phpmyadmin -y

# Reinstall with manual configuration
sudo DEBIAN_FRONTEND=noninteractive apt install -y phpmyadmin

# Create phpMyAdmin database and user manually
sudo mysql -u root -p
```

Then run these SQL commands:

```sql
CREATE DATABASE phpmyadmin;
CREATE USER 'phpmyadmin'@'localhost' IDENTIFIED BY 'PhpMyAdmin2024!';
GRANT ALL PRIVILEGES ON phpmyadmin.* TO 'phpmyadmin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Configure phpMyAdmin to use this database:

```bash
sudo nano /etc/phpmyadmin/config-db.php
```

Add or update:

```php
<?php
$dbuser='phpmyadmin';
$dbpass='PhpMyAdmin2024!';
$basepath='';
$dbname='phpmyadmin';
$dbserver='localhost';
$dbport='3306';
$dbtype='mysql';
```

Import phpMyAdmin tables:

```bash
sudo mysql -u root -p phpmyadmin < /usr/share/phpmyadmin/sql/create_tables.sql
```

**During installation, you'll be prompted with several questions:**

1. **Web server to configure automatically**: Select `None` (press Tab, then Space to deselect, then Enter)
   - We'll configure Nginx manually

2. **Configure database for phpmyadmin with dbconfig-common**: Choose `Yes`

3. **Connection method for MySQL database**: Select `Unix socket` (recommended for local connections)
   - Unix socket is faster and more secure when phpMyAdmin and MariaDB are on the same server

4. **Authentication plugin for MySQL database**: Select `default` (recommended)
   - This uses the server's default authentication method
   - Most compatible with MariaDB and phpMyAdmin

5. **MySQL username for phpmyadmin**: Press Ok to accept default `phpmyadmin@localhost`
   - This is the database user that phpMyAdmin will use for its own configuration
   - It's separate from your application database users

6. **MySQL application password for phpmyadmin**: Enter a strong password
   - **Important**: If you enabled VALIDATE PASSWORD component with MEDIUM or STRONG policy, your password must meet these requirements:
     - Minimum 8 characters
     - Contains uppercase letters (A-Z)
     - Contains lowercase letters (a-z)
     - Contains numbers (0-9)
     - Contains special characters (!@#$%^&*)
   - Example: `PhpMyAdmin2024!@#`
   - If you get "ERROR 1819: Your password does not satisfy the current policy requirements", select `retry` and use a stronger password

**Configure Nginx for phpMyAdmin:**

Create a symbolic link to make phpMyAdmin accessible:

```bash
sudo ln -s /usr/share/phpmyadmin /var/www/phpmyadmin
```

Create Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/phpmyadmin
```

Add the following configuration:

```nginx
server {
    listen 8080;
    server_name your_server_ip;
    root /var/www/phpmyadmin;

    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
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
}
```

**Enable the site:**

```bash
sudo ln -s /etc/nginx/sites-available/phpmyadmin /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

**Configure firewall to allow phpMyAdmin access:**

```bash
sudo ufw allow 8080/tcp
```

**Secure phpMyAdmin (Highly Recommended):**

1. **Change the access URL** (protect against automated attacks):

```bash
sudo nano /etc/nginx/sites-available/phpmyadmin
```

Change the root to include a secret path:

```nginx
location /secretpath {
    alias /usr/share/phpmyadmin;
    index index.php;
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /usr/share/phpmyadmin$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

2. **Add HTTP Basic Authentication:**

```bash
# Create password file
sudo htpasswd -c /etc/nginx/.phpmyadmin-htpasswd admin
# Enter a strong password when prompted

# Add authentication to Nginx config
sudo nano /etc/nginx/sites-available/phpmyadmin
```

Add inside the server block:

```nginx
auth_basic "Restricted Access";
auth_basic_user_file /etc/nginx/.phpmyadmin-htpasswd;
```

3. **Reload Nginx:**

```bash
sudo nginx -t
sudo systemctl reload nginx
```

**Access phpMyAdmin:**

- Open your browser: `http://your_server_ip:8080` or `http://your_server_ip:8080/secretpath`
- Login with your MariaDB credentials (e.g., `root` or `cashflow_user`)

**Important Security Notes:**

- Never expose phpMyAdmin on port 80 or 443 without SSL
- Always use strong passwords
- Consider using phpMyAdmin only when needed and disable it otherwise
- Use HTTP authentication as an additional security layer
- For production, consider using SSL/TLS for phpMyAdmin
- Alternatively, use SSH tunneling: `ssh -L 8080:localhost:8080 ubuntu@your_server_ip`

**To disable phpMyAdmin when not needed:**

```bash
sudo rm /etc/nginx/sites-enabled/phpmyadmin
sudo systemctl reload nginx
```

**To re-enable:**

```bash
sudo ln -s /etc/nginx/sites-available/phpmyadmin /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

---

## 3. Configure Application User

Add the `ubuntu` user to the `www-data` group to ensure proper permissions:

```bash
sudo usermod -aG www-data ubuntu
```

**Note:** You may need to log out and log back in for the group changes to take effect, or run:

```bash
newgrp www-data
```

### Setup SSH Keys for GitHub (if using SSH clone)

If you plan to clone using SSH (e.g., `git@github.com:...`), you need to set up SSH keys:

```bash
# Generate SSH key (if you don't have one)
ssh-keygen -t ed25519 -C "your_email@example.com"
# Press Enter to accept default location (~/.ssh/id_ed25519)
# Enter a passphrase or press Enter for no passphrase

# Start SSH agent
eval "$(ssh-agent -s)"

# Add SSH key to agent
ssh-add ~/.ssh/id_ed25519

# Display public key to add to GitHub
cat ~/.ssh/id_ed25519.pub
```

**Add the public key to GitHub:**

1. Copy the output from `cat ~/.ssh/id_ed25519.pub`
2. Go to GitHub → Settings → SSH and GPG keys
3. Click "New SSH key"
4. Paste your public key and save

**Test SSH connection:**

```bash
ssh -T git@github.com
# Should see: "Hi username! You've successfully authenticated..."
```

**Alternative: Use HTTPS instead of SSH** (no SSH keys needed):

- Use `https://github.com/username/repo.git` instead of `git@github.com:username/repo.git`

---

## 4. Database Setup

### Set MariaDB Root Password

If you haven't set a root password during `mysql_secure_installation`, or if you need to change it, you can set it manually:

**Method 1: Using mysql_secure_installation (Recommended)**

```bash
sudo mysql_secure_installation
```

Follow the prompts and choose `Y` when asked to change the root password.

**Method 2: Set password manually via MySQL command line**

```bash
# Connect to MariaDB as root (no password needed if not set yet)
sudo mysql -u root

# Once connected, run these SQL commands:
```

```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_strong_password_here';
FLUSH PRIVILEGES;
EXIT;
```

**Method 3: Set password using mysqladmin**

```bash
sudo mysqladmin -u root password 'your_strong_password_here'
```

**Verify the password works:**

```bash
sudo mysql -u root -p
# Enter your password when prompted
# If successful, you'll see the MariaDB prompt
EXIT;
```

### Create Database and User

**For MariaDB:**

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE cashflow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cashflow_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON cashflow_db.* TO 'cashflow_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**For PostgreSQL:**

```bash
sudo -u postgres psql
```

```sql
CREATE DATABASE cashflow_db;
CREATE USER cashflow_user WITH PASSWORD 'strong_password_here';
ALTER ROLE cashflow_user SET client_encoding TO 'utf8';
ALTER ROLE cashflow_user SET default_transaction_isolation TO 'read committed';
ALTER ROLE cashflow_user SET timezone TO 'UTC';
GRANT ALL PRIVILEGES ON DATABASE cashflow_db TO cashflow_user;
\q
```

---

## 5. Deploy Application

### Clone Repository

```bash
# Create /var/www directory if it doesn't exist and set ownership
sudo mkdir -p /var/www
sudo chown ubuntu:www-data /var/www

# Clone the repository (choose one method below)
```

**Option 1: Clone using SSH (requires SSH keys setup - see section 3)**

```bash
git clone git@github.com:RaminEgh/cashflow-backend.git /var/www/cashflow-backend
```

**Option 2: Clone using HTTPS (no SSH keys needed - recommended if SSH not set up)**

```bash
git clone https://github.com/RaminEgh/cashflow-backend.git /var/www/cashflow-backend
```

**Navigate to the project directory:**

```bash
cd /var/www/cashflow-backend
```

**Troubleshooting:**

**If you get "Permission denied (publickey)" error:**

- This means SSH keys are not set up. Either:
  1. Set up SSH keys (see section 3 above), or
  2. Use HTTPS instead: `git clone https://github.com/RaminEgh/cashflow-backend.git /var/www/cashflow-backend`

**If you get "Permission denied" for directory:**

```bash
# Set proper ownership of /var/www
sudo chown -R ubuntu:www-data /var/www

# Then try cloning again
```

### Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### Set Permissions

```bash
cd /var/www/cashflow-backend
sudo chown -R ubuntu:www-data /var/www/cashflow-backend
sudo chmod -R 755 /var/www/cashflow-backend
sudo chmod -R 775 /var/www/cashflow-backend/storage
sudo chmod -R 775 /var/www/cashflow-backend/bootstrap/cache
```

### Configure Environment

```bash
cp .env.example .env
nano .env
```

**Update the following in `.env`:**

```env
APP_NAME="Cashflow Backend"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cashflow_db
DB_USERNAME=cashflow_user
DB_PASSWORD=strong_password_here

QUEUE_CONNECTION=database

CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

SANCTUM_STATEFUL_DOMAINS=yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

### Generate Application Key

```bash
php artisan key:generate
```

### Run Migrations

```bash
php artisan migrate --force
```

### Seed Database (Optional)

```bash
php artisan db:seed --force
```

### Create Storage Link

```bash
php artisan storage:link
```

### Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 6. Configure Web Server

### Create Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/cashflow-backend
```

**Add the following configuration:**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/cashflow-backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Increase upload size if needed
    client_max_body_size 100M;
}
```

### Enable Site

```bash
sudo ln -s /etc/nginx/sites-available/cashflow-backend /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Configure PHP-FPM

```bash
sudo nano /etc/php/8.4/fpm/php.ini
```

**Update these values:**

```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 256M
max_execution_time = 300
```

```bash
sudo systemctl restart php8.4-fpm
```

---

## 7. Setup Queue Worker

### Create Systemd Service

```bash
sudo nano /etc/systemd/system/cashflow-queue.service
```

**Add the following:**

```ini
[Unit]
Description=Cashflow Backend Queue Worker
After=network.target

[Service]
User=ubuntu
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/cashflow-backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

### Enable and Start Queue Worker

```bash
sudo systemctl daemon-reload
sudo systemctl enable cashflow-queue
sudo systemctl start cashflow-queue
sudo systemctl status cashflow-queue
```

---

## 8. Configure SSL (Let's Encrypt)

### Install Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

### Obtain SSL Certificate

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Auto-Renewal Test

```bash
sudo certbot renew --dry-run
```

---

## 9. Final Configuration

### Setup Log Rotation

```bash
sudo nano /etc/logrotate.d/cashflow-backend
```

**Add:**

```
/var/www/cashflow-backend/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 ubuntu www-data
    sharedscripts
}
```

### Configure Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

### Test Application

```bash
curl http://localhost/up
# Should return: {"status":"ok"}
```

### Verify Queue Worker

```bash
sudo journalctl -u cashflow-queue -f
```

---

## 10. Maintenance & Updates

### Update Application

```bash
cd /var/www/cashflow-backend
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart cashflow-queue
sudo systemctl reload php8.4-fpm
```

### Clear Application Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### View Logs

```bash
# Application logs
sudo tail -f /var/www/cashflow-backend/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Queue worker logs
sudo journalctl -u cashflow-queue -f
```

### Backup Database

**MariaDB:**

```bash
sudo mysqldump -u cashflow_user -p cashflow_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

**PostgreSQL:**

```bash
sudo -u postgres pg_dump cashflow_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Monitor Queue Jobs

```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## Troubleshooting

### Permission Issues

```bash
sudo chown -R ubuntu:www-data /var/www/cashflow-backend
sudo chmod -R 755 /var/www/cashflow-backend
sudo chmod -R 775 /var/www/cashflow-backend/storage
sudo chmod -R 775 /var/www/cashflow-backend/bootstrap/cache
```

### Queue Not Processing

```bash
# Check queue worker status
sudo systemctl status cashflow-queue

# Restart queue worker
sudo systemctl restart cashflow-queue

# Check for failed jobs
php artisan queue:failed
```

### Database Connection Issues

```bash
# Test database connection
php artisan tinker
# Then run: DB::connection()->getPdo();
```

### Nginx 502 Bad Gateway

```bash
# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Check PHP-FPM socket
ls -la /var/run/php/php8.4-fpm.sock
```

---

## Security Checklist

- [ ] Change default database passwords
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure firewall (UFW)
- [ ] Enable SSL/HTTPS
- [ ] Set proper file permissions
- [ ] Configure `.env` with secure values
- [ ] Enable automatic security updates
- [ ] Setup regular database backups
- [ ] Configure log rotation
- [ ] Review and restrict Nginx access

---

## Additional Notes

- **Cron Jobs**: If your application uses scheduled tasks, add them to crontab:

  ```bash
  crontab -e
  # Add: * * * * * cd /var/www/cashflow-backend && php artisan schedule:run >> /dev/null 2>&1
  ```

- **Redis Caching**: If using Redis, update `.env`:

  ```env
  CACHE_DRIVER=redis
  SESSION_DRIVER=redis
  QUEUE_CONNECTION=redis
  ```

- **Multiple Environments**: Consider using separate servers or containers for staging and production.

- **Monitoring**: Consider setting up monitoring tools like Laravel Telescope (dev only), New Relic, or Sentry for production error tracking.

---

## Support

For issues or questions, refer to:

- Application README: `README.md`
- API Documentation: `docs/API.md`
- Architecture Documentation: `docs/ARCHITECTURE.md`
