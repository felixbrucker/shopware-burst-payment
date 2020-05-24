#!/bin/bash
set -e

PLUGIN_NAME="BurstPayment"

TAG="${TAG:-dev}"
echo "Building release zip for $PLUGIN_NAME $TAG"

PLUGIN_DIR="$(pwd)"
TMP_DIR="$(mktemp -d)"
PLUGIN_DIST_DIR="$TMP_DIR/$PLUGIN_NAME"

mkdir "$PLUGIN_DIST_DIR"
cp -R "$PLUGIN_DIR/src" "$PLUGIN_DIR/autoload-dist" "$PLUGIN_DIR/vendor" "$PLUGIN_DIR/CHANGELOG.md" "$PLUGIN_DIR/composer.json" "$PLUGIN_DIR/LICENSE" "$PLUGIN_DIR/README.md" "$PLUGIN_DIST_DIR/"
cd "$TMP_DIR"
PLUGIN_ZIP_NAME="burst-payment-$TAG.zip"
zip -r "$PLUGIN_ZIP_NAME" "$PLUGIN_NAME"
cp "$PLUGIN_ZIP_NAME" "$PLUGIN_DIR/$PLUGIN_ZIP_NAME"
