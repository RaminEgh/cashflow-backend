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

---

## 3. Create Application User

```bash
sudo adduser --disabled-password --gecos "" cashflow
sudo usermod -aG www-data cashflow
```

### Setup SSH Key Access (if deploying via Git)

```bash
sudo -u cashflow mkdir -p /home/cashflow/.ssh
sudo -u cashflow chmod 700 /home/cashflow/.ssh
# Add your public key to /home/cashflow/.ssh/authorized_keys
sudo -u cashflow chmod 600 /home/cashflow/.ssh/authorized_keys
```

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
sudo -u cashflow mkdir -p /var/www
cd /var/www
sudo -u cashflow git clone <your-repository-url> cashflow-backend
cd cashflow-backend
```

### Install Dependencies

```bash
sudo -u cashflow composer install --optimize-autoloader --no-dev
```

### Set Permissions

```bash
cd /var/www/cashflow-backend
sudo chown -R cashflow:www-data /var/www/cashflow-backend
sudo chmod -R 755 /var/www/cashflow-backend
sudo chmod -R 775 /var/www/cashflow-backend/storage
sudo chmod -R 775 /var/www/cashflow-backend/bootstrap/cache
```

### Configure Environment

```bash
sudo -u cashflow cp .env.example .env
sudo -u cashflow nano .env
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
sudo -u cashflow php artisan key:generate
```

### Run Migrations

```bash
sudo -u cashflow php artisan migrate --force
```

### Seed Database (Optional)

```bash
sudo -u cashflow php artisan db:seed --force
```

### Create Storage Link

```bash
sudo -u cashflow php artisan storage:link
```

### Optimize Application

```bash
sudo -u cashflow php artisan config:cache
sudo -u cashflow php artisan route:cache
sudo -u cashflow php artisan view:cache
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
User=cashflow
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
    create 0640 cashflow www-data
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
sudo -u cashflow git pull origin main
sudo -u cashflow composer install --optimize-autoloader --no-dev
sudo -u cashflow php artisan migrate --force
sudo -u cashflow php artisan config:cache
sudo -u cashflow php artisan route:cache
sudo -u cashflow php artisan view:cache
sudo systemctl restart cashflow-queue
sudo systemctl reload php8.4-fpm
```

### Clear Application Cache

```bash
sudo -u cashflow php artisan cache:clear
sudo -u cashflow php artisan config:clear
sudo -u cashflow php artisan route:clear
sudo -u cashflow php artisan view:clear
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
sudo -u cashflow php artisan queue:failed

# Retry failed jobs
sudo -u cashflow php artisan queue:retry all
```

---

## Troubleshooting

### Permission Issues

```bash
sudo chown -R cashflow:www-data /var/www/cashflow-backend
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
sudo -u cashflow php artisan queue:failed
```

### Database Connection Issues

```bash
# Test database connection
sudo -u cashflow php artisan tinker
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
  sudo crontab -u cashflow -e
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
