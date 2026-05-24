<?php
/**
 * update_products_books.php
 * Ejecutar: ddev drush php:script /var/www/html/scripts/update_products_books.php
 *
 * Elimina todos los productos existentes y los reemplaza por 20 libros
 * con imágenes reales de Open Library, categorías por género literario.
 */

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_price\Price;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

// ── 1. Obtener la tienda ───────────────────────────────────────────────────
$stores = \Drupal::entityTypeManager()->getStorage('commerce_store')->loadMultiple();
if (empty($stores)) {
  echo "❌ No hay ninguna tienda. Ejecuta primero setup_store.php\n";
  exit(1);
}
$store = reset($stores);
echo "🏪 Usando tienda: {$store->label()} (ID: {$store->id()})\n";

// ── 2. Eliminar todos los productos existentes ─────────────────────────────
echo "\n🗑️  Eliminando productos existentes...\n";
$product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
$variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');

$all_products = $product_storage->loadMultiple();
$count = count($all_products);
foreach ($all_products as $product) {
  // Eliminar variaciones
  foreach ($product->getVariations() as $variation) {
    $variation->delete();
  }
  $product->delete();
}
echo "   {$count} productos eliminados.\n";

// ── 3. Eliminar categorías antiguas y crear géneros literarios ─────────────
echo "\n🏷️  Actualizando categorías a géneros literarios...\n";
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

// Borrar términos viejos que no son géneros de libros
$old_cats = ['Electrónica', 'Ropa y Calzado', 'Deportes'];
foreach ($old_cats as $cat_name) {
  $terms = $term_storage->loadByProperties(['name' => $cat_name, 'vid' => 'product_category']);
  foreach ($terms as $term) {
    echo "   Eliminando categoría '{$cat_name}'...\n";
    $term->delete();
  }
}

// Géneros literarios
$genre_names = [
  'Ficción Literaria',
  'Ciencia Ficción',
  'Fantasía',
  'Clásicos de la Literatura',
  'Misterio y Thriller',
  'No Ficción',
  'Libros', // Conservar por si existía antes
];

$cats = [];
foreach ($genre_names as $name) {
  if ($name === 'Libros') {
    // Si "Libros" existe, lo renombramos a algo útil o simplemente lo ignoramos
    $existing = $term_storage->loadByProperties(['name' => 'Libros', 'vid' => 'product_category']);
    if ($existing) {
      foreach ($existing as $t) { $t->delete(); }
    }
    continue;
  }
  $existing = $term_storage->loadByProperties(['name' => $name, 'vid' => 'product_category']);
  if ($existing) {
    $cats[$name] = reset($existing);
    echo "   Género '{$name}' ya existe.\n";
  } else {
    $term = Term::create(['vid' => 'product_category', 'name' => $name]);
    $term->save();
    $cats[$name] = $term;
    echo "   Género '{$name}' creado.\n";
  }
}

// ── 4. Helper: descargar imagen ────────────────────────────────────────────
/** @var \Drupal\Core\File\FileSystemInterface $fs */
$fs = \Drupal::service('file_system');
$products_dir = 'public://products';
$fs->prepareDirectory($products_dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

function download_book_image(string $url, string $filename, string $fallback_url = ''): ?File {
  $destination = 'public://products/' . $filename;

  // Reemplazar si ya existe para forzar imagen nueva
  $existing = \Drupal::entityTypeManager()
    ->getStorage('file')
    ->loadByProperties(['uri' => $destination]);
  if ($existing) {
    foreach ($existing as $f) { $f->delete(); }
    @unlink(\Drupal::service('file_system')->realpath($destination));
  }

  foreach (array_filter([$url, $fallback_url]) as $try_url) {
    $ch = curl_init($try_url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_TIMEOUT        => 20,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_USERAGENT      => 'Mozilla/5.0 (TiendaVirtual/1.0)',
    ]);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($data && $http_code === 200 && strlen($data) > 2000) {
      $uri = \Drupal::service('file_system')->saveData($data, $destination, FileSystemInterface::EXISTS_REPLACE);
      if ($uri) {
        $file = File::create(['uri' => $uri, 'status' => 1, 'uid' => 1]);
        $file->save();
        return $file;
      }
    }
  }

  echo "      ⚠️  No se pudo descargar imagen: {$url}\n";
  return NULL;
}

// ── 5. Definición de los 20 libros ────────────────────────────────────────
// Imágenes: Open Library (ISBN) + fallback a picsum
$books = [

  // ── Ficción Literaria ────────────────────────────────────────────────────
  [
    'title'    => 'Cien Años de Soledad',
    'author'   => 'Gabriel García Márquez',
    'desc'     => '<p>La obra cumbre del <strong>realismo mágico latinoamericano</strong>. La saga de siete generaciones de la familia Buendía en la mítica Macondo. Premio Nobel de Literatura 1982. Considerada una de las mejores novelas del siglo XX. Edición conmemorativa del 50 aniversario. Tapa dura, 432 páginas.</p>',
    'price'    => '18.99',
    'stock'    => 40,
    'sku'      => 'LIB-001',
    'category' => 'Ficción Literaria',
    'isbn'     => '9780060883287',
    'img_name' => 'cien-anos-soledad.jpg',
    'img_seed' => 'gabriel',
  ],
  [
    'title'    => 'La Casa de los Espíritus',
    'author'   => 'Isabel Allende',
    'desc'     => '<p>La saga épica de la familia Trueba a lo largo de varias generaciones en un país latinoamericano sin nombre. Una novela de <strong>amor, magia y política</strong> que mezcla lo real y lo fantástico. Considerada la obra más importante de Isabel Allende. Tapa blanda, 433 páginas.</p>',
    'price'    => '15.99',
    'stock'    => 35,
    'sku'      => 'LIB-002',
    'category' => 'Ficción Literaria',
    'isbn'     => '9781501117015',
    'img_name' => 'casa-espiritus.jpg',
    'img_seed' => 'isabelallende',
  ],
  [
    'title'    => 'El Alquimista',
    'author'   => 'Paulo Coelho',
    'desc'     => '<p>La novela más vendida en la historia de la literatura brasileña, traducida a más de 80 idiomas. La historia de Santiago, un pastor andaluz que sueña con un tesoro escondido junto a las pirámides de Egipto. Una fábula sobre <strong>seguir los sueños</strong> y escuchar el corazón. Tapa blanda, 177 páginas.</p>',
    'price'    => '12.99',
    'stock'    => 55,
    'sku'      => 'LIB-003',
    'category' => 'Ficción Literaria',
    'isbn'     => '9780062315007',
    'img_name' => 'el-alquimista.jpg',
    'img_seed' => 'paulocoelho',
  ],
  [
    'title'    => 'Ensayo sobre la Ceguera',
    'author'   => 'José Saramago',
    'desc'     => '<p>Una ciudad es azotada por una misteriosa epidemia de ceguera blanca que se extiende rápidamente. Los infectados son internados en un manicomio vigilado militarmente. Una alegoría sobre la <strong>fragilidad de la civilización</strong> y la condición humana. Premio Nobel de Literatura. Tapa blanda, 352 páginas.</p>',
    'price'    => '14.99',
    'stock'    => 30,
    'sku'      => 'LIB-004',
    'category' => 'Ficción Literaria',
    'isbn'     => '9780156007757',
    'img_name' => 'ensayo-ceguera.jpg',
    'img_seed' => 'saramago',
  ],

  // ── Clásicos de la Literatura ────────────────────────────────────────────
  [
    'title'    => 'Don Quijote de la Mancha',
    'author'   => 'Miguel de Cervantes',
    'desc'     => '<p>La primera novela moderna y una de las mayores obras de la <strong>literatura universal</strong>. El hidalgo Alonso Quijano enloquece leyendo libros de caballerías y decide convertirse en caballero andante acompañado de su fiel escudero Sancho Panza. Edición de la Real Academia Española, 1376 páginas.</p>',
    'price'    => '22.99',
    'stock'    => 25,
    'sku'      => 'LIB-005',
    'category' => 'Clásicos de la Literatura',
    'isbn'     => '9788467020168',
    'img_name' => 'don-quijote.jpg',
    'img_seed' => 'cervantes',
  ],
  [
    'title'    => 'Orgullo y Prejuicio',
    'author'   => 'Jane Austen',
    'desc'     => '<p>La novela más célebre de Jane Austen. Elizabeth Bennet y el altivo Mr. Darcy protagonizan una de las historias de amor más famosas de la literatura. Una brillante sátira de las <strong>costumbres sociales</strong> de la Inglaterra del siglo XIX. Edición Penguin Clásicos con prólogo de Tony Tanner. Tapa blanda, 432 páginas.</p>',
    'price'    => '11.99',
    'stock'    => 45,
    'sku'      => 'LIB-006',
    'category' => 'Clásicos de la Literatura',
    'isbn'     => '9780141439518',
    'img_name' => 'orgullo-prejuicio.jpg',
    'img_seed' => 'janeausten',
  ],
  [
    'title'    => 'Crimen y Castigo',
    'author'   => 'Fiódor Dostoievski',
    'desc'     => '<p>El estudiante Raskolnikov comete un asesinato y se ve atrapado en una espiral de culpa y paranoia. Una de las novelas psicológicas más profundas de la <strong>literatura rusa</strong>. Explora la naturaleza del crimen, la redención y la moral humana. Traducción de Rafael Cansinos Assens. Tapa blanda, 576 páginas.</p>',
    'price'    => '13.99',
    'stock'    => 30,
    'sku'      => 'LIB-007',
    'category' => 'Clásicos de la Literatura',
    'isbn'     => '9780486415871',
    'img_name' => 'crimen-castigo.jpg',
    'img_seed' => 'dostoievski',
  ],
  [
    'title'    => 'El Gran Gatsby',
    'author'   => 'F. Scott Fitzgerald',
    'desc'     => '<p>La historia del misterioso millonario Jay Gatsby y su obsesión por la bella Daisy Buchanan en el Nueva York de los años 20. Un retrato del <strong>Sueño Americano</strong>, la decadencia y el exceso de la era del jazz. Considerada la gran novela americana. Edición Austral, tapa blanda, 208 páginas.</p>',
    'price'    => '10.99',
    'stock'    => 40,
    'sku'      => 'LIB-008',
    'category' => 'Clásicos de la Literatura',
    'isbn'     => '9780743273565',
    'img_name' => 'gran-gatsby.jpg',
    'img_seed' => 'fitzgerald',
  ],
  [
    'title'    => 'El Principito',
    'author'   => 'Antoine de Saint-Exupéry',
    'desc'     => '<p>El libro más leído y traducido de la literatura francesa. Un pequeño príncipe que viaja por el universo aprendiendo sobre la vida, el amor y la amistad. Contiene el célebre fragmento: <em>"Lo esencial es invisible a los ojos."</em> Edición especial con los dibujos originales del autor a color. Tapa blanda, 96 páginas.</p>',
    'price'    => '9.99',
    'stock'    => 60,
    'sku'      => 'LIB-009',
    'category' => 'Clásicos de la Literatura',
    'isbn'     => '9788498381498',
    'img_name' => 'el-principito.jpg',
    'img_seed' => 'principito',
  ],

  // ── Fantasía ─────────────────────────────────────────────────────────────
  [
    'title'    => 'Harry Potter y la Piedra Filosofal',
    'author'   => 'J.K. Rowling',
    'desc'     => '<p>El primer libro de la saga más vendida de la historia. Harry Potter descubre en su undécimo cumpleaños que es un mago y fue aceptado en <strong>Hogwarts</strong>, la escuela de magia y hechicería. Edición de lujo con ilustraciones exclusivas de Jim Kay. Tapa dura, 352 páginas. Apto para mayores de 9 años.</p>',
    'price'    => '19.99',
    'stock'    => 50,
    'sku'      => 'LIB-010',
    'category' => 'Fantasía',
    'isbn'     => '9788478885763',
    'img_name' => 'harry-potter.jpg',
    'img_seed' => 'harrypotter',
  ],
  [
    'title'    => 'El Señor de los Anillos: La Comunidad del Anillo',
    'author'   => 'J.R.R. Tolkien',
    'desc'     => '<p>El hobbit Frodo Bolsón hereda el Anillo Único, un artefacto de terrible poder creado por el Señor Oscuro Sauron. Comienza así una <strong>épica aventura</strong> a través de la Tierra Media para destruir el Anillo. La obra definitiva de la fantasía épica moderna. Edición Minotauro, tapa blanda, 576 páginas.</p>',
    'price'    => '24.99',
    'stock'    => 35,
    'sku'      => 'LIB-011',
    'category' => 'Fantasía',
    'isbn'     => '9780618640157',
    'img_name' => 'senor-anillos.jpg',
    'img_seed' => 'tolkien',
  ],
  [
    'title'    => 'El Hobbit',
    'author'   => 'J.R.R. Tolkien',
    'desc'     => '<p>El hobbit Bilbo Bolsón es reclutado por el mago Gandalf para unirse a un grupo de enanos en una aventura para recuperar el tesoro custodiado por el dragón Smaug. La <strong>precuela de El Señor de los Anillos</strong>. Perfecta para lectores de todas las edades. Edición ilustrada, tapa dura, 310 páginas.</p>',
    'price'    => '17.99',
    'stock'    => 45,
    'sku'      => 'LIB-012',
    'category' => 'Fantasía',
    'isbn'     => '9780547928227',
    'img_name' => 'el-hobbit.jpg',
    'img_seed' => 'hobbit',
  ],

  // ── Ciencia Ficción ───────────────────────────────────────────────────────
  [
    'title'    => '1984',
    'author'   => 'George Orwell',
    'desc'     => '<p>En el año 1984, el mundo está dominado por tres superpotencias totalitarias. Winston Smith trabaja para el Ministerio de la Verdad reescribiendo la historia. Una novela distópica sobre el <strong>totalitarismo, la vigilancia y la libertad</strong> que se ha convertido en una de las obras más influyentes del siglo XX. Tapa blanda, 320 páginas.</p>',
    'price'    => '13.99',
    'stock'    => 50,
    'sku'      => 'LIB-013',
    'category' => 'Ciencia Ficción',
    'isbn'     => '9780451524935',
    'img_name' => '1984-orwell.jpg',
    'img_seed' => 'orwell1984',
  ],
  [
    'title'    => 'Fahrenheit 451',
    'author'   => 'Ray Bradbury',
    'desc'     => '<p>En un futuro distópico, los libros están prohibidos y los bomberos se dedican a quemarlos. Guy Montag, un bombero, comienza a cuestionarse el orden establecido tras conocer a una joven que le hace descubrir el <strong>poder de la lectura</strong>. Un clásico de la ciencia ficción. Edición Debolsillo, tapa blanda, 256 páginas.</p>',
    'price'    => '12.99',
    'stock'    => 40,
    'sku'      => 'LIB-014',
    'category' => 'Ciencia Ficción',
    'isbn'     => '9781451673319',
    'img_name' => 'fahrenheit-451.jpg',
    'img_seed' => 'bradbury',
  ],
  [
    'title'    => 'Dune',
    'author'   => 'Frank Herbert',
    'desc'     => '<p>En el planeta desértico Arrakis, el joven Paul Atreides se convierte en líder de los Fremen tras la caída de su familia. Arrakis es el único lugar donde se produce la <strong>Especia</strong>, el recurso más valioso del universo. La novela de ciencia ficción más vendida de todos los tiempos. Tapa dura, 896 páginas.</p>',
    'price'    => '21.99',
    'stock'    => 30,
    'sku'      => 'LIB-015',
    'category' => 'Ciencia Ficción',
    'isbn'     => '9780441013593',
    'img_name' => 'dune.jpg',
    'img_seed' => 'frankherbert',
  ],
  [
    'title'    => 'Neuromancer',
    'author'   => 'William Gibson',
    'desc'     => '<p>La novela que fundó el género <strong>cyberpunk</strong>. Case, un hacker marginado, es contratado para un robo en el ciberespacio. Premio Hugo, Nebula y Philip K. Dick. Una visión profética del mundo conectado, las megacorporaciones y la inteligencia artificial que influyó en Matrix y toda la ciencia ficción moderna. Tapa blanda, 288 páginas.</p>',
    'price'    => '14.99',
    'stock'    => 25,
    'sku'      => 'LIB-016',
    'category' => 'Ciencia Ficción',
    'isbn'     => '9780441569595',
    'img_name' => 'neuromancer.jpg',
    'img_seed' => 'gibson',
  ],

  // ── Misterio y Thriller ───────────────────────────────────────────────────
  [
    'title'    => 'El Código Da Vinci',
    'author'   => 'Dan Brown',
    'desc'     => '<p>El profesor Robert Langdon es llamado al Louvre para examinar un cadáver junto a un enigmático mensaje. Lo que sigue es una frenética persecución que lo enfrenta a una <strong>conspiración de siglos</strong> relacionada con el Santo Grial y el Opus Dei. Más de 80 millones de copias vendidas. Tapa blanda, 489 páginas.</p>',
    'price'    => '16.99',
    'stock'    => 45,
    'sku'      => 'LIB-017',
    'category' => 'Misterio y Thriller',
    'isbn'     => '9780307474278',
    'img_name' => 'codigo-davinci.jpg',
    'img_seed' => 'danbrown',
  ],
  [
    'title'    => 'El Nombre de la Rosa',
    'author'   => 'Umberto Eco',
    'desc'     => '<p>En una abadía benedictina del siglo XIV, el monje Guillermo de Baskerville investiga una serie de misteriosas muertes. Un fascinante laberinto de <strong>misterio medieval, semiótica y filosofía</strong>. Premio Strega y Premio Médicis Extranjero. Uno de los libros más vendidos del siglo XX. Tapa blanda, 544 páginas.</p>',
    'price'    => '17.99',
    'stock'    => 30,
    'sku'      => 'LIB-018',
    'category' => 'Misterio y Thriller',
    'isbn'     => '9780156399473',
    'img_name' => 'nombre-rosa.jpg',
    'img_seed' => 'umberteco',
  ],
  [
    'title'    => 'La Sombra del Viento',
    'author'   => 'Carlos Ruiz Zafón',
    'desc'     => '<p>En la Barcelona de la posguerra, el joven Daniel Sempere descubre en el Cementerio de los Libros Olvidados una novela de Julián Carax. Al intentar saber más del autor, descubre un oscuro misterio. Una novela sobre <strong>libros, amor y secretos</strong> ambientada en la Barcelona más atmosférica. Tapa blanda, 544 páginas.</p>',
    'price'    => '16.99',
    'stock'    => 40,
    'sku'      => 'LIB-019',
    'category' => 'Misterio y Thriller',
    'isbn'     => '9780143034902',
    'img_name' => 'sombra-viento.jpg',
    'img_seed' => 'zafon',
  ],

  // ── No Ficción ────────────────────────────────────────────────────────────
  [
    'title'    => 'Sapiens: De animales a dioses',
    'author'   => 'Yuval Noah Harari',
    'desc'     => '<p>Un recorrido por la historia de la humanidad desde los primeros humanos hasta la era digital. Harari explora cómo el <em>Homo sapiens</em> llegó a dominar el planeta gracias a su capacidad de creer en ficciones colectivas como el dinero, los dioses y las naciones. <strong>El libro de no ficción más vendido</strong> de la última década. Tapa blanda, 496 páginas.</p>',
    'price'    => '19.99',
    'stock'    => 50,
    'sku'      => 'LIB-020',
    'category' => 'No Ficción',
    'isbn'     => '9780062316097',
    'img_name' => 'sapiens.jpg',
    'img_seed' => 'harari',
  ],
];

// ── 6. Crear los 20 libros ─────────────────────────────────────────────────
echo "\n📚 Creando " . count($books) . " libros...\n";

foreach ($books as $i => $data) {
  $num = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
  echo "   [{$num}] {$data['title']} — {$data['author']}...\n";

  // Imagen: intentar Open Library, fallback a picsum
  $img_url      = "https://covers.openlibrary.org/b/isbn/{$data['isbn']}-L.jpg";
  $fallback_url = "https://picsum.photos/seed/{$data['img_seed']}/400/600";
  $file         = download_book_image($img_url, $data['img_name'], $fallback_url);

  // Crear variación
  $variation = ProductVariation::create([
    'type'        => 'default',
    'sku'         => $data['sku'],
    'price'       => new Price($data['price'], 'USD'),
    'field_stock' => (int) $data['stock'],
    'status'      => 1,
  ]);
  $variation->save();

  // Construir producto
  $full_title    = $data['title'] . ' — ' . $data['author'];
  $product_fields = [
    'type'           => 'default',
    'title'          => $full_title,
    'body'           => ['value' => $data['desc'], 'format' => 'basic_html'],
    'stores'         => [$store],
    'variations'     => [$variation],
    'field_category' => [['target_id' => $cats[$data['category']]->id()]],
    'status'         => 1,
  ];

  if ($file) {
    $product_fields['field_product_image'] = [
      ['target_id' => $file->id(), 'alt' => $full_title],
    ];
  }

  $product = Product::create($product_fields);
  $product->save();
  echo "        ✅ Creado (ID: {$product->id()})\n";
}

echo "\n✅ " . count($books) . " libros creados exitosamente.\n";
echo "🔗 Visita: https://tiendavirtual.ddev.site:8443/catalog\n";
