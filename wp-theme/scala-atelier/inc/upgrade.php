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
	if ( wp_doing_ajax() ) {
		return;
	}

	/*
	 * Не лише в адмінці. Інакше дія чекала б, поки власниця відкриє
	 * панель, — а вона про це й не знає. На першому ж запиті до сайту
	 * після оновлення теми все виконується саме.
	 */
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
	scala_sync_guides();

	// До заповнення SEO-полів: опис сторінки виду штор береться зі вступу.
	if ( function_exists( 'scala_apply_copy_fixes' ) ) {
		scala_apply_copy_fixes();
	}

	scala_fill_seo_fields();
	scala_move_commercial_need();

	update_option( SCALA_APPLIED, SCALA_VERSION, false );
	delete_transient( 'scala_upgrading' );
}
add_action( 'init', 'scala_maybe_upgrade', 20 );

/**
 * Звіряє матеріали з заготовками.
 *
 * Окремо від оновлення версії, бо привід буває й інший: сторінку під
 * кімнату опублікували пізніше, і посилання на неї в статті має
 * зʼявитися само, без жодної кнопки. Перевірка дешева — кілька
 * запитів, — але щогодини, а не на кожному кліку в адмінці.
 *
 * @return void
 */
function scala_sync_guides(): void {
	if ( ! function_exists( 'scala_insert_missing_guides' ) ) {
		return;
	}

	scala_insert_missing_guides();
	set_transient( 'scala_guides_synced', 1, HOUR_IN_SECONDS );
}

/**
 * Щогодинна звірка матеріалів поза оновленням теми.
 *
 * @return void
 */
function scala_maybe_sync_guides(): void {
	if ( wp_doing_ajax() ) {
		return;
	}

	if ( get_transient( 'scala_guides_synced' ) ) {
		return;
	}

	// Під час оновлення версії звірка вже відбудеться — не подвоюємо.
	if ( SCALA_VERSION !== (string) get_option( SCALA_APPLIED, '' ) ) {
		return;
	}

	scala_sync_guides();
}
add_action( 'init', 'scala_maybe_sync_guides', 21 );

/**
 * Заповнює порожні поля опису в SEO-плагіні.
 *
 * Тема й так підставляє опис у розмітку, коли поле порожнє. Але в
 * редакторі воно виглядає незаповненим, і зрозуміти, чи все гаразд,
 * неможливо. Тому переносимо той самий текст у саме поле: тепер його
 * видно, і його можна відредагувати.
 *
 * Заповнене людиною не чіпаємо ніколи.
 *
 * @return void
 */
function scala_fill_seo_fields(): void {
	if ( ! function_exists( 'scala_description_for' ) ) {
		return;
	}

	$ids = get_posts(
		array(
			'post_type'        => array( 'post', 'page', 'scala_type', 'scala_project', 'scala_fabric' ),
			'post_status'      => 'publish',
			'posts_per_page'   => 200,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	foreach ( $ids as $id ) {
		$id = (int) $id;

		/*
		 * Три поля, які плагін очікує від людини. Заповнюємо лише
		 * порожні: те, що вписали руками, важливіше за наш здогад.
		 */
		$fields = array(
			'_yoast_wpseo_title'    => scala_title_for( $id ),
			'_yoast_wpseo_metadesc' => scala_description_for( $id ),
			'_yoast_wpseo_focuskw'  => scala_keyphrase_for( $id ),
		);

		foreach ( $fields as $key => $value ) {
			if ( '' === $value ) {
				continue;
			}

			$now = trim( (string) get_post_meta( $id, $key, true ) );

			/*
			 * Порожнє заповнюємо. Непорожнє чіпаємо лише тоді, коли це
			 * рівно те, що ми самі й записали минулого разу: свої
			 * формулювання можна покращувати, чужі — ні.
			 */
			$ours = (string) get_post_meta( $id, '_scala_seo_' . ltrim( $key, '_' ), true );

			/*
			 * Перші заповнення робились ще без відбитка, тож у них
			 * $ours порожній. Для сторінок, які створила сама тема,
			 * це наше ж значення — його можна виправити.
			 */
			$seeded = function_exists( 'scala_is_seeded_guide' ) && scala_is_seeded_guide( $id );

			if ( '' !== $now && md5( $now ) !== $ours && ! ( '' === $ours && $seeded ) ) {
				continue;
			}

			if ( $now === $value ) {
				continue;
			}

			update_post_meta( $id, $key, $value );
			update_post_meta( $id, '_scala_seo_' . ltrim( $key, '_' ), md5( $value ) );
		}
	}
}

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

/**
 * «Комерційний простір» — тепер тип приміщення, а не те, що оформлюють.
 *
 * У формі з'явилось окреме поле «Тип приміщення». Якби варіант лишився
 * і в «Що потрібно оформити», людина бачила б його двічі. Прибираємо
 * лише цей рядок і лише якщо назва збігається дослівно: решту списку,
 * який могли редагувати в адмінці, не чіпаємо.
 *
 * @return void
 */
function scala_move_commercial_need(): void {
	$options = get_option( SCALA_OPT_KEY, array() );

	if ( ! is_array( $options ) || empty( $options['needs'] ) || ! is_array( $options['needs'] ) ) {
		return;
	}

	$kept = array_values(
		array_filter(
			$options['needs'],
			static function ( $row ): bool {
				return ! ( is_array( $row ) && 'Комерційний простір' === trim( (string) ( $row['text'] ?? '' ) ) );
			}
		)
	);

	if ( count( $kept ) === count( $options['needs'] ) ) {
		return;
	}

	$options['needs'] = $kept;

	// Повз фільтр очищення форми — пояснення в scala_telegram_action_use_chat().
	remove_filter( 'sanitize_option_' . SCALA_OPT_KEY, 'scala_sanitize_options' );
	update_option( SCALA_OPT_KEY, $options );
	add_filter( 'sanitize_option_' . SCALA_OPT_KEY, 'scala_sanitize_options' );
}
