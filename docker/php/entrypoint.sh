#!/bin/sh
set -e

echo "🚀 Starting Laravel container..."

# 1️⃣ Δημιουργία SQLite DB αν δεν υπάρχει
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

# 2️⃣ Permissions
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

# 3️⃣ Composer install αν δεν υπάρχει vendor
if [ ! -d vendor ]; then
    composer install --prefer-dist --no-interaction --optimize-autoloader
fi

# 4️⃣ Generate key αν χρειάζεται
php artisan key:generate --force

# 5️⃣ **Πρώτα τρέξε migrations & seed** πριν οποιοδήποτε cache clear
php artisan migrate:fresh --seed --force

# 6️⃣ Τώρα μπορείς να καθαρίσεις cache ή να τρέξεις άλλα artisan commands
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# 7️⃣ Τέλος, start PHP-FPM
exec php-fpm
