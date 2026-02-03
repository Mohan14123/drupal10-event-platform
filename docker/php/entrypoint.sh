#!/bin/bash
set -e

# Wait for database to be ready
echo "Waiting for PostgreSQL connection..."
until PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; do
    echo "PostgreSQL is unavailable - sleeping..."
    sleep 2
done
echo "PostgreSQL is ready!"

cd /var/www/html

# Install Composer dependencies if vendor folder doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Check if Drupal is installed
if [ ! -f "web/sites/default/settings.php" ]; then
    echo "Setting up Drupal..."
    
    # Copy default settings
    if [ -f "web/sites/default/default.settings.php" ]; then
        cp web/sites/default/default.settings.php web/sites/default/settings.php
        chmod 666 web/sites/default/settings.php
    fi
    
    # Create files directory
    mkdir -p web/sites/default/files
    chmod -R 775 web/sites/default/files
    
    # Add database configuration for PostgreSQL
    cat >> web/sites/default/settings.php << EOF

\$databases['default']['default'] = [
  'database' => getenv('DB_NAME') ?: 'drupal',
  'username' => getenv('DB_USER') ?: 'drupal',
  'password' => getenv('DB_PASSWORD') ?: 'drupal_password',
  'host' => getenv('DB_HOST') ?: 'postgres',
  'port' => '5432',
  'driver' => 'pgsql',
  'prefix' => '',
  'namespace' => 'Drupal\\pgsql\\Driver\\Database\\pgsql',
  'autoload' => 'core/modules/pgsql/src/Driver/Database/pgsql/',
];

\$settings['hash_salt'] = '$(openssl rand -hex 32)';
\$settings['config_sync_directory'] = '../config/sync';
\$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^127\\.0\\.0\\.1$',
  '^drupal_nginx$',
];

EOF
    
    chmod 444 web/sites/default/settings.php
fi

# Check if Drupal needs to be installed
if ! vendor/bin/drush status bootstrap 2>/dev/null | grep -q "Successful"; then
    echo "Installing Drupal..."
    vendor/bin/drush site:install standard \
        --db-url="pgsql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}/${DB_NAME}" \
        --account-name=admin \
        --account-pass=admin \
        --account-mail="${ADMIN_EMAIL}" \
        --site-name="Event Registration Platform" \
        --site-mail="${ADMIN_EMAIL}" \
        -y
    
    echo "Drupal installed successfully!"
fi

# Enable custom module if it exists
if [ -d "web/modules/custom/event_registration" ]; then
    echo "Enabling event_registration module..."
    vendor/bin/drush en event_registration -y 2>/dev/null || true
    vendor/bin/drush cr
fi

# Ensure proper permissions
chown -R www-data:www-data /var/www/html/web/sites/default/files 2>/dev/null || true

echo "Drupal is ready!"

# Execute the main command
exec "$@"
