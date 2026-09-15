<?php
/**
 * Разові дії після оновлення теми.
 *
 * Те, що раніше доводилось клацати руками — додати матеріал, оновити
 * його текст, створити сторінку журналу, — робиться тут само, коли
 * власниця наступного разу відкриє адмінку. Кнопки лишаються: вони
 * потрібні, щоб повторити дію свідомо, а не щоб виконати її вперше.
 *
 * Кожна дія ідемпотентна й перевіряє стан перед тим, як щось міняти:
 * повторний запуск нічого не ламає, а ручні правки не перезаписуються.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/** Версія теми, дії якої вже виконані. */
define( 'SCALA_APPLIED', 'scala_applied_version' );

/**
 * Запускає дії, якщо тема оновилася.
 *
 * Тільки в адмінці: на сайті для відвідувача цим займатися нема чого,
 * а лишній запит на кожній сторінці коштує швидкості.
 *
 * @return void
 */
function scala_maybe_upgrade(): void {
	if ( wp_doing_ajax() || wp_doing_cron() || ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	if ( SCALA_VERSION === (string) get_option( SCALA_APPLIED, '' ) ) {
		return;
	}

	/*
	 * Замок на хвилину. Якщо десь усередині станеться помилка, дії не
	 * повторюватимуться на кожному кліку в адмінці — але й не будуть
	 * позначені виконаними, тож наступного разу спроба повториться.
	 */
	if ( get_transient( 'scala_upgrading' ) ) {
		return;
	}

	set_transient( 'scala_upgrading', 1, MINUTE_IN_SECONDS );

	scala_ensure_blog_page();

	if ( function_exists( 'scala_insert_missing_guides' ) ) {
		scala_insert_missing_guides();
	}

	update_option( SCALA_APPLIED, SCALA_VERSION, false );
	delete_transient( 'scala_upgrading' );
}
add_action( 'admin_init', 'scala_maybe_upgrade' );

/**
 * Сторінка зі списком статей.
 *
 * Без неї статті існують, але спільного входу в них немає: ні в меню,
 * ні у футері, ні для пошуковика. Створюємо один раз і призначаємо
 * сторінкою записів.
 *
 * @return void
 */
function scala_ensure_blog_page(): void {
	// Головна — окрема сторінка. Інакше записи й так на головній.
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		return;
	}

	$assigned = (int) get_option( 'page_for_posts' );

	if ( $assigned && 'publish' === get_post_status( $assigned ) ) {
		return;
	}

	$page = get_page_by_path( 'zhurnal' );

	if ( $page instanceof WP_Post ) {
		$page_id = (int) $page->ID;

		if ( 'publish' !== $page->post_status ) {
			wp_update_post(
				array(
					'ID'          => $page_id,
					'post_status' => 'publish',
				)
			);
		}
	} else {
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Журнал', 'scala' ),
				'post_name'    => 'zhurnal',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return;
		}
	}

	update_option( 'page_for_posts', (int) $page_id );
}
