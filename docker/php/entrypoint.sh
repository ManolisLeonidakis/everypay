#!/bin/sh
set -e

echo "🚀 Laravel container starting..."

# Περιμένουμε λίγο το filesystem (ασφαλές)
sleep 1

# Αν δεν υπάρχει .env, το δημιουργούμε (προαιρετικό)
if [ ! -f .env ]; then
  echo "⚠️  .env not found, creating from .env.example"
  cp .env.example .env
fi

# Permissions (ασφαλές default)
chown -R www-data:www-data storage bootstrap/cache

# Clear caches
php artisan key:generate --force || true
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

# SQLite DB
if [ ! -f database/database.sqlite ]; then
  echo "📦 Creating SQLite database"
  touch database/database.sqlite
fi

# Migrate & Seed
echo "🗄️  Running migrations & seeders"
php artisan migrate:fresh --seed --force

echo "✅ Laravel ready!"

# Εκκίνηση PHP-FPM
exec php-fpm
