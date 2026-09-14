<?php
/**
 * Тимчасова діагностика.
 *
 * Друкує стан теми у HTML-коментар — але тільки на адресу з ?scala-diag=1,
 * тож звичайні відвідувачі цього не бачать. Потрібна, щоб зрозуміти,
 * чому пункти меню не реєструються: з адмінки я цього не бачу, а так
 * можу прочитати ззовні.
 *
 * ПРИБРАТИ, щойно причина знайдеться.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Виводить стан теми.
 *
 * @return void
 */
function scala_diagnose(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- лише читання, без дій.
	if ( ! isset( $_GET['scala-diag'] ) ) {
		return;
	}

	$funcs = array(
		'scala_admin_menu',
		'scala_migrate_menu',
		'scala_render_options_page',
		'scala_render_migrate_page',
		'scala_setup_step',
		'scala_options_schema',
		'scala_redirect_map',
		'scala_seed_content',
		'str_contains',
	);

	$out = array( 'version=' . SCALA_VERSION, 'php=' . PHP_VERSION );

	foreach ( $funcs as $f ) {
		$out[] = $f . '=' . ( function_exists( $f ) ? 'є' : 'НЕМАЄ' );
	}

	$out[] = 'hook_admin_menu_9=' . ( has_action( 'admin_menu', 'scala_admin_menu' ) ?: 'НЕМАЄ' );
	$out[] = 'hook_admin_menu_11=' . ( has_action( 'admin_menu', 'scala_migrate_menu' ) ?: 'НЕМАЄ' );
	$out[] = 'seeded=' . ( get_option( 'scala_seeded' ) ?: 'ні' );
	$out[] = 'setup_needed=' . ( get_option( 'scala_setup_needed' ) ?: 'ні' );
	$out[] = 'setup_error=' . ( get_option( 'scala_setup_error' ) ?: 'немає' );
	$out[] = 'types=' . wp_count_posts( 'scala_type' )->publish;
	$out[] = 'attachments=' . wp_count_posts( 'attachment' )->inherit;

	$index = scala_bundled_index( true );
	$names = scala_bundled_names();
	$found = array();
	$miss  = array();
	$dupes = 0;

	foreach ( $names as $n ) {
		if ( empty( $index[ $n ] ) ) {
			$miss[] = $n;
		} else {
			$found[] = $n . ':' . count( $index[ $n ] );
			$dupes  += count( $index[ $n ] ) - 1;
		}
	}

	$out[] = 'images_total=' . count( $names );
	$out[] = 'images_found=' . count( $found );
	$out[] = 'images_missing=' . ( $miss ? implode( ',', $miss ) : 'немає' );
	$out[] = 'images_extra_copies=' . $dupes;
	$out[] = 'images_index=' . implode( ' ', $found );

	// Сам механізм поломки: чи перехоплені загальні права як мета-права.
	global $post_type_meta_caps;
	foreach ( array( 'edit_theme_options', 'manage_options' ) as $cap ) {
		$out[] = 'hijacked_' . $cap . '=' . ( isset( $post_type_meta_caps[ $cap ] ) ? 'ТАК' : 'ні' );
	}

	$menu_dbg = get_option( 'scala_menu_debug' );

	if ( is_array( $menu_dbg ) ) {
		foreach ( $menu_dbg as $k => $v ) {
			$out[] = 'menu_' . $k . '=' . $v;
		}
	} else {
		$out[] = 'menu=ще не заходили в адмінку';
	}

	printf( "\n<!-- SCALA-DIAG\n%s\n-->\n", esc_html( implode( "\n", $out ) ) );
}
add_action( 'wp_head', 'scala_diagnose', 1 );
