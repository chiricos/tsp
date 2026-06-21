<?php
/**
 * Importa traducciones al español para Drupal Commerce.
 * Ejecutar: ddev drush php:script /var/www/html/scripts/translate_commerce.php
 */

$langcode = 'es';

$strings = [
  // Carrito
  'Shopping cart'            => 'Carrito de compras',
  'Add to cart'              => 'Añadir al carrito',
  'Add to Cart'              => 'Añadir al carrito',
  'Añadir a la cesta'        => 'Añadir al carrito',
  'View cart'                => 'Ver carrito',
  'Your cart is empty.'      => 'Tu carrito está vacío.',
  'Update cart'              => 'Actualizar carrito',
  'Remove'                   => 'Eliminar',
  'Quantity'                 => 'Cantidad',
  'Price'                    => 'Precio',
  'Subtotal'                 => 'Subtotal',
  'Total'                    => 'Total',
  'Proceed to checkout'      => 'Proceder al pago',
  'Empty cart'               => 'Vaciar carrito',
  'Cart'                     => 'Carrito',
  // Checkout pasos
  'Checkout'                 => 'Finalizar compra',
  'Order summary'            => 'Resumen del pedido',
  'Shipping information'     => 'Información de envío',
  'Payment information'      => 'Información de pago',
  'Review'                   => 'Revisar pedido',
  'Complete purchase'        => 'Completar compra',
  'Pay'                      => 'Pagar',
  'Back'                     => 'Volver',
  'Continue to review'       => 'Continuar a revisión',
  'Contact information'      => 'Información de contacto',
  'Billing information'      => 'Información de facturación',
  'Continue to payment'      => 'Continuar al pago',
  'Continue to review'       => 'Continuar a revisión',
  'Place order'              => 'Realizar pedido',
  // Campos de dirección
  'First name'               => 'Nombre',
  'Last name'                => 'Apellido',
  'Company'                  => 'Empresa',
  'Street address'           => 'Dirección',
  'Apartment, suite, etc.'   => 'Apartamento, suite, etc.',
  'City'                     => 'Ciudad',
  'Country'                  => 'País',
  'State'                    => 'Departamento',
  'Postal code'              => 'Código postal',
  'Phone'                    => 'Teléfono',
  'Email'                    => 'Correo electrónico',
  'Address'                  => 'Dirección',
  // Mensajes de pedido
  'Your order number is @number.' => 'Tu número de pedido es @number.',
  'Your order has been placed.'   => 'Tu pedido ha sido realizado.',
  'Thank you for shopping with us!' => '¡Gracias por comprar con nosotros!',
  'Order @number'            => 'Pedido @number',
  'Order items'              => 'Artículos del pedido',
  'Unit price'               => 'Precio unitario',
  // Pago
  'Payment method'           => 'Método de pago',
  'Credit card'              => 'Tarjeta de crédito',
  'Card number'              => 'Número de tarjeta',
  'Expiration date'          => 'Fecha de expiración',
  'CVV'                      => 'CVV',
  'Example payment'          => 'Pago de ejemplo',
  // Estado de pedidos
  'Draft'                    => 'Borrador',
  'Pending'                  => 'Pendiente',
  'Completed'                => 'Completado',
  'Canceled'                 => 'Cancelado',
  'Placed'                   => 'Realizado',
  'Validation'               => 'Validación',
  'Fulfillment'              => 'Procesando',
];

$db = \Drupal::database();
$count = 0;

foreach ($strings as $source => $translation) {
  // Buscar o crear la cadena fuente
  $lid = $db->select('locales_source', 'ls')
    ->fields('ls', ['lid'])
    ->condition('ls.source', $source)
    ->execute()->fetchField();

  if (!$lid) {
    $lid = $db->insert('locales_source')
      ->fields(['source' => $source, 'context' => '', 'version' => '1'])
      ->execute();
  }

  // Insertar o actualizar la traducción
  $existing = $db->select('locales_target', 'lt')
    ->fields('lt', ['lid'])
    ->condition('lt.lid', $lid)
    ->condition('lt.language', $langcode)
    ->execute()->fetchField();

  if (!$existing) {
    $db->insert('locales_target')
      ->fields(['lid' => $lid, 'language' => $langcode, 'translation' => $translation, 'customized' => 1])
      ->execute();
  } else {
    $db->update('locales_target')
      ->fields(['translation' => $translation, 'customized' => 1])
      ->condition('lid', $lid)
      ->condition('language', $langcode)
      ->execute();
  }
  $count++;
  echo "OK: $source\n";
}

echo "\n✓ $count traducciones importadas.\n";
