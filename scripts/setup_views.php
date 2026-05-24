<?php

/**
 * @file
 * Create Views for the product catalog (matches Belgrade template: views-view--product-catalog.html.twig).
 *
 * Usage: ddev drush php:script /var/www/html/scripts/setup_views.php
 */

use Drupal\views\Entity\View;

// Check if view already exists.
$existing = Drupal::entityTypeManager()->getStorage('view')->load('product_catalog');
if ($existing) {
  echo "- product_catalog view already exists, deleting and recreating...\n";
  $existing->delete();
}

// Build the view config array.
$view_config = [
  'langcode' => 'es',
  'status' => TRUE,
  'dependencies' => [],
  'id' => 'product_catalog',
  'label' => 'Product catalog',
  'module' => 'views',
  'description' => 'Catálogo de productos con filtros',
  'tag' => 'commerce',
  'base_table' => 'commerce_product_field_data',
  'base_field' => 'product_id',
  'display' => [
    'default' => [
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Master',
      'position' => 0,
      'display_options' => [
        'title' => 'Products',
        'pager' => [
          'type' => 'mini',
          'options' => [
            'offset' => 0,
            'items_per_page' => 12,
            'total_pages' => NULL,
            'id' => 0,
            'tags' => [
              'first' => '« First',
              'previous' => '‹ Previous',
              'next' => 'Next ›',
              'last' => 'Last »',
            ],
            'expose' => [
              'items_per_page' => FALSE,
              'items_per_page_label' => 'Items per page',
              'items_per_page_options' => '5, 10, 25, 50',
              'items_per_page_options_all' => FALSE,
              'items_per_page_options_all_label' => '- All -',
              'offset' => FALSE,
              'offset_label' => 'Offset',
            ],
          ],
        ],
        'style' => [
          'type' => 'default',
          'options' => [],
        ],
        'row' => [
          'type' => 'entity:commerce_product',
          'options' => [
            'relationship' => 'none',
            'view_mode' => 'teaser',
          ],
        ],
        'fields' => [],
        'filters' => [
          'status' => [
            'id' => 'status',
            'table' => 'commerce_product_field_data',
            'field' => 'status',
            'relationship' => 'none',
            'group_type' => 'group',
            'admin_label' => '',
            'operator' => '=',
            'value' => '1',
            'group' => 1,
            'exposed' => FALSE,
            'expose' => [
              'operator_id' => '',
              'label' => '',
              'description' => '',
              'use_operator' => FALSE,
              'operator' => '',
              'identifier' => '',
              'required' => FALSE,
              'remember' => FALSE,
              'multiple' => FALSE,
              'remember_roles' => [
                'authenticated' => 'authenticated',
              ],
            ],
            'is_grouped' => FALSE,
            'group_info' => [
              'label' => '',
              'description' => '',
              'identifier' => '',
              'optional' => TRUE,
              'widget' => 'select',
              'multiple' => FALSE,
              'remember' => FALSE,
              'default_group' => 'All',
              'default_group_multiple' => [],
              'group_items' => [],
            ],
            'plugin_id' => 'boolean',
          ],
        ],
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'commerce_product_field_data',
            'field' => 'created',
            'relationship' => 'none',
            'group_type' => 'group',
            'admin_label' => '',
            'operator' => 'DESC',
            'order' => 'DESC',
            'exposed' => FALSE,
            'expose' => [
              'label' => '',
            ],
            'plugin_id' => 'date',
          ],
        ],
        'header' => [],
        'footer' => [],
        'empty' => [],
        'relationships' => [],
        'arguments' => [],
        'display_extenders' => [],
        'cache' => [
          'type' => 'tag',
          'options' => [],
        ],
        'query' => [
          'type' => 'views_query',
          'options' => [
            'query_comment' => '',
            'disable_sql_rewrite' => FALSE,
            'distinct' => FALSE,
            'replica' => FALSE,
            'query_tags' => [],
          ],
        ],
        'exposed_form' => [
          'type' => 'basic',
          'options' => [
            'submit_button' => 'Buscar',
            'reset_button' => FALSE,
            'reset_button_label' => 'Reset',
            'exposed_sorts_label' => 'Sort by',
            'expose_sort_order' => TRUE,
            'sort_asc_label' => 'Asc',
            'sort_desc_label' => 'Desc',
          ],
        ],
        'access' => [
          'type' => 'none',
          'options' => [],
        ],
        'use_ajax' => FALSE,
        'use_more' => FALSE,
        'use_more_always' => TRUE,
        'use_more_text' => 'more',
        'css_class' => 'row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4',
      ],
    ],
    'page_1' => [
      'display_plugin' => 'page',
      'id' => 'page_1',
      'display_title' => 'Catálogo',
      'position' => 1,
      'display_options' => [
        'path' => 'productos',
        'display_extenders' => [],
      ],
      'cache_metadata' => [
        'max-age' => -1,
        'contexts' => [
          'languages:language_content',
          'languages:language_interface',
          'url',
          'url.query_args',
          'user.permissions',
        ],
        'tags' => [],
      ],
    ],
  ],
];

$view = View::create($view_config);
$view->save();

echo "✓ product_catalog view created at /productos\n";

// Also create a featured products view block.
$existing_featured = Drupal::entityTypeManager()->getStorage('view')->load('featured_products');
if ($existing_featured) {
  $existing_featured->delete();
}

$featured_config = [
  'langcode' => 'es',
  'status' => TRUE,
  'id' => 'featured_products',
  'label' => 'Featured Products',
  'module' => 'views',
  'description' => 'Últimos productos para la homepage',
  'tag' => 'commerce',
  'base_table' => 'commerce_product_field_data',
  'base_field' => 'product_id',
  'display' => [
    'default' => [
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Master',
      'position' => 0,
      'display_options' => [
        'title' => 'New products',
        'pager' => [
          'type' => 'some',
          'options' => [
            'items_per_page' => 4,
            'offset' => 0,
          ],
        ],
        'style' => [
          'type' => 'default',
          'options' => [],
        ],
        'row' => [
          'type' => 'entity:commerce_product',
          'options' => [
            'relationship' => 'none',
            'view_mode' => 'teaser',
          ],
        ],
        'fields' => [],
        'filters' => [
          'status' => [
            'id' => 'status',
            'table' => 'commerce_product_field_data',
            'field' => 'status',
            'operator' => '=',
            'value' => '1',
            'group' => 1,
            'exposed' => FALSE,
            'plugin_id' => 'boolean',
          ],
        ],
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'commerce_product_field_data',
            'field' => 'created',
            'order' => 'DESC',
            'operator' => 'DESC',
            'exposed' => FALSE,
            'plugin_id' => 'date',
          ],
        ],
        'header' => [],
        'footer' => [],
        'empty' => [],
        'css_class' => 'row row-cols-2 row-cols-md-4 g-4',
        'access' => ['type' => 'none', 'options' => []],
        'cache' => ['type' => 'tag', 'options' => []],
        'query' => [
          'type' => 'views_query',
          'options' => [],
        ],
        'exposed_form' => ['type' => 'basic', 'options' => []],
        'display_extenders' => [],
      ],
    ],
    'block_new' => [
      'display_plugin' => 'block',
      'id' => 'block_new',
      'display_title' => 'Block: New products',
      'position' => 1,
      'display_options' => [
        'display_extenders' => [],
        'block_description' => 'New products',
        'block_category' => 'Tienda',
      ],
      'cache_metadata' => [
        'max-age' => -1,
        'contexts' => [
          'languages:language_content',
          'languages:language_interface',
          'user.permissions',
        ],
        'tags' => [],
      ],
    ],
    'block_bestsellers' => [
      'display_plugin' => 'block',
      'id' => 'block_bestsellers',
      'display_title' => 'Block: Best sellers',
      'position' => 2,
      'display_options' => [
        'title' => 'Best sellers',
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'commerce_product_field_data',
            'field' => 'created',
            'order' => 'ASC',
            'operator' => 'ASC',
            'exposed' => FALSE,
            'plugin_id' => 'date',
          ],
        ],
        'display_extenders' => [],
        'block_description' => 'Best sellers',
        'block_category' => 'Tienda',
      ],
      'cache_metadata' => [
        'max-age' => -1,
        'contexts' => [
          'languages:language_content',
          'languages:language_interface',
          'user.permissions',
        ],
        'tags' => [],
      ],
    ],
  ],
];

$featured_view = View::create($featured_config);
$featured_view->save();

echo "✓ featured_products view created with blocks\n";
echo "\nDone! Run: ddev drush cr\n";
