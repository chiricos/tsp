<?php

/**
 * @file
 * Update product_catalog view to match Commerce Kickstart demo layout.
 *
 * Fixes:
 *  - Removes wrong CSS class from view wrapper
 *  - Sets per-row Bootstrap column classes (col-lg-4 col-md-6)
 *  - Adds exposed keyword search + sort filters
 *
 * Usage: ddev drush php:script /var/www/html/scripts/fix_catalog_view.php
 */

use Drupal\views\Entity\View;

$view = View::load('product_catalog');
if (!$view) {
  echo "ERROR: product_catalog view not found.\n";
  exit(1);
}

$display = &$view->getDisplay('default');

// 1. Remove wrong CSS class from view wrapper (it conflicts with template's own row).
$display['display_options']['css_class'] = '';

// 2. Set Bootstrap column class on every row (col-lg-4 col-md-6).
$display['display_options']['style'] = [
  'type' => 'default',
  'options' => [
    'row_class'          => 'col-lg-4 col-md-6',
    'default_row_class'  => TRUE,
  ],
];

// 3. Add keyword search exposed filter.
$display['display_options']['filters']['title'] = [
  'id'            => 'title',
  'table'         => 'commerce_product_field_data',
  'field'         => 'title',
  'relationship'  => 'none',
  'group_type'    => 'group',
  'admin_label'   => 'Keyword',
  'operator'      => 'CONTAINS',
  'value'         => '',
  'group'         => 1,
  'exposed'       => TRUE,
  'expose'        => [
    'operator_id'  => 'title_op',
    'label'        => 'Keyword search',
    'description'  => '',
    'use_operator' => FALSE,
    'operator'     => 'title_op',
    'identifier'   => 'keyword_search',
    'required'     => FALSE,
    'remember'     => FALSE,
    'multiple'     => FALSE,
    'remember_roles' => ['authenticated' => 'authenticated'],
  ],
  'is_grouped'    => FALSE,
  'plugin_id'     => 'string',
];

// 4. Add exposed sort (combined sort by title / created / price).
$display['display_options']['sorts']['title'] = [
  'id'           => 'title',
  'table'        => 'commerce_product_field_data',
  'field'        => 'title',
  'relationship' => 'none',
  'group_type'   => 'group',
  'admin_label'  => '',
  'operator'     => 'ASC',
  'order'        => 'ASC',
  'exposed'      => TRUE,
  'expose'       => [
    'label'        => 'Sort by',
    'field_identifier' => 'title',
  ],
  'plugin_id'    => 'standard',
];

// 5. Exposed form settings.
$display['display_options']['exposed_form'] = [
  'type'    => 'basic',
  'options' => [
    'submit_button'       => 'Apply',
    'reset_button'        => FALSE,
    'reset_button_label'  => 'Reset',
    'exposed_sorts_label' => 'Sort by',
    'expose_sort_order'   => TRUE,
    'sort_asc_label'      => 'A → Z / Newer',
    'sort_desc_label'     => 'Z → A / Older',
  ],
];

// Also fix the page_1 display (inherits from default).
$page = &$view->getDisplay('page_1');
$page['display_options']['display_extenders'] = [];

$view->save();
echo "✓ product_catalog view updated\n";

// Also update featured_products view rows.
$fv = View::load('featured_products');
if ($fv) {
  foreach (['default', 'block_new', 'block_bestsellers'] as $did) {
    $d = &$fv->getDisplay($did);
    $d['display_options']['style'] = [
      'type'    => 'default',
      'options' => [
        'row_class'         => 'col-6 col-md-3',
        'default_row_class' => TRUE,
      ],
    ];
    $d['display_options']['css_class'] = '';
  }
  $fv->save();
  echo "✓ featured_products view updated\n";
}

echo "\nDone! Run: ddev drush cr\n";
