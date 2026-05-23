Actúa como un experto en Drupal 10/11, comercio electrónico y arquitectura de software.

Contexto:
Estoy desarrollando un sistema tipo e-commerce en Drupal.

Necesito implementar los siguientes módulos:

0. Tienda virtual de venta de libros

1. Registro e inicio de sesión:
- Usuarios deben autenticarse para comprar
- Usar sistema de usuarios de Drupal

2. Búsqueda:
- Búsqueda básica por nombre (Views o Search API)
- Búsqueda avanzada con filtros (categoría, precio, atributos)

3. Catálogo de productos:
- Mínimo 20 productos
- Cada producto debe tener:
  - título
  - descripción
  - imagen
  - stock
- Al hacer clic en la imagen, mostrar página de detalle (node view)

4. Compra por artículo:
- Selección de cantidad
- Agregar al carrito

5. Carrito de compras:
- Mostrar productos agregados
- Precio total
- Cantidad total

6. Usar theme 
- usa commerce kickstart
- adecua a lo que tenemos anterior

Reglas:
- Usar módulos estándar de Drupal (Views, Commerce si aplica)
- No modificar core ni contrib
- Aplicar buenas prácticas Drupal
- Usar entidades y fields correctamente

Salida esperada:
- Qué módulos usar (core/contrib)
- Cómo configurarlo paso a paso
- Ejemplo de estructura (content types, views, etc.)
- Comandos drush si aplica