<?php
/**
 * Базове налаштування теми.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Підтримка можливостей WordPress і розміри зображень.
 *
 * @return void
 */
function scala_setup(): void {
	load_theme_textdomain( 'scala', SCALA_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 48,
		'width'       => 180,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form',
		'gallery',
		'caption',
		'style',
		'script',
		'navigation-widgets',
	) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'automatic-feed-links' );

	// Розміри під сітки макета. Кроп там, де співвідношення фіксоване.
	add_image_size( 'scala-sm', 640, 0 );
	add_image_size( 'scala-md', 1100, 0 );
	add_image_size( 'scala-lg', 1800, 0 );
	add_image_size( 'scala-square', 800, 800, true );
	add_image_size( 'scala-portrait', 900, 1200, true );

	// Лише одне місце: футер збирається шаблоном, окреме меню там зайве.
	register_nav_menus( array(
		'primary' => __( 'Головне меню', 'scala' ),
	) );
}
add_action( 'after_setup_theme', 'scala_setup' );

/**
 * Ширина контенту для вбудованих медіа.
 *
 * @return void
 */
function scala_content_width(): void {
	$GLOBALS['content_width'] = 1440;
}
add_action( 'after_setup_theme', 'scala_content_width', 0 );

/**
 * Прибирає з <head> те, що темі не потрібне.
 *
 * @return void
 */
function scala_clean_head(): void {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
}
add_action( 'init', 'scala_clean_head' );

/**
 * Вимикає емодзі-скрипти ядра — вони важать і темі не потрібні.
 *
 * @return void
 */
function scala_disable_emojis(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'scala_disable_emojis' );
