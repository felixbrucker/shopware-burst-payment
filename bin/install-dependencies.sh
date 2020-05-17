#!/bin/bash
set -e

PLUGIN_DIR="$(pwd)"
echo "Installing plugin dependencies"
cd "$PLUGIN_DIR/autoload-dist" && composer install
cp -R "$PLUGIN_DIR/autoload-dist/vendor" "$PLUGIN_DIR/vendor"
cd "$PLUGIN_DIR" && npm install
