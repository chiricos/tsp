<?php

namespace Drupal\tienda_virtual\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_product\Entity\Product;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides catalog and search pages for TiendaVirtual.
 */
class CatalogController extends ControllerBase {

  // ── Catalog (basic keyword + category filter) ───────────────────────────

  public function catalog(Request $request): array {
    $keyword     = trim((string) $request->query->get('keyword', ''));
    $category_id = (int) $request->query->get('category', 0);
    $per_page    = 12;
    $page        = max(1, (int) $request->query->get('page', 1));

    $all_products = $this->queryProducts($keyword, $category_id);
    $total        = count($all_products);
    $total_pages  = max(1, (int) ceil($total / $per_page));
    $page         = min($page, $total_pages);
    $products_data = array_slice($all_products, ($page - 1) * $per_page, $per_page);
    $categories   = $this->loadCategories();

    return [
      '#theme'       => 'tienda_catalog',
      '#products'    => $products_data,
      '#keyword'     => $keyword,
      '#category_id' => $category_id,
      '#categories'  => $categories,
      '#total'       => $total,
      '#page'        => $page,
      '#total_pages' => $total_pages,
      '#per_page'    => $per_page,
      '#page_range'  => $this->buildPageRange($page, $total_pages),
      '#attached'    => ['library' => ['tienda_virtual/tienda_virtual']],
      '#cache'       => ['max-age' => 0],
    ];
  }

  // ── Advanced search (keyword + category + price range + sort) ───────────

  public function advancedSearch(Request $request): array {
    $keyword     = trim((string) $request->query->get('keyword', ''));
    $category_id = (int) $request->query->get('category', 0);
    $price_min   = $request->query->get('price_min', '');
    $price_max   = $request->query->get('price_max', '');
    $sort        = $request->query->get('sort', 'title_asc');

    $products_data = $this->queryProducts($keyword, $category_id);

    // Price filter (post-process because price is on variation entity)
    if ($price_min !== '' || $price_max !== '') {
      $products_data = array_values(array_filter(
        $products_data,
        static function (array $p) use ($price_min, $price_max): bool {
          $n = $p['price_number'];
          if ($price_min !== '' && $n < (float) $price_min) {
            return FALSE;
          }
          if ($price_max !== '' && $n > (float) $price_max) {
            return FALSE;
          }
          return TRUE;
        }
      ));
    }

    // Sort
    switch ($sort) {
      case 'price_asc':
        usort($products_data, static fn($a, $b) => $a['price_number'] <=> $b['price_number']);
        break;
      case 'price_desc':
        usort($products_data, static fn($a, $b) => $b['price_number'] <=> $a['price_number']);
        break;
      case 'title_desc':
        usort($products_data, static fn($a, $b) => strcmp($b['title'], $a['title']));
        break;
      default: // title_asc
        usort($products_data, static fn($a, $b) => strcmp($a['title'], $b['title']));
    }

    $categories = $this->loadCategories();

    return [
      '#theme'       => 'tienda_search_advanced',
      '#products'    => $products_data,
      '#keyword'     => $keyword,
      '#category_id' => $category_id,
      '#categories'  => $categories,
      '#price_min'   => $price_min,
      '#price_max'   => $price_max,
      '#sort'        => $sort,
      '#total'       => count($products_data),
      '#attached'    => ['library' => ['tienda_virtual/tienda_virtual']],
      '#cache'       => ['max-age' => 0],
    ];
  }

  // ── Private helpers ─────────────────────────────────────────────────────

  /**
   * Build product data arrays from entity query results.
   */
  private function queryProducts(string $keyword, int $category_id): array {
    $query = \Drupal::entityQuery('commerce_product')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('title');

    if ($keyword !== '') {
      $or = $query->orConditionGroup()
        ->condition('title', '%' . $keyword . '%', 'LIKE')
        ->condition('body', '%' . $keyword . '%', 'LIKE');
      $query->condition($or);
    }

    if ($category_id > 0) {
      $query->condition('field_category', $category_id);
    }

    $ids      = $query->execute();
    $products = Product::loadMultiple($ids);

    return $this->buildProductData($products);
  }

  /**
   * Convert Product entities into plain arrays for Twig templates.
   *
   * @param Product[] $products
   */
  private function buildProductData(array $products): array {
    $data              = [];
    $file_url_generator = \Drupal::service('file_url_generator');

    foreach ($products as $product) {
      $variation     = $product->getDefaultVariation();
      $price_number  = 0.0;
      $price_display = 'N/A';
      $stock         = 0;

      if ($variation) {
        $price = $variation->getPrice();
        if ($price) {
          $price_number  = (float) $price->getNumber();
          $formatter = \Drupal::service('commerce_price.currency_formatter');
          $price_display = $formatter->format($price->getNumber(), $price->getCurrencyCode());
        }
        if ($variation->hasField('field_stock')) {
          $stock = (int) $variation->get('field_stock')->value;
        }
      }

      // Product URL
      $url = $product->toUrl()->toString();

      // Image URL
      $image_url = base_path() . \Drupal::service('extension.list.module')
        ->getPath('tienda_virtual') . '/images/no-image.png';

      if ($product->hasField('field_product_image') &&
          !$product->get('field_product_image')->isEmpty()) {
        $item = $product->get('field_product_image')->first();
        if ($item && $item->entity) {
          $image_url = $file_url_generator->generateAbsoluteString(
            $item->entity->getFileUri()
          );
        }
      }

      // Category
      $category = '';
      if ($product->hasField('field_category') &&
          !$product->get('field_category')->isEmpty()) {
        $term = $product->get('field_category')->entity;
        if ($term) {
          $category = $term->getName();
        }
      }

      // Body summary
      $body = '';
      if (!$product->get('body')->isEmpty()) {
        $body_field = $product->get('body')->first();
        $body = $body_field->summary ?: mb_substr(strip_tags($body_field->value), 0, 160) . '…';
      }

      $data[] = [
        'id'            => $product->id(),
        'title'         => $product->getTitle(),
        'url'           => $url,
        'price_number'  => $price_number,
        'price_display' => $price_display,
        'stock'         => $stock,
        'image_url'     => $image_url,
        'category'      => $category,
        'body'          => $body,
      ];
    }

    return $data;
  }

  /**
   * Build a compact page range with ellipsis markers (0 = ellipsis).
   *
   * @return int[]
   */
  private function buildPageRange(int $current, int $total): array {
    if ($total <= 7) {
      return range(1, $total);
    }
    $pages = [1];
    $start = max(2, $current - 2);
    $end   = min($total - 1, $current + 2);
    if ($start > 2) {
      $pages[] = 0; // ellipsis
    }
    for ($i = $start; $i <= $end; $i++) {
      $pages[] = $i;
    }
    if ($end < $total - 1) {
      $pages[] = 0; // ellipsis
    }
    $pages[] = $total;
    return $pages;
  }

  /**
   * Load all product-category taxonomy terms.
   */
  private function loadCategories(): array {
    return \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'product_category']);
  }

}
