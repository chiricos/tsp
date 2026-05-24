<?php

/**
 * @file
 * Setup homepage node with product blocks for Belgrade theme.
 *
 * Usage: ddev drush php:script /var/www/html/scripts/setup_homepage.php
 */

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\block\Entity\Block;

// 1. Ensure the 'page' content type exists and enable Layout Builder on it.
$page_type = NodeType::load('page');
if (!$page_type) {
  echo "Creating 'page' content type...\n";
  $page_type = NodeType::create([
    'type' => 'page',
    'name' => 'Basic page',
    'description' => 'Use for simple pages.',
  ]);
  $page_type->save();
  echo "✓ Page content type created\n";
}

// 2. Check for existing homepage node.
$query = Drupal::entityQuery('node')
  ->condition('type', 'page')
  ->condition('title', 'Homepage')
  ->accessCheck(FALSE)
  ->range(0, 1);
$nids = $query->execute();

if (!empty($nids)) {
  $homepage = Node::load(reset($nids));
  echo "- Homepage node already exists (nid=" . $homepage->id() . "), updating...\n";
}
else {
  $homepage = Node::create([
    'type' => 'page',
    'title' => 'Homepage',
    'status' => 1,
    'uid' => 1,
  ]);
  $homepage->save();
  echo "✓ Homepage node created (nid=" . $homepage->id() . ")\n";
}

// 3. Set homepage as the front page.
Drupal::service('config.factory')
  ->getEditable('system.site')
  ->set('page.front', '/node/' . $homepage->id())
  ->save();
echo "✓ Front page set to /node/" . $homepage->id() . "\n";

// 4. Place featured products blocks in the 'content' region.
$block_storage = Drupal::entityTypeManager()->getStorage('block');

// Place "New products" block.
$block_new_id = 'belgrade_featured_products_new';
$existing_block = $block_storage->load($block_new_id);
if ($existing_block) {
  $existing_block->delete();
}

Block::create([
  'id' => $block_new_id,
  'theme' => 'belgrade',
  'region' => 'content',
  'plugin' => 'views_block:featured_products-block_new',
  'settings' => [
    'id' => 'views_block:featured_products-block_new',
    'label' => 'New products',
    'label_display' => 'visible',
    'views_label' => '',
    'items_per_page' => 'none',
  ],
  'visibility' => [
    'request_path' => [
      'id' => 'request_path',
      'pages' => '<front>',
      'negate' => FALSE,
      'context_mapping' => [],
    ],
  ],
  'weight' => 1,
])->save();
echo "✓ 'New products' block placed\n";

// Place "Best sellers" block.
$block_bs_id = 'belgrade_featured_products_bestsellers';
$existing_bs = $block_storage->load($block_bs_id);
if ($existing_bs) {
  $existing_bs->delete();
}

Block::create([
  'id' => $block_bs_id,
  'theme' => 'belgrade',
  'region' => 'content',
  'plugin' => 'views_block:featured_products-block_bestsellers',
  'settings' => [
    'id' => 'views_block:featured_products-block_bestsellers',
    'label' => 'Best sellers',
    'label_display' => 'visible',
    'views_label' => '',
    'items_per_page' => 'none',
  ],
  'visibility' => [
    'request_path' => [
      'id' => 'request_path',
      'pages' => '<front>',
      'negate' => FALSE,
      'context_mapping' => [],
    ],
  ],
  'weight' => 2,
])->save();
echo "✓ 'Best sellers' block placed\n";

echo "\nDone! Run: ddev drush cr\n";
echo "Visit: https://tiendavirtual.ddev.site:8443\n";
