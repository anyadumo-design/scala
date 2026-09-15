<?php
/**
 * Прибирає з сайту чужі стилі й скрипти, які на сторінці не працюють.
 *
 * WordPress і плагіни підключають своє на кожній сторінці, не питаючи,
 * чи є там що оформлювати. У нашому випадку це стилі блокового
 * редактора на сайті, який блоків не використовує, і стилі та чотири
 * скрипти Contact Form 7 на сторінках, де жодної його форми немає:
 * форма заявки в темі власна.
 *
 * Перевірка йде по вмісту конкретної сторінки, а не «вимкнути всюди».
 * Зʼявиться блок чи форма плагіна — стилі для них повернуться самі.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Вміст сторінки, яку зараз показуємо.
 *
 * @return string
 */
function scala_current_content(): string {
	if ( ! is_singular() ) {
		return '';
	}

	$post = get_post();

	return $post instanceof WP_Post ? (string) $post->post_content : '';
}

/**
 * Знімає зайве з черги завантаження.
 *
 * @return void
 */
function scala_dequeue_unused_assets(): void {
	if ( is_admin() ) {
		return;
	}

	$content = scala_current_content();

	// Стилі блокового редактора — лише там, де блоки справді є.
	if ( ! str_contains( $content, '<!-- wp:' ) && ! str_contains( $content, 'wp-block-' ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'global-styles' );
	}

	// Contact Form 7 — тільки там, де стоїть його форма.
	if ( ! str_contains( $content, '[contact-form-7' ) ) {
		wp_dequeue_style( 'contact-form-7' );
		wp_dequeue_script( 'contact-form-7' );
		wp_dequeue_script( 'swv' );
	}
}
add_action( 'wp_enqueue_scripts', 'scala_dequeue_unused_assets', 100 );
