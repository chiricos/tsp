<?php
/**
 * fix_book_images.php
 * Ejecutar: ddev drush php:script /var/www/html/scripts/fix_book_images.php
 *
 * 1. Crea image style 'book_cover' con Scale and Crop 300×450
 * 2. Actualiza el teaser display para usarlo
 * 3. Limpia caché de imágenes derivadas anteriores
 */

use Drupal\image\Entity\ImageStyle;

// ── 1. Crear / actualizar image style book_cover ───────────────────────────
echo "🖼️  Configurando image style 'book_cover'...\n";

$style = ImageStyle::load('book_cover');
if (!$style) {
  $style = ImageStyle::create([
    'name'  => 'book_cover',
    'label' => 'Portada de libro (300×450)',
  ]);
  $style->save();
  echo "   Image style creado.\n";
} else {
  foreach ($style->getEffects() as $e) {
    $style->deleteImageEffect($e);
  }
  echo "   Image style existente, efectos limpiados.\n";
}

// Efecto: Scale and Crop centrado a 300×450 (ratio 2:3 — portada estándar)
$effect_manager = \Drupal::service('plugin.manager.image.effect');
$config = [
  'id'     => 'image_scale_and_crop',
  'data'   => [
    'width'  => 300,
    'height' => 450,
    'anchor' => 'center-center',
  ],
  'weight' => 0,
  'uuid'   => \Drupal::service('uuid')->generate(),
];
$style->addImageEffect($config);
$style->save();
echo "   Efecto Scale and Crop 300×450 (center) aplicado.\n";

// ── 2. Actualizar el teaser display ───────────────────────────────────────
echo "\n📋 Actualizando display Teaser de commerce_product...\n";

$display = \Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('commerce_product.default.teaser');

if ($display) {
  $comp = $display->getComponent('field_product_image');
  $comp['settings']['image_style'] = 'book_cover';
  $display->setComponent('field_product_image', $comp);
  $display->save();
  echo "   Display Teaser actualizado: field_product_image → book_cover.\n";
} else {
  echo "   ⚠️  Display Teaser no encontrado.\n";
}

// ── 3. Limpiar imágenes derivadas antiguas ────────────────────────────────
echo "\n🗑️  Limpiando caché de imágenes derivadas...\n";

/** @var \Drupal\Core\File\FileSystemInterface $fs */
$fs = \Drupal::service('file_system');

foreach (['large', 'book_cover'] as $style_id) {
  $dir = $fs->realpath("public://styles/{$style_id}/public/products");
  if ($dir && is_dir($dir)) {
    $files = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    $count = 0;
    foreach ($files as $f) {
      @unlink($f);
      $count++;
    }
    echo "   {$style_id}: {$count} archivos eliminados.\n";
  }
}

// ── 4. Limpiar caché de Drupal ─────────────────────────────────────────────
echo "\n🔄 Limpiando caché...\n";
drupal_flush_all_caches();
echo "   Caché limpiado.\n";

echo "\n✅ Listo. Recarga /productos para ver las portadas uniformes.\n";
