#!/bin/bash
# ddev-install.sh – Runs inside DDEV web container on first ddev start.
set -e

DRUPAL_ROOT="/var/www/html"
cd "$DRUPAL_ROOT"

echo "============================================"
echo "  🛒  TiendaVirtual - Verificando instalación"
echo "============================================"

# Skip if Drupal is already bootstrapped
if drush status --field=bootstrap 2>/dev/null | grep -q "Successful"; then
  echo "ℹ️  Drupal ya está instalado y en funcionamiento. Nada que hacer."
  exit 0
fi

echo "🚀 Primera instalación detectada, comenzando..."

# ── 1. Create Drupal 10 project (composer create-project) ─────────────────
if [ ! -f composer.json ]; then
  echo "📦 Creando proyecto Drupal 10 con Composer..."

  # Back up custom modules before create-project overwrites web/
  if [ -d web/modules/custom ]; then
    cp -r web/modules/custom /tmp/tv-custom-bak
  fi

  # create-project needs an empty dir; use temp and merge
  composer create-project drupal/recommended-project:^10 /tmp/drupal-new \
    --no-interaction --no-progress --quiet

  # Merge into project root (no-clobber so existing files win)
  cp -rn /tmp/drupal-new/. . 2>/dev/null || true
  rm -rf /tmp/drupal-new

  # Restore custom modules
  if [ -d /tmp/tv-custom-bak ]; then
    mkdir -p web/modules/custom
    cp -r /tmp/tv-custom-bak/. web/modules/custom/
    rm -rf /tmp/tv-custom-bak
  fi

  echo "✅ Proyecto Drupal creado."
else
  echo "ℹ️  composer.json ya existe, omitiendo create-project."
fi

# ── 2. Add Commerce, Search API, Facets, Drush ────────────────────────────
echo "🔧 Agregando módulos de comercio..."
composer config minimum-stability dev
composer config prefer-stable true
composer require \
  drush/drush:^12 \
  drupal/commerce:^2 \
  drupal/search_api:^1 \
  drupal/search_api_db:^1 \
  drupal/facets:^2 \
  --no-interaction --no-progress --with-all-dependencies
echo "✅ Dependencias instaladas."

# ── 3. Prepare settings.php ───────────────────────────────────────────────
SITES="$DRUPAL_ROOT/web/sites/default"
chmod 777 "$SITES"
if [ ! -f "$SITES/settings.php" ]; then
  cp "$SITES/default.settings.php" "$SITES/settings.php"
  chmod 666 "$SITES/settings.php"
fi

# ── 4. Install Drupal (DDEV provides DB as db:db@db/db) ───────────────────
echo "🌐 Instalando Drupal..."
drush site:install standard \
  --db-url="mysql://db:db@db/db" \
  --site-name="TiendaVirtual" \
  --account-name="admin" \
  --account-pass="admin123" \
  --account-mail="admin@tiendavirtual.com" \
  --locale=es \
  --yes
echo "✅ Drupal instalado."

# ── 5. Enable Commerce + Search modules ───────────────────────────────────
echo "🛒 Habilitando módulos..."
drush en \
  commerce commerce_store commerce_product commerce_order \
  commerce_price commerce_cart commerce_checkout \
  commerce_payment commerce_payment_example \
  search search_api search_api_db facets \
  --yes
echo "✅ Módulos habilitados."

# ── 6. Configure store and create 20 products ─────────────────────────────
echo "⚙️  Configurando tienda y creando productos..."
drush php:script /var/www/html/scripts/setup_store.php

# ── 7. Enable custom module ───────────────────────────────────────────────
echo "🧩 Habilitando módulo tienda_virtual..."
drush en tienda_virtual --yes

# ── 8. Clear caches ───────────────────────────────────────────────────────
drush cr

echo ""
echo "============================================"
echo "  🚀  ¡TiendaVirtual lista!"
echo "  URL:   https://tiendavirtual.ddev.site:8443"
echo "  Admin: https://tiendavirtual.ddev.site:8443/user/login"
echo "  Usuario: admin | Contraseña: admin123"
echo "============================================"
