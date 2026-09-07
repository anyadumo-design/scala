<?php
/**
 * Хелпери для шаблонів: доступ до налаштувань і виведення зображень.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Усі налаштування теми одним масивом (кешується в межах запиту).
 *
 * @return array
 */
function scala_options(): array {
	static $cache = null;

	if ( null === $cache ) {
		$saved = get_option( SCALA_OPT_KEY, array() );
		$cache = is_array( $saved ) ? $saved : array();
	}

	return $cache;
}

/**
 * Значення налаштування з підтримкою вкладеності через крапку: 'hero.title'.
 *
 * @param string $path    Шлях до значення.
 * @param mixed  $default Значення за замовчуванням, якщо порожньо.
 * @return mixed
 */
function scala_opt( string $path, $default = '' ) {
	$value = scala_options();

	foreach ( explode( '.', $path ) as $key ) {
		if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
			return $default;
		}
		$value = $value[ $key ];
	}

	if ( '' === $value || null === $value || array() === $value ) {
		return $default;
	}

	return $value;
}

/**
 * Виводить налаштування як екранований текст.
 *
 * @param string $path    Шлях до значення.
 * @param string $default Значення за замовчуванням.
 * @return void
 */
function scala_the( string $path, string $default = '' ): void {
	echo esc_html( (string) scala_opt( $path, $default ) );
}

/**
 * Повторюване поле як масив рядків (завжди масив, навіть якщо порожньо).
 *
 * @param string $path Шлях до репітера.
 * @return array
 */
function scala_rows( string $path ): array {
	$rows = scala_opt( $path, array() );

	return is_array( $rows ) ? array_values( $rows ) : array();
}

/**
 * Дозволені теги в коротких текстах: розрив рядка й акцентний span.
 *
 * @return array
 */
function scala_inline_tags(): array {
	return array(
		'br'     => array(),
		'span'   => array( 'class' => array() ),
		'strong' => array(),
		'em'     => array(),
		'a'      => array(
			'href'   => array(),
			'rel'    => array(),
			'target' => array(),
		),
	);
}

/**
 * Текст із дозволеними інлайновими тегами.
 *
 * ВАЖЛИВО: перед <br> має бути пробіл, інакше textContent злипається
 * («якізмінюють») — саме так це прочитають робот і скрінрідер.
 *
 * @param string $path    Шлях до значення.
 * @param string $default Значення за замовчуванням.
 * @return void
 */
function scala_the_rich( string $path, string $default = '' ): void {
	$html = (string) scala_opt( $path, $default );
	$html = preg_replace( '~(?<!\s)(<br\s*/?>)~i', ' $1', $html );

	echo wp_kses( $html, scala_inline_tags() );
}

/**
 * Розміри зображення, зареєстровані темою, для srcset.
 *
 * @return array
 */
function scala_image_sizes(): array {
	return array( 'scala-sm', 'scala-md', 'scala-lg' );
}

/**
 * Виводить <img> з вкладення: srcset, розміри, lazy і alt.
 *
 * Якщо вкладення немає — малює плейсхолдер із підказкою, ЩО саме
 * туди завантажити. Плейсхолдер не містить тексту в DOM для робота:
 * підпис віддається через aria-label, а не текстовим вузлом.
 *
 * @param int|string $attachment_id ID вкладення.
 * @param array      $args          Параметри виводу.
 * @return void
 */
function scala_image( $attachment_id, array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'size'        => 'scala-lg',
			'sizes'       => '100vw',
			'class'       => '',
			'alt'         => '',
			'placeholder' => '',
			'loading'     => 'lazy',
			'fetchpriority' => '',
		)
	);

	$attachment_id = (int) $attachment_id;

	if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
		$attr = array(
			'sizes'   => $args['sizes'],
			'loading' => $args['loading'],
		);

		if ( $args['class'] ) {
			$attr['class'] = $args['class'];
		}

		if ( $args['alt'] ) {
			$attr['alt'] = $args['alt'];
		}

		if ( 'eager' === $args['loading'] ) {
			// Головне зображення екрана не має чекати — знімаємо lazy.
			unset( $attr['loading'] );
		}

		if ( $args['fetchpriority'] ) {
			$attr['fetchpriority'] = $args['fetchpriority'];
		}

		echo wp_get_attachment_image( $attachment_id, $args['size'], false, $attr );

		return;
	}

	// Порожній слот. Текст підказки бачить лише редактор у режимі
	// перегляду сайту, у DOM він не потрапляє як контент сторінки.
	if ( $args['placeholder'] && current_user_can( 'edit_theme_options' ) ) {
		printf(
			'<div class="slot-empty" aria-hidden="true">%s</div>',
			esc_html( $args['placeholder'] )
		);

		return;
	}

	echo '<div class="slot-empty" aria-hidden="true"></div>';
}

/**
 * URL зображення потрібного розміру (для background-image).
 *
 * @param int|string $attachment_id ID вкладення.
 * @param string     $size          Розмір.
 * @return string
 */
function scala_image_url( $attachment_id, string $size = 'scala-lg' ): string {
	$attachment_id = (int) $attachment_id;

	if ( ! $attachment_id ) {
		return '';
	}

	$src = wp_get_attachment_image_src( $attachment_id, $size );

	return $src ? $src[0] : '';
}

/**
 * Телефон у форматі для href="tel:" — лише цифри й початковий плюс.
 *
 * @param string $phone Телефон як його ввели в адмінці.
 * @return string
 */
function scala_tel( string $phone ): string {
	$has_plus = str_starts_with( trim( $phone ), '+' );
	$digits   = preg_replace( '~\D~', '', $phone );

	if ( ! $digits ) {
		return '';
	}

	if ( $has_plus ) {
		return '+' . $digits;
	}

	// Розрізняємо формати за довжиною, а не за наявністю нуля:
	// «38 (097) 123-33-30» раніше перетворювалось на +380380971233330,
	// і дзвінок не проходив узагалі.
	$len = strlen( $digits );

	if ( 12 === $len && str_starts_with( $digits, '380' ) ) {
		return '+' . $digits;              // 380971233330
	}

	if ( 10 === $len && str_starts_with( $digits, '0' ) ) {
		return '+38' . $digits;            // 0971233330
	}

	if ( 9 === $len ) {
		return '+380' . $digits;           // 971233330
	}

	return '+' . $digits;                  // інші країни — лишаємо як є
}

/**
 * Записи потрібного типу в порядку menu_order.
 *
 * @param string $post_type Тип запису.
 * @param int    $limit     Скільки повернути.
 * @return WP_Post[]
 */
function scala_posts( string $post_type, int $limit = -1 ): array {
	$posts = get_posts(
		array(
			'post_type'        => $post_type,
			'posts_per_page'   => $limit,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'suppress_filters' => false,
		)
	);

	return is_array( $posts ) ? $posts : array();
}

/**
 * Мета-поле запису.
 *
 * @param int    $post_id ID запису.
 * @param string $key     Ключ без префікса.
 * @param mixed  $default Значення за замовчуванням.
 * @return mixed
 */
function scala_meta( int $post_id, string $key, $default = '' ) {
	$value = get_post_meta( $post_id, '_scala_' . $key, true );

	return ( '' === $value || null === $value ) ? $default : $value;
}

/**
 * Адреса сторінки за призначеним їй шаблоном.
 *
 * Дозволяє посилатися на «Каталог» чи «Контакти», не прив’язуючись до
 * слага: клієнт може перейменувати сторінку, посилання не зламається.
 *
 * @param string $key catalog | about | contacts.
 * @return string
 */
function scala_page_url( string $key ): string {
	static $cache = array();

	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'template-' . $key . '.php', // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'         => 'ids',
		)
	);

	$cache[ $key ] = $pages ? (string) get_permalink( (int) $pages[0] ) : '';

	return $cache[ $key ];
}

/**
 * Пункти навігації за замовчуванням, якщо меню ще не призначили.
 *
 * @return array
 */
function scala_default_nav(): array {
	$pages = array(
		array( 'url' => scala_page_url( 'catalog' ), 'title' => __( 'Каталог', 'scala' ) ),
		array( 'url' => scala_page_url( 'about' ), 'title' => __( 'Про бренд', 'scala' ) ),
		array( 'url' => scala_page_url( 'contacts' ), 'title' => __( 'Контакти', 'scala' ) ),
	);

	if ( is_front_page() ) {
		$anchors = array(
			array( 'url' => '#solutions', 'title' => __( 'Рішення', 'scala' ) ),
			array( 'url' => '#projects', 'title' => __( 'Проєкти', 'scala' ) ),
			array( 'url' => '#process', 'title' => __( 'Як працюємо', 'scala' ) ),
			array( 'url' => '#fabrics', 'title' => __( 'Тканини', 'scala' ) ),
		);
	} else {
		$anchors = array(
			array( 'url' => home_url( '/' ), 'title' => __( 'Головна', 'scala' ) ),
		);
	}

	// Сторінки, яких ще немає, у меню не потрапляють.
	$pages = array_values( array_filter( $pages, static fn( $item ) => '' !== $item['url'] ) );

	return array_merge( $anchors, $pages );
}

/**
 * Виводить навігацію: призначене меню або набір за замовчуванням.
 *
 * @param string $location Місце меню.
 * @return void
 */
function scala_nav( string $location ): void {
	$items     = array();
	$locations = get_nav_menu_locations();

	if ( ! empty( $locations[ $location ] ) ) {
		$menu_items = wp_get_nav_menu_items( (int) $locations[ $location ] );

		if ( $menu_items ) {
			foreach ( $menu_items as $item ) {
				// Показуємо лише верхній рівень: у макеті меню однорівневе.
				if ( (int) $item->menu_item_parent ) {
					continue;
				}

				$items[] = array(
					'url'   => $item->url,
					'title' => $item->title,
				);
			}
		}
	}

	if ( ! $items ) {
		$items = scala_default_nav();
	}

	$current = is_singular() ? (string) get_permalink() : '';

	foreach ( $items as $item ) {
		$is_current = $current && untrailingslashit( $item['url'] ) === untrailingslashit( $current );

		printf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $item['url'] ),
			$is_current ? ' class="is-current"' : '',
			$is_current ? ' aria-current="page"' : '',
			esc_html( $item['title'] )
		);
	}
}
