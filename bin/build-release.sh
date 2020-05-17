#!/bin/bash
set -e

SHOPWARE_VERSION=6.2
TAG="${GITHUB_REF##*/}"
TAG="${TAG:-dev}"
echo "Building v$TAG with Shopware v$SHOPWARE_VERSION"

PLUGIN_DIR="$(pwd)"
TMP_DIR="$(mktemp -d)"
SHOPWARE_DEVELOPMENT_DIR="$TMP_DIR/shopware-development"

echo "Installing plugin dependencies"
cd "$PLUGIN_DIR/autoload-dist" && composer install
cd "$PLUGIN_DIR" && npm install

echo "Installing Shopware v$SHOPWARE_VERSION"
git clone --single-branch --branch "$SHOPWARE_VERSION" https://github.com/shopware/development.git "$SHOPWARE_DEVELOPMENT_DIR"
ln -s "$PLUGIN_DIR" "$SHOPWARE_DEVELOPMENT_DIR/custom/plugins/BurstPayment"
printf "const:\n    APP_ENV: \"dev\"\n    APP_URL: \"http://localhost\"\n    DB_HOST: \"localhost\"\n    DB_PORT: \"3306\"\n    DB_NAME: \"shopware6release\"\n    DB_USER: \"shopware\"\n    DB_PASSWORD: \"shopware\"" > "$SHOPWARE_DEVELOPMENT_DIR/.psh.yaml.override"
cd "$SHOPWARE_DEVELOPMENT_DIR" && ./psh.phar install
cd "$SHOPWARE_DEVELOPMENT_DIR" && bin/console plugin:install --activate BurstPayment

echo "Building the administration bundle"
cd "$SHOPWARE_DEVELOPMENT_DIR" && ./psh.phar administration:build

echo "Building the release zip"
PLUGIN_DIST_DIR="$TMP_DIR/burst-payment"
ls -lah "$PLUGIN_DIR"
mkdir "$PLUGIN_DIST_DIR"
cd /
mv "$PLUGIN_DIR" "$PLUGIN_DIST_DIR"
ls -lah "$PLUGIN_DIST_DIR"
cd "$TMP_DIR"
zip -r "burst-payment-$TAG.zip" "burst-payment" --exclude burst-payment/vendor/\* burst-payment/node_modules/\* bin burst-payment/test/\* burst-payment/.editorconfig burst-payment/.gitignore burst-payment/coverage\*.xml burst-payment/phpunit.xml burst-payment/.git/\* burst-payment/.github/\*
cp "burst-payment-$TAG.zip" "$PLUGIN_DIR/"
