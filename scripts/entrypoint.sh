#!/bin/bash
set -e

DRUPAL_ROOT="/opt/drupal"
WEB_ROOT="${DRUPAL_ROOT}/web"
SITES_DEFAULT="${WEB_ROOT}/sites/default"

DB_HOST="${DRUPAL_DB_HOST:-db}"
DB_USER="${DRUPAL_DB_USER:-drupal}"
DB_PASS="${DRUPAL_DB_PASSWORD:-drupal_pass}"
DB_NAME="${DRUPAL_DB_NAME:-drupal}"
DB_PORT="${DRUPAL_DB_PORT:-3306}"
SITE_NAME="${DRUPAL_SITE_NAME:-TiendaVirtual}"
ADMIN_USER="${DRUPAL_ADMIN_USER:-admin}"
ADMIN_PASS="${DRUPAL_ADMIN_PASS:-admin123}"

echo "============================================"
echo "  🛒  TiendaVirtual - Inicializando..."
echo "============================================"

# ── Wait for database ─────────────────────────────────────────────────────────
echo "⏳ Esperando base de datos en ${DB_HOST}:${DB_PORT}..."
until mysqladmin ping -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" --silent 2>/dev/null; do
  echo "   Base de datos no disponible, reintentando en 5s..."
  sleep 5
done
echo "✅ Base de datos disponible."

cd "${DRUPAL_ROOT}"

# ── Check if already installed ────────────────────────────────────────────────
if drush status --field=bootstrap 2>/dev/null | grep -q "Successful"; then
  echo "ℹ️  Drupal ya está instalado. Arrancando Apache..."
  exec apache2-foreground
fi

# ── Prepare settings.php ──────────────────────────────────────────────────────
chmod 777 "${SITES_DEFAULT}"
if [ ! -f "${SITES_DEFAULT}/settings.php" ]; then
  cp "${SITES_DEFAULT}/default.settings.php" "${SITES_DEFAULT}/settings.php"
  chmod 666 "${SITES_DEFAULT}/settings.php"
fi

# ── Install Drupal ────────────────────────────────────────────────────────────
echo "📦 Instalando Drupal (perfil estándar)..."
drush site:install standard \
  --db-url="mysql://${DB_USER}:${DB_PASS}@${DB_HOST}:${DB_PORT}/${DB_NAME}" \
  --site-name="${SITE_NAME}" \
  --account-name="${ADMIN_USER}" \
  --account-pass="${ADMIN_PASS}" \
  --account-mail="admin@tiendavirtual.com" \
  --locale=es \
  --yes 2>&1

echo "✅ Drupal instalado."

# ── Enable Commerce & Search modules ─────────────────────────────────────────
echo "🛒 Habilitando módulos de comercio y búsqueda..."
drush en \
  commerce \
  commerce_store \
  commerce_product \
  commerce_order \
  commerce_price \
  commerce_cart \
  commerce_checkout \
  commerce_payment \
  commerce_payment_example \
  search \
  search_api \
  search_api_db \
  facets \
  --yes 2>&1

echo "✅ Módulos habilitados."

# ── Run store configuration script ───────────────────────────────────────────
echo "⚙️  Configurando tienda y creando productos..."
drush php:script /scripts/setup_store.php 2>&1

# ── Enable custom module ──────────────────────────────────────────────────────
echo "🧩 Habilitando módulo tienda_virtual..."
drush en tienda_virtual --yes 2>&1

# ── Clear caches ──────────────────────────────────────────────────────────────
echo "🗑️  Limpiando caché..."
drush cr 2>&1

echo ""
echo "============================================"
echo "  🚀  ¡TiendaVirtual lista!"
echo "  URL:   http://localhost:${APP_PORT:-8080}"
echo "  Admin: http://localhost:${APP_PORT:-8080}/user/login"
echo "  Usuario: ${ADMIN_USER} | Contraseña: ${ADMIN_PASS}"
echo "============================================"

exec apache2-foreground
