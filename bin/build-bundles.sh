#!/bin/bash
set -e

SHOPWARE_VERSION=6.2

TAG="${TAG:-dev}"
echo "Building BurstPayment $TAG bundles with Shopware v$SHOPWARE_VERSION"

DB_PORT="${DB_PORT:-3306}"
PLUGIN_DIR="$(pwd)"
TMP_DIR="$(mktemp -d)"
SHOPWARE_DEVELOPMENT_DIR="$TMP_DIR/shopware-development"

echo "Installing Shopware v$SHOPWARE_VERSION"
git clone --single-branch --branch "$SHOPWARE_VERSION" https://github.com/shopware/development.git "$SHOPWARE_DEVELOPMENT_DIR"
ln -s "$PLUGIN_DIR" "$SHOPWARE_DEVELOPMENT_DIR/custom/plugins/BurstPayment"
printf "const:\n    APP_ENV: \"dev\"\n    APP_URL: \"http://127.0.0.1\"\n    DB_HOST: \"127.0.0.1\"\n    DB_PORT: \"$DB_PORT\"\n    DB_NAME: \"shopware6release\"\n    DB_USER: \"root\"\n    DB_PASSWORD: \"shopware\"" > "$SHOPWARE_DEVELOPMENT_DIR/.psh.yaml.override"
cd "$SHOPWARE_DEVELOPMENT_DIR" && ./psh.phar init-composer
cd "$SHOPWARE_DEVELOPMENT_DIR" && ./psh.phar init-database
cd "$SHOPWARE_DEVELOPMENT_DIR" && ./psh.phar init-shopware
cd "$SHOPWARE_DEVELOPMENT_DIR" && ./psh.phar administration:install-dependencies
cd "$SHOPWARE_DEVELOPMENT_DIR" && bin/console plugin:install --activate BurstPayment

echo "Building the administration bundle"
cd "$SHOPWARE_DEVELOPMENT_DIR" && ./psh.phar administration:build
