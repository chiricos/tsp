# Copilot Instructions — TiendaVirtual

## Idioma y estilo
- Responder **en español**
- Dar comandos listos para ejecutar
- Respuestas directas, sin teoría innecesaria
- Asumir entorno Linux + DDEV

---

## Entorno de desarrollo

**Requisitos:** DDEV ≥ v1.22 · Docker ≥ 24. No se necesita PHP/Composer en el host.

```bash
ddev start          # Primera vez: instala todo (~5-10 min). Siguientes arranques: segundos.
ddev launch         # Abre el sitio en el navegador
ddev stop / restart
ddev ssh            # Shell dentro del contenedor web
```

**Stack:** Drupal 10.6 · PHP 8.3 · Apache · MariaDB 10.11 · Composer 2  
**URL local:** `https://tiendavirtual.ddev.site:8443`  
**Credenciales admin:** `admin` / `admin123`  
**BD:** host `db`, user/pass/dbname todos = `db`

---

## Comandos Drush frecuentes

```bash
ddev drush cr                   # Limpiar caché (tras cualquier cambio de código/config)
ddev drush updb                 # Actualizar esquema de base de datos
ddev drush cim                  # Importar configuración desde /config/sync/
ddev drush cex                  # Exportar configuración activa
ddev drush uli                  # Login sin contraseña (link de un solo uso)
ddev drush php:script /var/www/html/scripts/setup_store.php  # Re-ejecutar setup de productos
ddev drush en <modulo> --yes    # Habilitar módulo
ddev drush pmu <modulo> --yes   # Deshabilitar módulo
```

---

## Arquitectura

### Capas principales

```
composer.json          → dependencias (drupal/commerce ^2, search_api, facets, drush)
.ddev/config.yaml      → configuración DDEV; el hook post-start llama a scripts/ddev-install.sh
scripts/
  ddev-install.sh      → instalación automática primera vez (create-project + drush site:install + módulos)
  setup_store.php      → configura tienda Commerce, campos, taxonomía y crea los 20 productos
web/
  modules/custom/tienda_virtual/   → módulo custom (toda la lógica de catálogo y búsqueda)
  modules/contrib/                 → módulos de terceros (NO modificar)
```

### Módulo custom `tienda_virtual`

| Archivo | Responsabilidad |
|---------|----------------|
| `tienda_virtual.routing.yml` | Define `/catalog` y `/busqueda-avanzada` |
| `src/Controller/CatalogController.php` | Lógica de consulta y filtrado de productos |
| `templates/tienda-catalog.html.twig` | Grid del catálogo con búsqueda básica |
| `templates/tienda-search-advanced.html.twig` | Formulario y resultados de búsqueda avanzada |
| `js/tienda_virtual.js` | Modal de vista rápida al hacer clic en imagen |
| `css/tienda_virtual.css` | Estilos responsivos del catálogo |

### Flujo de datos del catálogo

1. `CatalogController::queryProducts()` → `entityQuery('commerce_product')` con filtros `title/body LIKE` y `field_category`
2. `buildProductData()` → itera `Product::loadMultiple()`, extrae precio de la **variación** (`getDefaultVariation()->getPrice()`), stock de `field_stock` en la variación
3. Filtro de precio y ordenación se hacen **en PHP post-query** (no en SQL) porque el precio vive en la entidad `commerce_product_variation`, no en `commerce_product`
4. El array resultante se pasa a Twig; el carrito usa Commerce 2 nativo (`/cart`, `/checkout`)

### Campos personalizados relevantes

| Campo | Entidad | Tipo |
|-------|---------|------|
| `field_product_image` | `commerce_product` | image |
| `field_category` | `commerce_product` | entity_reference → taxonomy `product_category` |
| `field_stock` | `commerce_product_variation` | integer |

---

## Convenciones del codebase

- **`\Drupal::` en el controlador**: el código actual usa llamadas estáticas. Al refactorizar, mover a servicios inyectados (PSR-4 / PSR-12, DI en constructor).
- **Sin lógica en templates Twig**: los datos llegan como arrays planos desde el controlador.
- **`'#cache' => ['max-age' => 0]`**: ambas páginas tienen caché deshabilitada para que los filtros GET funcionen sin invalidaciones manuales. Cambiar a cache tags si se necesita rendimiento.
- **Instalación idempotente**: `setup_store.php` comprueba existencia antes de crear (store, campos, productos). Seguro re-ejecutarlo.
- **Parches a contrib**: vía `composer patches` (plugin `cweagans/composer-patches`), nunca editar `web/modules/contrib/` directamente.
- **`minimum-stability: dev` + `prefer-stable: true`**: requerido por `drupal/commerce ^2` (depende de `inline_entity_form ^3@RC`).

---

## Seguridad

- CSP, `X-Frame-Options`, `Strict-Transport-Security` en producción (configurar en `web/.htaccess` o balanceador)
- No usar `unsafe-inline` en CSP si se puede evitar
- Aplicar parches `SA-CORE` de Drupal inmediatamente tras publicación
- En AWS: ALB + WAF, restringir IPs a `/admin`

---

## Módulos contrib instalados

| Módulo | Función |
|--------|---------|
| `drupal/commerce` | Tienda, productos, carrito, checkout |
| `drupal/search_api` + `search_api_db` | Índice de búsqueda sobre BD |
| `drupal/facets` | Filtros facetados (disponible para configurar en UI) |
| `drush/drush` | CLI de administración Drupal |
