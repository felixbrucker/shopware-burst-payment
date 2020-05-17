#!/bin/bash
set -e

TAG="${TAG:-dev}"
echo "Building release zip for BurstPayment $TAG"

PLUGIN_DIR="$(pwd)"
TMP_DIR="$(mktemp -d)"
PLUGIN_DIST_DIR="$TMP_DIR/burst-payment"

cd /
cp -R "$PLUGIN_DIR" "$PLUGIN_DIST_DIR"
cd "$TMP_DIR"
zip -r "burst-payment-$TAG.zip" "burst-payment" --exclude burst-payment/node_modules/\* bin burst-payment/test/\* burst-payment/.editorconfig burst-payment/.gitignore burst-payment/coverage\*.xml burst-payment/phpunit.xml burst-payment/.git/\* burst-payment/.github/\*
cp "burst-payment-$TAG.zip" "$PLUGIN_DIR/burst-payment-$TAG.zip"
