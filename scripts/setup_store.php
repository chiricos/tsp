<?php
/**
 * setup_store.php
 * Executed via: drush php:script /scripts/setup_store.php
 *
 * Configures the Commerce store, product fields, taxonomy, and creates 20+
 * sample products with placeholder images.
 */

use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_price\Price;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

// ── 1. Import USD currency ─────────────────────────────────────────────────
echo "💵 Importando moneda USD...\n";
try {
  /** @var \Drupal\commerce_price\CurrencyImporterInterface $importer */
  $importer = \Drupal::service('commerce_price.currency_importer');
  $importer->import('USD');
  echo "   USD importado.\n";
} catch (\Exception $e) {
  echo "   USD ya existe.\n";
}

// ── 2. Create store ────────────────────────────────────────────────────────
echo "🏪 Creando tienda...\n";
$stores = \Drupal::entityTypeManager()->getStorage('commerce_store')->loadMultiple();
if (empty($stores)) {
  $store = Store::create([
    'type'             => 'online',
    'uid'              => 1,
    'name'             => 'TiendaVirtual',
    'mail'             => 'tienda@example.com',
    'address'          => [
      'country_code'        => 'CO',
      'administrative_area' => 'CUN',
      'locality'            => 'Bogotá',
      'address_line1'       => 'Calle 100 #15-30',
      'postal_code'         => '110111',
    ],
    'default_currency' => 'USD',
    'billing_countries' => ['CO', 'US'],
  ]);
  $store->save();

  \Drupal::configFactory()
    ->getEditable('commerce_store.settings')
    ->set('default_store', $store->uuid())
    ->save();
  echo "   Tienda creada (ID: {$store->id()}).\n";
} else {
  $store = reset($stores);
  echo "   Tienda ya existe (ID: {$store->id()}).\n";
}

// ── 3. Create product-category taxonomy vocabulary ─────────────────────────
echo "🏷️  Creando vocabulario de categorías...\n";
if (!Vocabulary::load('product_category')) {
  Vocabulary::create([
    'vid'  => 'product_category',
    'name' => 'Categoría de Producto',
  ])->save();
  echo "   Vocabulario creado.\n";
} else {
  echo "   Vocabulario ya existe.\n";
}

// ── 4. Add field_product_image to commerce_product ─────────────────────────
echo "🖼️  Configurando campo de imagen en producto...\n";
if (!FieldStorageConfig::loadByName('commerce_product', 'field_product_image')) {
  FieldStorageConfig::create([
    'field_name'  => 'field_product_image',
    'entity_type' => 'commerce_product',
    'type'        => 'image',
    'cardinality' => 3,
  ])->save();

  FieldConfig::create([
    'field_name'  => 'field_product_image',
    'entity_type' => 'commerce_product',
    'bundle'      => 'default',
    'label'       => 'Imágenes del producto',
    'settings'    => [
      'file_extensions' => 'png gif jpg jpeg',
      'alt_field'       => TRUE,
      'alt_field_required' => FALSE,
    ],
  ])->save();
  echo "   Campo de imagen creado.\n";
} else {
  echo "   Campo de imagen ya existe.\n";
}

// ── 5. Add field_stock to commerce_product_variation ──────────────────────
echo "📦 Configurando campo stock en variación...\n";
if (!FieldStorageConfig::loadByName('commerce_product_variation', 'field_stock')) {
  FieldStorageConfig::create([
    'field_name'  => 'field_stock',
    'entity_type' => 'commerce_product_variation',
    'type'        => 'integer',
    'settings'    => ['unsigned' => TRUE],
  ])->save();

  FieldConfig::create([
    'field_name'  => 'field_stock',
    'entity_type' => 'commerce_product_variation',
    'bundle'      => 'default',
    'label'       => 'Stock',
    'settings'    => ['min' => 0, 'max' => ''],
  ])->save();
  echo "   Campo stock creado.\n";
} else {
  echo "   Campo stock ya existe.\n";
}

// ── 6. Add field_category to commerce_product ─────────────────────────────
echo "📂 Configurando campo categoría en producto...\n";
if (!FieldStorageConfig::loadByName('commerce_product', 'field_category')) {
  FieldStorageConfig::create([
    'field_name'  => 'field_category',
    'entity_type' => 'commerce_product',
    'type'        => 'entity_reference',
    'settings'    => ['target_type' => 'taxonomy_term'],
    'cardinality' => 1,
  ])->save();

  FieldConfig::create([
    'field_name'  => 'field_category',
    'entity_type' => 'commerce_product',
    'bundle'      => 'default',
    'label'       => 'Categoría',
    'settings'    => [
      'handler' => 'default:taxonomy_term',
      'handler_settings' => [
        'target_bundles' => ['product_category' => 'product_category'],
      ],
    ],
  ])->save();
  echo "   Campo categoría creado.\n";
} else {
  echo "   Campo categoría ya existe.\n";
}

// ── 7. Create taxonomy terms ───────────────────────────────────────────────
echo "🗂️  Creando categorías...\n";
$cat_names = ['Electrónica', 'Libros', 'Ropa y Calzado', 'Deportes'];
$cats = [];
foreach ($cat_names as $name) {
  $existing = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['name' => $name, 'vid' => 'product_category']);
  if ($existing) {
    $cats[$name] = reset($existing);
    echo "   Categoría '{$name}' ya existe.\n";
  } else {
    $term = Term::create(['vid' => 'product_category', 'name' => $name]);
    $term->save();
    $cats[$name] = $term;
    echo "   Categoría '{$name}' creada.\n";
  }
}

// ── 8. Helper: download image and return File entity ─────────────────────
/** @var \Drupal\Core\File\FileSystemInterface $fs */
$fs = \Drupal::service('file_system');
$products_dir = 'public://products';
$fs->prepareDirectory($products_dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

function download_product_image(string $url, string $filename): ?File {
  $destination = 'public://products/' . $filename;

  // Skip if already downloaded
  $existing = \Drupal::entityTypeManager()
    ->getStorage('file')
    ->loadByProperties(['uri' => $destination]);
  if ($existing) {
    return reset($existing);
  }

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_FOLLOWLOCATION => TRUE,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => FALSE,
  ]);
  $data = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if (!$data || $http_code !== 200) {
    echo "   ⚠️  No se pudo descargar imagen: {$url}\n";
    return NULL;
  }

  /** @var \Drupal\Core\File\FileSystemInterface $fs */
  $uri = \Drupal::service('file_system')->saveData($data, $destination, FileSystemInterface::EXISTS_REPLACE);
  if (!$uri) {
    return NULL;
  }

  $file = File::create(['uri' => $uri, 'status' => 1, 'uid' => 1]);
  $file->save();
  return $file;
}

// ── 9. Product definitions (20 products) ──────────────────────────────────
$products_data = [

  // ── Electrónica ────────────────────────────────────────────────────────
  [
    'title'       => 'Laptop HP Pavilion 15',
    'description' => '<p>Potente laptop con procesador <strong>Intel Core i7</strong> de 11ª generación, 16 GB de RAM DDR4 y SSD NVMe de 512 GB. Pantalla Full HD IPS de 15.6" con colores vibrantes y 300 nits de brillo. Incluye Windows 11 Home, retroiluminación de teclado y batería de hasta 8 horas. Perfecta para trabajo, estudio y entretenimiento.</p>',
    'price'       => '799.99',
    'stock'       => 25,
    'sku'         => 'ELEC-001',
    'category'    => 'Electrónica',
    'img_seed'    => 'hplaptop',
    'img_name'    => 'laptop-hp.jpg',
  ],
  [
    'title'       => 'Samsung Galaxy S23 Ultra',
    'description' => '<p>El smartphone más avanzado de Samsung con pantalla <strong>Dynamic AMOLED 2X de 6.8"</strong>, cámara principal de 200 MP, procesador Snapdragon 8 Gen 2 y batería de 5000 mAh con carga rápida de 45 W. S Pen integrado para mayor productividad. Almacenamiento de 256 GB con 12 GB de RAM.</p>',
    'price'       => '1199.99',
    'stock'       => 15,
    'sku'         => 'ELEC-002',
    'category'    => 'Electrónica',
    'img_seed'    => 'samsungs23',
    'img_name'    => 'samsung-galaxy.jpg',
  ],
  [
    'title'       => 'Sony WH-1000XM5 Auriculares',
    'description' => '<p>Auriculares over-ear con la <strong>mejor cancelación de ruido activa</strong> del mercado. Hasta 30 horas de batería, Bluetooth 5.2 multiconexión y calidad de audio Hi-Res LDAC. Nuevo diseño plegable más ligero. Incluye estuche de viaje premium y cable de audio de 3.5 mm.</p>',
    'price'       => '349.99',
    'stock'       => 30,
    'sku'         => 'ELEC-003',
    'category'    => 'Electrónica',
    'img_seed'    => 'sonyheadphones',
    'img_name'    => 'sony-headphones.jpg',
  ],
  [
    'title'       => 'Apple iPad Air 5ª Generación',
    'description' => '<p>iPad Air con <strong>chip M1 de Apple</strong>, pantalla Liquid Retina de 10.9" con True Tone y P3 de gama amplia. Cámara trasera de 12 MP con video 4K y delantera de 12 MP con Ultra Ancho y Centro Escena. Compatible con Apple Pencil 2ª gen. y Magic Keyboard. Wi‑Fi 6 + 5G opcional.</p>',
    'price'       => '749.99',
    'stock'       => 20,
    'sku'         => 'ELEC-004',
    'category'    => 'Electrónica',
    'img_seed'    => 'appleipad',
    'img_name'    => 'apple-ipad.jpg',
  ],
  [
    'title'       => 'Monitor Dell 24" Full HD',
    'description' => '<p>Monitor IPS de 24 pulgadas con resolución <strong>Full HD (1920×1080)</strong>, tiempo de respuesta de 5 ms y frecuencia de actualización de 75 Hz. Conectores HDMI 1.4, VGA y DisplayPort 1.2. Ajustable en altura, inclinación y pivote. Certificado Energy Star. Incluye cable HDMI.</p>',
    'price'       => '279.99',
    'stock'       => 18,
    'sku'         => 'ELEC-005',
    'category'    => 'Electrónica',
    'img_seed'    => 'dellmonitor',
    'img_name'    => 'dell-monitor.jpg',
  ],

  // ── Libros ─────────────────────────────────────────────────────────────
  [
    'title'       => 'El Código Da Vinci – Dan Brown',
    'description' => '<p>El profesor Robert Langdon es llamado al Louvre una noche para examinar un cadáver junto a un enigmático mensaje. Lo que sigue es una frenética persecución que lo enfrenta a una <strong>conspiración de siglos</strong> relacionada con el Santo Grial y el Opus Dei. Más de 80 millones de copias vendidas en todo el mundo. Tapa blanda, 489 páginas.</p>',
    'price'       => '16.99',
    'stock'       => 50,
    'sku'         => 'BOOK-001',
    'category'    => 'Libros',
    'img_seed'    => 'davincibook',
    'img_name'    => 'codigo-davinci.jpg',
  ],
  [
    'title'       => 'Harry Potter y la Piedra Filosofal',
    'description' => '<p>El primer libro de la saga más vendida de la historia. Harry Potter descubre en su undécimo cumpleaños que es un mago y que fue aceptado en <strong>Hogwarts</strong>, la escuela de magia y hechicería. Edición de lujo con ilustraciones exclusivas de Jim Kay. Tapa dura, 352 páginas. Apto para mayores de 9 años.</p>',
    'price'       => '14.99',
    'stock'       => 45,
    'sku'         => 'BOOK-002',
    'category'    => 'Libros',
    'img_seed'    => 'harrypotter',
    'img_name'    => 'harry-potter.jpg',
  ],
  [
    'title'       => 'Cien Años de Soledad – García Márquez',
    'description' => '<p>La obra cumbre del <strong>realismo mágico latinoamericano</strong>. La saga de siete generaciones de la familia Buendía en la mítica Macondo. Premio Nobel de Literatura 1982. Considerada una de las mejores novelas del siglo XX. Edición conmemorativa del 50 aniversario. Tapa dura con ilustraciones a color.</p>',
    'price'       => '13.99',
    'stock'       => 40,
    'sku'         => 'BOOK-003',
    'category'    => 'Libros',
    'img_seed'    => 'cienanos',
    'img_name'    => 'cien-anos.jpg',
  ],
  [
    'title'       => 'El Principito – Antoine de Saint-Exupéry',
    'description' => '<p>El libro más leído y traducido de la literatura francesa. Un pequeño príncipe que viaja por el universo aprendiendo sobre la vida, el amor y la amistad. Contiene el célebre fragmento: <em>"Lo esencial es invisible a los ojos."</em> Edición especial con los dibujos originales del autor a color. Tapa blanda, 96 páginas.</p>',
    'price'       => '10.99',
    'stock'       => 60,
    'sku'         => 'BOOK-004',
    'category'    => 'Libros',
    'img_seed'    => 'principito',
    'img_name'    => 'el-principito.jpg',
  ],
  [
    'title'       => 'Don Quijote de la Mancha – Cervantes',
    'description' => '<p>La primera novela moderna y una de las mayores obras de la <strong>literatura universal</strong>. El hidalgo Alonso Quijano enloquece leyendo libros de caballerías y decide convertirse en caballero andante. Edición académica con notas de la Real Academia Española. Dos tomos en estuche, 1376 páginas.</p>',
    'price'       => '12.99',
    'stock'       => 35,
    'sku'         => 'BOOK-005',
    'category'    => 'Libros',
    'img_seed'    => 'quijote',
    'img_name'    => 'don-quijote.jpg',
  ],

  // ── Ropa y Calzado ─────────────────────────────────────────────────────
  [
    'title'       => 'Polo Ralph Lauren Classic Fit',
    'description' => '<p>Camiseta polo de <strong>algodón pima 100%</strong> de máxima suavidad con el icónico logo del jinete bordado. Cuello de punto con dos botones, costura lateral para mejor ajuste. Lavable a máquina en frío. Disponible en azul, blanco, negro y rojo. Tallas S, M, L, XL y XXL.</p>',
    'price'       => '89.99',
    'stock'       => 35,
    'sku'         => 'ROPA-001',
    'category'    => 'Ropa y Calzado',
    'img_seed'    => 'poloralph',
    'img_name'    => 'polo-ralph.jpg',
  ],
  [
    'title'       => "Jeans Levi's 501 Original",
    'description' => "<p>El jean original desde 1873. Corte recto con cierre de botones de latón, pierna recta desde la cadera hasta el tobillo. Confeccionado en <strong>denim 100% algodón</strong> de 12.5 oz. Detalles de costura naranja clásicos. Disponible en lavado stonewash, negro y azul oscuro. Tallas 28–40, largo 30 y 32.</p>",
    'price'       => '89.99',
    'stock'       => 40,
    'sku'         => 'ROPA-002',
    'category'    => 'Ropa y Calzado',
    'img_seed'    => 'levisjeans',
    'img_name'    => 'levis-jeans.jpg',
  ],
  [
    'title'       => 'Nike Air Max 270 Zapatillas',
    'description' => '<p>Las Air Max 270 presentan la <strong>unidad Air más grande de Nike</strong> en calzado lifestyle, con 270° de aire visible en el talón para amortiguación superior. Parte superior de malla transpirable con estructura en la zona del mediopié. Suela de goma con diseño waffle. Disponibles en varias combinaciones de colores. Tallas 36–46.</p>',
    'price'       => '149.99',
    'stock'       => 28,
    'sku'         => 'ROPA-003',
    'category'    => 'Ropa y Calzado',
    'img_seed'    => 'nikeairmax',
    'img_name'    => 'nike-airmax.jpg',
  ],
  [
    'title'       => 'Chaqueta The North Face Resolve',
    'description' => '<p>Chaqueta impermeable ultraligera con tecnología <strong>DryVent™</strong> para protección contra lluvia y viento. Costuras completamente selladas. Bolsillo delantero con cierre que funciona como bolsa de empaque (packable). Cintura ajustable con cordón elástico. Disponible en varios colores. Tallas XS–XXL.</p>',
    'price'       => '169.99',
    'stock'       => 22,
    'sku'         => 'ROPA-004',
    'category'    => 'Ropa y Calzado',
    'img_seed'    => 'northfacejacket',
    'img_name'    => 'northface-jacket.jpg',
  ],
  [
    'title'       => 'Vestido Floral Manga Corta Zara',
    'description' => '<p>Elegante vestido midi con estampado floral primaveral. Confeccionado en <strong>viscosa fluida</strong> suave y transpirable. Escote en V con cordón ajustable, manga corta y largo hasta la rodilla. Cierre lateral con cremallera invisible. Forro interior. Perfecto para eventos casuales y al aire libre. Tallas XS–XL.</p>',
    'price'       => '59.99',
    'stock'       => 30,
    'sku'         => 'ROPA-005',
    'category'    => 'Ropa y Calzado',
    'img_seed'    => 'floralDress',
    'img_name'    => 'vestido-zara.jpg',
  ],

  // ── Deportes ───────────────────────────────────────────────────────────
  [
    'title'       => 'Bicicleta Trek Marlin 5 MTB 29"',
    'description' => '<p>Bicicleta de montaña con <strong>marco Alpha Gold Aluminum</strong> de alta resistencia y geometría optimizada para trail. Suspensión delantera SR Suntour XCT de 100 mm. Transmisión Shimano Altus de 21 velocidades. Frenos de disco mecánicos Tektro con rotores de 160/160 mm. Ruedas de 29". Disponible en tallas S, M y L.</p>',
    'price'       => '699.99',
    'stock'       => 10,
    'sku'         => 'DEPO-001',
    'category'    => 'Deportes',
    'img_seed'    => 'mtbbike',
    'img_name'    => 'trek-bike.jpg',
  ],
  [
    'title'       => 'Raqueta de Tenis Wilson Ultra 100',
    'description' => '<p>Raqueta de alto rendimiento con tecnología <strong>Power Rib y Parallel Drilling</strong> para mayor potencia y punto dulce ampliado. Cabeza de 100 in², peso de 300 g sin encordar, balance de 32 cm. Marco de grafito con fibra de carbono. Ideal para jugadores intermedios y avanzados. Incluye funda termorígida.</p>',
    'price'       => '179.99',
    'stock'       => 20,
    'sku'         => 'DEPO-002',
    'category'    => 'Deportes',
    'img_seed'    => 'tennisracket',
    'img_name'    => 'wilson-raqueta.jpg',
  ],
  [
    'title'       => 'Pelota de Fútbol Adidas Finale',
    'description' => '<p>Pelota oficial de alta calidad con diseño exclusivo de paneles termosoldados. <strong>Cámara de butilo</strong> de alta retención de aire. Cubierta TPU texturizada para mejor control de toque y comportamiento predecible en vuelo. Talla 5 reglamentaria. Apta para césped natural y artificial. Incluye inflador de regalo.</p>',
    'price'       => '49.99',
    'stock'       => 55,
    'sku'         => 'DEPO-003',
    'category'    => 'Deportes',
    'img_seed'    => 'soccerball',
    'img_name'    => 'adidas-pelota.jpg',
  ],
  [
    'title'       => 'Guantes de Boxeo Everlast Pro Style',
    'description' => '<p>Guantes de boxeo con exterior de <strong>cuero sintético de alta durabilidad</strong> y relleno de espuma EverDri de doble capa para máxima protección. Sistema de ventilación en la palma. Cierre de velcro ajustable para soporte óptimo de muñeca. Disponibles en 8, 10, 12, 14 y 16 oz. Certificados para entrenamiento y sparring.</p>',
    'price'       => '44.99',
    'stock'       => 25,
    'sku'         => 'DEPO-004',
    'category'    => 'Deportes',
    'img_seed'    => 'boxinggloves',
    'img_name'    => 'everlast-guantes.jpg',
  ],
  [
    'title'       => 'Colchoneta de Yoga Manduka PRO 6mm',
    'description' => '<p>La colchoneta de yoga más reconocida del mercado con <strong>garantía de por vida</strong>. Grosor de 6 mm para amortiguación superior de articulaciones. Superficie de alta densidad antideslizante en ambas caras. Dimensiones: 180 × 61 cm. Peso: 3.5 kg. Libre de PVC, látex y materiales tóxicos. Incluye correa de transporte.</p>',
    'price'       => '109.99',
    'stock'       => 35,
    'sku'         => 'DEPO-005',
    'category'    => 'Deportes',
    'img_seed'    => 'yogamat',
    'img_name'    => 'manduka-yoga.jpg',
  ],
];

// ── 10. Create products ────────────────────────────────────────────────────
echo "\n🛍️  Creando " . count($products_data) . " productos...\n";

foreach ($products_data as $i => $data) {
  $num = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
  echo "   [{$num}] {$data['title']}...\n";

  // Skip if SKU already exists
  $existing_var = \Drupal::entityTypeManager()
    ->getStorage('commerce_product_variation')
    ->loadByProperties(['sku' => $data['sku']]);
  if ($existing_var) {
    echo "        ↳ Ya existe, omitiendo.\n";
    continue;
  }

  // Download image
  $img_url = "https://picsum.photos/seed/{$data['img_seed']}/800/600";
  $file = download_product_image($img_url, $data['img_name']);

  // Create variation
  $variation = ProductVariation::create([
    'type'        => 'default',
    'sku'         => $data['sku'],
    'price'       => new Price($data['price'], 'USD'),
    'field_stock' => (int) $data['stock'],
    'status'      => 1,
  ]);
  $variation->save();

  // Build product array
  $product_fields = [
    'type'           => 'default',
    'title'          => $data['title'],
    'body'           => ['value' => $data['description'], 'format' => 'basic_html'],
    'stores'         => [$store],
    'variations'     => [$variation],
    'field_category' => [['target_id' => $cats[$data['category']]->id()]],
    'status'         => 1,
  ];

  if ($file) {
    $product_fields['field_product_image'] = [
      ['target_id' => $file->id(), 'alt' => $data['title']],
    ];
  }

  $product = Product::create($product_fields);
  $product->save();
}

echo "\n✅ " . count($products_data) . " productos creados.\n";

// ── 11. Configure user registration ───────────────────────────────────────
echo "\n👤 Configurando registro de usuarios...\n";
\Drupal::configFactory()->getEditable('user.settings')
  ->set('register', 'visitors')
  ->set('verify_mail', FALSE)
  ->save();
echo "   Registro abierto a visitantes (sin verificación de e-mail).\n";

// ── 12. Set site front page to /catalog ───────────────────────────────────
\Drupal::configFactory()->getEditable('system.site')
  ->set('name', 'TiendaVirtual')
  ->set('slogan', 'Tu tienda online de confianza')
  ->set('page.front', '/catalog')
  ->save();
echo "   Página de inicio configurada: /catalog\n";

// ── 13. Configure Search API server + index via config API ──────────────────
echo "\n🔍 Configurando servidor e índice de búsqueda...\n";

// Server
$server_config_id = 'search_api.server.productos_db';
if (!\Drupal::config($server_config_id)->get('id')) {
  \Drupal::configFactory()->getEditable($server_config_id)
    ->setData([
      'langcode'   => 'es',
      'status'     => TRUE,
      'id'         => 'productos_db',
      'name'       => 'Productos (base de datos)',
      'description'=> '',
      'backend'    => 'search_api_db',
      'backend_config' => [
        'min_chars'           => 1,
        'partial_matches'     => FALSE,
        'database'            => 'default:default',
        'autocomplete'        => ['suggest_suffix' => TRUE, 'suggest_words' => TRUE],
      ],
    ])
    ->save();
  echo "   Servidor creado.\n";
} else {
  echo "   Servidor ya existe.\n";
}

// Index
$index_config_id = 'search_api.index.productos';
if (!\Drupal::config($index_config_id)->get('id')) {
  \Drupal::configFactory()->getEditable($index_config_id)
    ->setData([
      'langcode'   => 'es',
      'status'     => TRUE,
      'id'         => 'productos',
      'name'       => 'Índice de Productos',
      'description'=> '',
      'read_only'  => FALSE,
      'field_settings' => [
        'title'  => ['label' => 'Título', 'datasource_id' => 'entity:commerce_product', 'property_path' => 'title',  'type' => 'text',    'boost' => '5.0', 'dependencies' => []],
        'body'   => ['label' => 'Cuerpo',  'datasource_id' => 'entity:commerce_product', 'property_path' => 'body',   'type' => 'text',    'boost' => '1.0', 'dependencies' => []],
        'status' => ['label' => 'Estado',  'datasource_id' => 'entity:commerce_product', 'property_path' => 'status', 'type' => 'boolean', 'boost' => '1.0', 'dependencies' => []],
      ],
      'datasource_settings' => [
        'entity:commerce_product' => [
          'bundles'   => ['default' => TRUE, 'selected' => []],
          'languages' => ['default' => TRUE, 'selected' => []],
        ],
      ],
      'processor_settings' => [],
      'tracker_settings'   => ['default' => ['indexing_order' => 'fifo']],
      'options'            => ['index_directly' => TRUE, 'cron_limit' => 50],
      'server'             => 'productos_db',
      'dependencies'       => [],
    ])
    ->save();
  echo "   Índice creado.\n";
} else {
  echo "   Índice ya existe.\n";
}

echo "\n🎉 ¡Configuración de la tienda completada!\n";
