#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCE_BUILD="$APP_ROOT/public/build"
HOSTINGER_PUBLIC_HTML="${HOSTINGER_PUBLIC_HTML:-$APP_ROOT/../public_html}"
TARGET_BUILD="$HOSTINGER_PUBLIC_HTML/build"

if [ ! -d "$SOURCE_BUILD/assets" ]; then
    echo "No existe $SOURCE_BUILD/assets. Ejecuta pnpm run build primero." >&2
    exit 1
fi

if [ ! -d "$HOSTINGER_PUBLIC_HTML" ]; then
    echo "No existe $HOSTINGER_PUBLIC_HTML. Ajusta HOSTINGER_PUBLIC_HTML=/ruta/public_html." >&2
    exit 1
fi

rm -rf "$TARGET_BUILD"
cp -a "$SOURCE_BUILD" "$TARGET_BUILD"

echo "Build sincronizado en $TARGET_BUILD"
