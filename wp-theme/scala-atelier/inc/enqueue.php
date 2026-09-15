<?php
/**
 * Підключення стилів і скриптів.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Фронтенд: шрифт, стилі теми, інтерактив.
 *
 * @return void
 */
function scala_enqueue_front(): void {
	// Instrument Sans. preconnect до gstatic додається у scala_resource_hints().
	wp_enqueue_style(
		'scala-font',
		'https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'scala-main',
		SCALA_URI . '/assets/css/main.css',
		array( 'scala-font' ),
		SCALA_VERSION
	);

	wp_enqueue_script(
		'scala-main',
		SCALA_URI . '/assets/js/main.js',
		array(),
		SCALA_VERSION,
		true
	);

	wp_localize_script(
		'scala-main',
		'SCALA',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'scala_lead' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'scala_enqueue_front' );

/**
 * preconnect до Google Fonts — інакше шрифт чекає на зайвий раунд DNS+TLS.
 *
 * @param array  $urls          Уже наявні URL.
 * @param string $relation_type Тип звʼязку.
 * @return array
 */
function scala_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'scala_resource_hints', 10, 2 );

/**
 * Адмінка: стилі й скрипт полів (медіа-пікер, репітери).
 *
 * @param string $hook Поточна сторінка адмінки.
 * @return void
 */
function scala_enqueue_admin( string $hook ): void {
	$screen = get_current_screen();

	$is_options = str_contains( $hook, 'scala-settings' );
	$is_scala_post = $screen && str_starts_with( (string) $screen->post_type, 'scala_' );

	if ( ! $is_options && ! $is_scala_post ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_style(
		'scala-admin',
		SCALA_URI . '/assets/admin/admin.css',
		array(),
		SCALA_VERSION
	);

	wp_enqueue_script(
		'scala-admin',
		SCALA_URI . '/assets/admin/admin.js',
		array( 'jquery' ),
		SCALA_VERSION,
		true
	);

	wp_localize_script(
		'scala-admin',
		'SCALA_ADMIN',
		array(
			'chooseImage' => __( 'Обрати зображення', 'scala' ),
			'useImage'    => __( 'Використати це зображення', 'scala' ),
			'chooseVideo' => __( 'Обрати відео', 'scala' ),
			'useVideo'    => __( 'Використати це відео', 'scala' ),
			'confirmRow'  => __( 'Видалити цей блок?', 'scala' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'scala_enqueue_admin' );

/**
 * Шрифти не мають затримувати перший показ.
 *
 * Стиль із fonts.googleapis.com — це чужий домен: окреме зʼєднання,
 * окремий DNS, і показ сторінки чекає на них. Вантажимо його як
 * друкований (браузер не блокує показ), а після завантаження
 * перемикаємо на звичайний. Без JS лишається звичайне посилання.
 *
 * @param string $tag    Готовий тег.
 * @param string $handle Дескриптор стилю.
 * @return string
 */
function scala_async_font_style( string $tag, string $handle ): string {
	if ( 'scala-font' !== $handle || is_admin() ) {
		return $tag;
	}

	$async = str_replace(
		"media='all'",
		"media='print' onload=\"this.media='all'\"",
		$tag
	);

	return $async . '<noscript>' . $tag . '</noscript>';
}
add_filter( 'style_loader_tag', 'scala_async_font_style', 10, 2 );
