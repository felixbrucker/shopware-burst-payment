#!/bin/bash
set -e

PLUGIN_NAME="BurstPayment"

TAG="${TAG:-dev-master}"
echo "Building release zip for $PLUGIN_NAME $TAG"

PLUGIN_DIR="$(pwd)"
TMP_DIR="$(mktemp -d)"
PLUGIN_DIST_DIR="$TMP_DIR/$PLUGIN_NAME"

echo "Installing php autoload-dist dependencies .."
rm -rf "$PLUGIN_DIR/vendor"
cd "$PLUGIN_DIR/autoload-dist" && composer install && cd "$PLUGIN_DIR" && cp -R "autoload-dist/vendor" "vendor"

echo "Building js bundles .."
cd "$PLUGIN_DIR" && npm ci && npm run build

echo "Copying all files required for the zip .."
mkdir "$PLUGIN_DIST_DIR"
cp -R "$PLUGIN_DIR/src" "$PLUGIN_DIR/autoload-dist" "$PLUGIN_DIR/vendor" "$PLUGIN_DIR/CHANGELOG.md" "$PLUGIN_DIR/composer.json" "$PLUGIN_DIR/LICENSE" "$PLUGIN_DIR/README.md" "$PLUGIN_DIST_DIR/"

echo "Creating the zip archive .."
cd "$TMP_DIR"
PLUGIN_ZIP_NAME="burst-payment-$TAG.zip"
zip -r "$PLUGIN_ZIP_NAME" "$PLUGIN_NAME"

cp "$PLUGIN_ZIP_NAME" "$PLUGIN_DIR/$PLUGIN_ZIP_NAME"
echo "$PLUGIN_ZIP_NAME copied to $PLUGIN_DIR"
