<?php

/**
 * @file
 * Fix product teaser display: clean price, hidden labels, no add-to-cart form.
 *
 * Usage: ddev drush php:script /var/www/html/scripts/fix_teaser_display.php
 */

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

// ── Fix PRODUCT teaser display ──────────────────────────────────────────────
$product_teaser = \Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('commerce_product.default.teaser');

if (!$product_teaser) {
  echo "ERROR: product teaser display not found\n";
  exit(1);
}

// Image field — label hidden, medium style, link to content.
$product_teaser->setComponent('field_product_image', [
  'type'     => 'image',
  'label'    => 'hidden',
  'weight'   => -10,
  'settings' => [
    'image_style' => 'medium',
    'image_link'  => 'content',
  ],
]);

// Title — label hidden.
$product_teaser->setComponent('title', [
  'type'     => 'string',
  'label'    => 'hidden',
  'weight'   => 0,
  'settings' => ['link_to_entity' => FALSE],
]);

// Variations — use commerce_add_to_cart but with no quantity / no button.
// The template will wrap this in a styled price div.
$product_teaser->setComponent('variations', [
  'type'     => 'commerce_add_to_cart',
  'label'    => 'hidden',
  'weight'   => 10,
  'settings' => [
    'default_quantity' => '1',
    'combine'          => TRUE,
    'show_quantity'    => FALSE,
  ],
]);

$product_teaser->save();
echo "✓ Product teaser display updated\n";

// ── Create / fix VARIATION teaser view mode ──────────────────────────────────
// Ensure there's a variation display that only shows the price (no label).
$var_teaser = \Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('commerce_product_variation.default.teaser');

if (!$var_teaser) {
  // Check if variation view mode exists.
  $vm = \Drupal::entityTypeManager()
    ->getStorage('entity_view_mode')
    ->load('commerce_product_variation.teaser');
  if (!$vm) {
    EntityViewMode::create([
      'id'               => 'commerce_product_variation.teaser',
      'label'            => 'Teaser',
      'targetEntityType' => 'commerce_product_variation',
    ])->save();
    echo "✓ Variation teaser view mode created\n";
  }
  $var_teaser = EntityViewDisplay::create([
    'targetEntityType' => 'commerce_product_variation',
    'bundle'           => 'default',
    'mode'             => 'teaser',
    'status'           => TRUE,
  ]);
}

// Show ONLY price, hidden label.
$var_teaser->removeComponent('title');
$var_teaser->setComponent('price', [
  'type'     => 'commerce_price_default',
  'label'    => 'hidden',
  'weight'   => 0,
  'settings' => ['strip_trailing_zeroes' => FALSE, 'display_currency_code' => FALSE],
]);
$var_teaser->save();
echo "✓ Variation teaser display updated (price only)\n";

echo "\nDone! Run: ddev drush cr\n";
