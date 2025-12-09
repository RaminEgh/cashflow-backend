#!/bin/bash

echo "🚀 Starting deployment..."

# Enter maintenance mode
php artisan down

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers
# sudo systemctl restart sofrehdar-queue

# Exit maintenance mode
php artisan up

echo "✅ Deployment complete!"
