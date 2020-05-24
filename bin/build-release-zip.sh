#!/bin/bash
set -e

PLUGIN_NAME="BurstPayment"

TAG="${TAG:-dev}"
echo "Building release zip for $PLUGIN_NAME $TAG"

PLUGIN_DIR="$(pwd)"
TMP_DIR="$(mktemp -d)"
PLUGIN_DIST_DIR="$TMP_DIR/$PLUGIN_NAME"

cp -R "$PLUGIN_DIR" "$PLUGIN_DIST_DIR"
cd "$TMP_DIR"
PLUGIN_ZIP_NAME="burst-payment-$TAG.zip"
zip -r "$PLUGIN_ZIP_NAME" "$PLUGIN_NAME" --exclude "$PLUGIN_NAME"/node_modules/\* "$PLUGIN_NAME"/bin/\* "$PLUGIN_NAME"/test/\* "$PLUGIN_NAME"/.editorconfig "$PLUGIN_NAME"/.gitignore "$PLUGIN_NAME"/coverage\*.xml "$PLUGIN_NAME"/phpunit\*.xml "$PLUGIN_NAME"/.git/\* "$PLUGIN_NAME"/.github/\* "$PLUGIN_NAME"/composer.lock
cp "$PLUGIN_ZIP_NAME" "$PLUGIN_DIR/$PLUGIN_ZIP_NAME"
