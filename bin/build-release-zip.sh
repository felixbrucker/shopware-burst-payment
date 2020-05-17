#!/bin/bash
set -e

PLUGIN_NAME="BurstPayment"
PLUGIN_NAME_PATH="burst-payment"

TAG="${TAG:-dev}"
echo "Building release zip for $PLUGIN_NAME $TAG"

PLUGIN_DIR="$(pwd)"
TMP_DIR="$(mktemp -d)"
PLUGIN_DIST_DIR="$TMP_DIR/$PLUGIN_NAME_PATH"

cd /
cp -R "$PLUGIN_DIR" "$PLUGIN_DIST_DIR"
cd "$TMP_DIR"
PLUGIN_ZIP_NAME="$PLUGIN_NAME_PATH-$TAG.zip"
zip -r "$PLUGIN_ZIP_NAME" "$PLUGIN_NAME_PATH" --exclude burst-payment/node_modules/\* burst-payment/bin/\* burst-payment/test/\* burst-payment/.editorconfig burst-payment/.gitignore burst-payment/coverage\*.xml burst-payment/phpunit.xml burst-payment/.git/\* burst-payment/.github/\*
cp "$PLUGIN_ZIP_NAME" "$PLUGIN_DIR/$PLUGIN_ZIP_NAME"
