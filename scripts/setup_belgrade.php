<?php

/**
 * @file
 * Setup Belgrade theme configuration and product display modes.
 *
 * Usage: ddev drush php:script /var/www/html/scripts/setup_belgrade.php
 */

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

// 1. Create teaser view mode for commerce_product.
$view_modes = Drupal::entityTypeManager()
  ->getStorage('entity_view_mode')
  ->loadByProperties([
    'targetEntityType' => 'commerce_product',
    'id' => 'commerce_product.teaser',
  ]);

if (empty($view_modes)) {
  $view_mode = EntityViewMode::create([
    'id' => 'commerce_product.teaser',
    'label' => 'Teaser',
    'targetEntityType' => 'commerce_product',
  ]);
  $view_mode->save();
  echo "✓ Teaser view mode created\n";
}
else {
  echo "- Teaser view mode already exists\n";
}

// 2. Create teaser view display for commerce_product:default.
$existing = Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('commerce_product.default.teaser');

if (!$existing) {
  $teaser_display = EntityViewDisplay::create([
    'targetEntityType' => 'commerce_product',
    'bundle' => 'default',
    'mode' => 'teaser',
    'status' => TRUE,
  ]);

  $teaser_display->setComponent('field_product_image', [
    'label' => 'hidden',
    'type' => 'image',
    'weight' => -10,
    'settings' => [
      'image_style' => 'medium',
      'image_link' => 'content',
    ],
  ]);

  $teaser_display->setComponent('title', [
    'label' => 'hidden',
    'type' => 'string',
    'weight' => 0,
  ]);

  // Show price via variations rendered formatter.
  $teaser_display->setComponent('variations', [
    'label' => 'hidden',
    'type' => 'commerce_add_to_cart',
    'weight' => 10,
    'settings' => [
      'default_quantity' => '1',
      'combine' => TRUE,
      'show_quantity' => FALSE,
    ],
  ]);

  $teaser_display->save();
  echo "✓ Teaser display created\n";
}
else {
  echo "- Teaser display already exists\n";
}

// 3. Set Belgrade theme settings.
$config = Drupal::service('config.factory')->getEditable('belgrade.settings');
$config->set('product_teaser', 'card');
$config->set('product_image_display', 'default');
$config->save();
echo "✓ Belgrade theme settings saved (product_teaser=card)\n";

echo "\nDone! Run: ddev drush cr\n";
