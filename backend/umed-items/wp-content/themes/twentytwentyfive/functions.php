<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

if ( ! function_exists( 'twentytwentyfive_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_post_format_setup' );

if ( ! function_exists( 'twentytwentyfive_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_editor_style() {
		add_editor_style( 'assets/css/editor-style.css' );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_editor_style' );

if ( ! function_exists( 'twentytwentyfive_enqueue_styles' ) ) :
	/**
	 * Enqueues the theme stylesheet on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_enqueue_styles() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$src    = 'style' . $suffix . '.css';

		wp_enqueue_style(
			'twentytwentyfive-style',
			get_parent_theme_file_uri( $src ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
		wp_style_add_data(
			'twentytwentyfive-style',
			'path',
			get_parent_theme_file_path( $src )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles' );

if ( ! function_exists( 'twentytwentyfive_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'twentytwentyfive' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_block_styles' );

if ( ! function_exists( 'twentytwentyfive_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_pattern_categories() {

		register_block_pattern_category(
			'twentytwentyfive_page',
			array(
				'label'       => __( 'Pages', 'twentytwentyfive' ),
				'description' => __( 'A collection of full page layouts.', 'twentytwentyfive' ),
			)
		);

		register_block_pattern_category(
			'twentytwentyfive_post-format',
			array(
				'label'       => __( 'Post formats', 'twentytwentyfive' ),
				'description' => __( 'A collection of post format patterns.', 'twentytwentyfive' ),
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_pattern_categories' );

if ( ! function_exists( 'twentytwentyfive_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_register_block_bindings() {
		register_block_bindings_source(
			'twentytwentyfive/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive' ),
				'get_value_callback' => 'twentytwentyfive_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_register_block_bindings' );

if ( ! function_exists( 'twentytwentyfive_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function twentytwentyfive_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;

// Регистрируем собственный публичный эндпоинт для React
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/products', [
        'methods'             => 'GET',
        'callback'            => 'get_custom_products_for_react',
        'permission_callback' => '__return_true', // Разрешает доступ всем без ключей ck/cs
    ]);
});

// Функция, которая собирает товары в нужном формате
function get_custom_products_for_react() {
    if (!function_exists('wc_get_products')) {
        return new WP_Error('wc_missing', 'WooCommerce не активен', ['status' => 500]);
    }

    $products = wc_get_products(['limit' => -1]);
    $response = [];

    foreach ($products as $product) {
        $images = [];
        $product_id = $product->get_id();

        // 1. Пытаемся получить главное фото через WooCommerce
        $main_image_id = $product->get_image_id();
        
        // 2. Если WC не отдал ID, пробуем стандартную миниатюру WordPress
        if (!$main_image_id) {
            $main_image_id = get_post_thumbnail_id($product_id);
        }

        // 3. Собираем галерею WooCommerce
        $gallery_ids = $product->get_gallery_image_ids();

        // Если галерея пустая, проверим старый мета-кусок (на всякий случай)
        if (empty($gallery_ids)) {
            $meta_gallery = get_post_meta($product_id, '_product_image_gallery', true);
            if (!empty($meta_gallery)) {
                $gallery_ids = explode(',', $meta_gallery);
            }
        }

        // Объединяем все ID в один массив
        $attachment_ids = array_merge([$main_image_id], (array)$gallery_ids);
        
        // Очищаем от пустых значений и дубликатов
        $attachment_ids = array_unique(array_filter($attachment_ids));

        foreach ($attachment_ids as $id) {
            $img_url = wp_get_attachment_url($id);
            if ($img_url) {
                $images[] = $img_url;
            }
        }

        // РЕЗЕРВНЫЙ ВАРИАНТ: Если картинок всё ещё НЕТ, поищем любую картинку, загруженную в этот пост
        if (empty($images)) {
            $attached_media = get_attached_media('image', $product_id);
            foreach ($attached_media as $media) {
                $images[] = wp_get_attachment_url($media->ID);
            }
        }

        $response[] = [
            'id'       => $product_id,
            'name'     => $product->get_name(),
            'fullName' => $product->get_name(),
            'price'    => (float)$product->get_price(),
            'images'   => array_values($images) // Сбрасываем ключи массива для JSON
        ];
    }

    return new WP_REST_Response($response, 200);
}

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/checkout', [
        'methods'             => 'POST',
        'callback'            => 'create_custom_woocommerce_order',
        'permission_callback' => '__return_true',
    ]);
});

function create_custom_woocommerce_order(WP_REST_Request $request) {
    if (!class_exists('WooCommerce')) {
        return new WP_Error('wc_missing', 'WooCommerce не активен', ['status' => 500]);
    }

    $params    = $request->get_json_params();
    $full_name = isset($params['fullName']) ? sanitize_text_field($params['fullName']) : '';
    $birth_date = isset($params['birthDate']) ? sanitize_text_field($params['birthDate']) : '';
    $cart_items = isset($params['cart']) ? $params['cart'] : [];

    if (empty($cart_items)) {
        return new WP_Error('empty_cart', 'Корзина пуста', ['status' => 400]);
    }

    // Разбиваем ФИО на Фамилию и Имя для стандартных полей WooCommerce
    $name_parts = explode(' ', $full_name);
    $first_name = isset($name_parts[1]) ? $name_parts[1] : $name_parts[0];
    $last_name  = isset($name_parts[0]) && isset($name_parts[1]) ? $name_parts[0] : '';

    // 1. Создаем заказ WooCommerce
    $order = wc_create_order();

    // 2. Наполняем товарами
    foreach ($cart_items as $item) {
        $product_id = intval($item['product']['id']);
        $quantity   = intval($item['quantity']);
        if ($product_id && $quantity) {
            $order->add_product(get_product($product_id), $quantity);
        }
    }

    // 3. Заполняем стандартные поля (Flamix их считывает автоматически)
    $order->set_billing_first_name($first_name);
    $order->set_billing_last_name($last_name);

    // 4. Записываем дату рождения в мета-данные заказа
    // Ключ '_billing_birth_date' регистрируется как кастомное поле адреса плательщика
    $order->update_meta_data('_billing_birth_date', $birth_date);
    $order->update_meta_data('Дата рождения сотрудника', $birth_date); // Дубликат в понятном виде

    // Пересчет и сохранение со статусом "В обработке" (Запускает триггер Flamix)
    $order->calculate_totals();
    $order->update_status('processing', 'Заявка сотрудника создана через React-интерфейс.');

    return new WP_REST_Response([
        'success'  => true,
        'order_id' => $order->get_id()
    ], 200);
}
