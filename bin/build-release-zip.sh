#!/bin/bash
set -e

PLUGIN_NAME="BurstPayment"

TAG="${TAG:-dev-master}"
echo "Building release zip for $PLUGIN_NAME $TAG"

CURRENT_DIR="$(pwd)"
TMP_DIR="$(mktemp -d)"
PLUGIN_DIR="$TMP_DIR/plugin"
TMP_DIR="$(mktemp -d)"
PLUGIN_DIST_DIR="$TMP_DIR/$PLUGIN_NAME"

echo "Copying required files for a clean build .."
cp -R "$CURRENT_DIR" "$PLUGIN_DIR"
rm -rf "$PLUGIN_DIR/autoload-dist/vendor"
rm -rf "$PLUGIN_DIR/vendor"

echo "Installing php autoload-dist dependencies .."
cd "$PLUGIN_DIR/autoload-dist" && composer install && cd "$PLUGIN_DIR" && cp -R "autoload-dist/vendor" "vendor"

echo "Building js bundles .."
cd "$PLUGIN_DIR" && npm ci && npm run build

echo "Copying required files for the zip .."
mkdir "$PLUGIN_DIST_DIR"
cp -R "$PLUGIN_DIR/src" "$PLUGIN_DIR/autoload-dist" "$PLUGIN_DIR/vendor" "$PLUGIN_DIR/CHANGELOG.md" "$PLUGIN_DIR/composer.json" "$PLUGIN_DIR/LICENSE" "$PLUGIN_DIR/README.md" "$PLUGIN_DIST_DIR/"

echo "Creating the zip archive .."
cd "$TMP_DIR"
PLUGIN_ZIP_NAME="burst-payment-$TAG.zip"
zip -qr "$PLUGIN_ZIP_NAME" "$PLUGIN_NAME"

cp "$PLUGIN_ZIP_NAME" "$CURRENT_DIR/$PLUGIN_ZIP_NAME"
echo "$PLUGIN_ZIP_NAME copied to $CURRENT_DIR"
