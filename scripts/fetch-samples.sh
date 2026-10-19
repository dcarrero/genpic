#!/usr/bin/env bash
# Descarga las imágenes originales a resolución completa (≈340 MB) y las
# coloca en public/img/, sustituyendo las versiones reducidas del repo.
#
#   ./scripts/fetch-samples.sh
#
# El repo trae las muestras de estilo a 1200 px para que el clone sea ligero.
# Este pack contiene las de 2048 px que se usaban en producción.
set -euo pipefail

URL="${GENPIC_IMAGES_URL:-https://github.com/dcarrero/genpic/releases/download/v1.7.2/genpic-images-full.tar.gz}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

echo "Descargando $URL"
curl -fL --progress-bar -o "$TMP/images.tar.gz" "$URL"

echo "Descomprimiendo en public/img/"
tar -xzf "$TMP/images.tar.gz" -C "$ROOT/public"

echo "Listo."
