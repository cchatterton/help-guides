#!/usr/bin/env bash
set -euo pipefail

PLUGIN_DIR="Help Guides"
ASSET_NAME="help-guides.zip"
DIST_DIR="dist"

rm -rf "$DIST_DIR/$PLUGIN_DIR"
rm -f "$ASSET_NAME"
mkdir -p "$DIST_DIR"
cp -R "$PLUGIN_DIR" "$DIST_DIR/$PLUGIN_DIR"

find "$DIST_DIR/$PLUGIN_DIR" -name ".DS_Store" -delete
rm -rf "$DIST_DIR/$PLUGIN_DIR/node_modules"

(
    cd "$DIST_DIR"
    rm -f "$ASSET_NAME"
    zip -qr "$ASSET_NAME" "$PLUGIN_DIR"
)

cp "$DIST_DIR/$ASSET_NAME" "$ASSET_NAME"
