# 🛒 TiendaVirtual — Drupal 10 + Commerce (DDEV)

Tienda virtual completa construida con **Drupal 10 + Commerce 2** y **DDEV**.

## Requisitos

- [DDEV](https://ddev.readthedocs.io/en/stable/) ≥ v1.22
- Docker ≥ 24

## Inicio rápido

```bash
# 1. Entrar al directorio del proyecto
cd tsp

# 2. Iniciar DDEV (primera vez: instala Drupal + módulos + 20 productos ~5-10 min)
ddev start

# 3. Cuando veas "🚀 ¡TiendaVirtual lista!", abrir en el navegador:
ddev launch
```

> La instalación automática solo ocurre la **primera vez**.  
> Las siguientes ejecuciones de `ddev start` arrancan en segundos.

---

## Credenciales por defecto

| Rol   | Usuario | Contraseña |
|-------|---------|------------|
| Admin | admin   | admin123   |

---

## URLs

| URL | Descripción |
|-----|-------------|
| `https://tiendavirtual.ddev.site:8443/` | Catálogo (inicio) |
| `https://tiendavirtual.ddev.site:8443/catalog` | Catálogo + búsqueda básica |
| `https://tiendavirtual.ddev.site:8443/busqueda-avanzada` | Búsqueda avanzada |
| `https://tiendavirtual.ddev.site:8443/cart` | Carrito de compras |
| `https://tiendavirtual.ddev.site:8443/user/register` | Registro de clientes |
| `https://tiendavirtual.ddev.site:8443/user/login` | Inicio de sesión |
| `https://tiendavirtual.ddev.site:8443/admin` | Panel de administración |

---

## Módulos implementados

| Módulo | Funcionalidad |
|--------|--------------|
| **Registro / Login** | Drupal core — `/user/register`, `/user/login` |
| **Búsqueda básica** | `/catalog?keyword=…` + filtro por categoría |
| **Búsqueda avanzada** | `/busqueda-avanzada` — keyword + categoría + rango de precio + orden |
| **Artículos y características** | 20 productos con imagen, descripción, stock, precio y categoría |
| **Vista rápida (modal)** | Clic en imagen → modal con descripción ampliada |
| **Compra por artículo** | Página de producto con selector de cantidad + "Agregar al carrito" |
| **Carrito de compras** | `/cart` — artículos, cantidades y total |
| **Checkout** | Flujo guiado de Commerce |

---

## Catálogo (20 productos)

| Categoría | Productos |
|-----------|-----------|
| Electrónica | Laptop HP · Samsung Galaxy S23 · Sony WH-1000XM5 · Apple iPad Air · Monitor Dell 24" |
| Libros | El Código Da Vinci · Harry Potter · Cien Años de Soledad · El Principito · Don Quijote |
| Ropa y Calzado | Polo Ralph Lauren · Levi's 501 · Nike Air Max 270 · North Face Resolve · Vestido Zara |
| Deportes | Trek Marlin 5 MTB · Raqueta Wilson · Pelota Adidas · Guantes Everlast · Colchoneta Manduka |

---

## Comandos útiles

```bash
ddev start          # Iniciar proyecto
ddev stop           # Detener contenedores
ddev restart        # Reiniciar
ddev ssh            # Acceder al contenedor web
ddev drush cr       # Limpiar caché de Drupal
ddev drush uli      # Generar enlace de login sin contraseña
ddev logs           # Ver logs del servidor web
ddev describe       # Ver puertos, URLs y servicios activos
```

## Estructura del proyecto

```
tsp/
├── .ddev/
│   └── config.yaml                  # Configuración DDEV
├── scripts/
│   ├── ddev-install.sh              # Instalación automática (primera vez)
│   └── setup_store.php              # Configura tienda y crea 20 productos
└── web/modules/custom/
    └── tienda_virtual/
        ├── tienda_virtual.info.yml
        ├── tienda_virtual.routing.yml
        ├── tienda_virtual.module
        ├── tienda_virtual.libraries.yml
        ├── src/Controller/CatalogController.php
        ├── templates/
        │   ├── tienda-catalog.html.twig
        │   └── tienda-search-advanced.html.twig
        ├── css/tienda_virtual.css
        ├── js/tienda_virtual.js
        └── images/no-image.png
```
