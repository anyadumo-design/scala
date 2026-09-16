<?php
/**
 * Приймання заявок з форми.
 *
 * Заявка зберігається як запис у розділі «Заявки» і дублюється листом.
 * Навіть якщо пошта не піде, звернення не загубиться.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Обробник AJAX-запиту з форми.
 *
 * @return void
 */
function scala_handle_lead(): void {
	// 1. Нонс.
	if ( ! check_ajax_referer( 'scala_lead', 'nonce', false ) ) {
		wp_send_json_error( __( 'Сторінку відкрито надто давно. Оновіть її й спробуйте ще раз.', 'scala' ), 403 );
	}

	// 2. Пастка для ботів: люди це поле не бачать і не заповнюють.
	$honeypot = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';

	if ( '' !== $honeypot ) {
		// Ботові показуємо успіх, щоб не підбирав обхід.
		wp_send_json_success( array( 'skipped' => true ) );
	}

	// 3. Обмеження частоти: не більше 5 заявок за 10 хвилин з однієї адреси.
	$ip  = scala_client_ip();
	$key = 'scala_lead_' . md5( $ip );
	$hits = (int) get_transient( $key );

	if ( $hits >= 5 ) {
		wp_send_json_error( __( 'Забагато спроб. Зачекайте кілька хвилин або зателефонуйте нам.', 'scala' ), 429 );
	}

	// 4. Дані.
	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	/*
	 * «Що потрібно» — кілька позначок, приходить масивом need[]. Один
	 * рядок теж приймаємо: сторінка могла бути відкрита ще до оновлення,
	 * коли тут був випадаючий список.
	 */
	$need_raw = isset( $_POST['need'] ) ? wp_unslash( $_POST['need'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$need     = implode(
		', ',
		array_slice( array_filter( array_map( 'sanitize_text_field', (array) $need_raw ), 'strlen' ), 0, 12 )
	);
	$place    = isset( $_POST['place'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['place'] ) ), 0, 60 ) : '';
	$note  = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$src   = isset( $_POST['source'] ) ? esc_url_raw( wp_unslash( $_POST['source'] ) ) : '';
	// Назва блоку, з якого відкрито форму: «Hero — головна кнопка» тощо.
	$label = isset( $_POST['source_label'] ) ? sanitize_text_field( wp_unslash( $_POST['source_label'] ) ) : '';

	if ( '' === trim( $phone ) ) {
		wp_send_json_error( __( 'Вкажіть, будь ласка, номер телефону.', 'scala' ), 400 );
	}

	/*
	 * Зводимо номер до одного вигляду: +38 (0XX) XXX-XX-XX.
	 * Маска на сайті це вже робить, але форма має працювати й без
	 * скриптів, а в адмінці й у листі номери мають виглядати однаково.
	 */
	$normalized = scala_normalize_phone( $phone );

	if ( '' === $normalized ) {
		wp_send_json_error( __( 'Схоже, у номері помилка: після +38 0 має бути дев\'ять цифр.', 'scala' ), 400 );
	}

	$phone = $normalized;

	set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

	// 5. Запис.
	$title = $name ? $name . ' — ' . $phone : $phone;

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'scala_lead',
			'post_status' => 'publish',
			'post_title'  => wp_strip_all_tags( $title ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( __( 'Не вдалося зберегти заявку. Зателефонуйте нам, будь ласка.', 'scala' ), 500 );
	}

	update_post_meta( $post_id, '_scala_name', $name );
	update_post_meta( $post_id, '_scala_phone', $phone );
	update_post_meta( $post_id, '_scala_need', $need );
	update_post_meta( $post_id, '_scala_place', $place );
	update_post_meta( $post_id, '_scala_note', $note );
	update_post_meta( $post_id, '_scala_source', $src );
	update_post_meta( $post_id, '_scala_source_label', $label );

	// Звідки прийшли: UTM-мітки, перехід, перша сторінка сеансу.
	$traffic = scala_collect_traffic();
	update_post_meta( $post_id, '_scala_traffic', $traffic );

	// 6. Лист.
	scala_notify_lead( $name, $phone, $need, $note, $src, $label, $traffic, $place );

	/**
	 * Для інтеграцій: CRM, Telegram-бот тощо.
	 *
	 * @param int   $post_id ID заявки.
	 * @param array $data    Дані заявки.
	 */
	do_action(
		'scala_lead_received',
		$post_id,
		compact( 'name', 'phone', 'need', 'place', 'note', 'src', 'label', 'traffic' )
	);

	wp_send_json_success( array( 'id' => $post_id ) );
}
add_action( 'wp_ajax_scala_lead', 'scala_handle_lead' );
add_action( 'wp_ajax_nopriv_scala_lead', 'scala_handle_lead' );

/**
 * Зводить український номер до вигляду +38 (0XX) XXX-XX-XX.
 *
 * Приймає 0971233330, 380971233330, +38 097 123 33 30 і подібне.
 * Повертає порожній рядок, якщо після коду не набирається девʼять цифр.
 *
 * @param string $raw Що ввели.
 * @return string
 */
function scala_normalize_phone( string $raw ): string {
	$d = preg_replace( '~\D~', '', $raw );

	if ( str_starts_with( $d, '380' ) ) {
		$d = substr( $d, 3 );
	} elseif ( str_starts_with( $d, '38' ) && strlen( $d ) >= 11 ) {
		$d = substr( $d, 2 );
	} elseif ( str_starts_with( $d, '0' ) ) {
		$d = substr( $d, 1 );
	}

	if ( 9 !== strlen( $d ) ) {
		return '';
	}

	return sprintf(
		'+38 (0%s) %s-%s-%s',
		substr( $d, 0, 2 ),
		substr( $d, 2, 3 ),
		substr( $d, 5, 2 ),
		substr( $d, 7, 2 )
	);
}

/**
 * Надсилає лист про нову заявку.
 *
 * @param string $name    Імʼя.
 * @param string $phone   Телефон.
 * @param string $need    Що потрібно.
 * @param string $note    Коментар.
 * @param string $src     Сторінка-джерело.
 * @param string $label   Блок, з якого відкрито форму.
 * @param array  $traffic Звідки прийшли: UTM-мітки, перехід, перша сторінка.
 * @param string $place   Тип приміщення.
 * @return void
 */
function scala_notify_lead( string $name, string $phone, string $need, string $note, string $src, string $label = '', array $traffic = array(), string $place = '' ): void {
	$to = (string) scala_opt( 'notify_email', '' );

	if ( ! $to ) {
		$to = (string) get_option( 'admin_email' );
	}

	if ( ! $to ) {
		return;
	}

	$site = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );

	/* translators: %s — назва сайту */
	$subject = sprintf( __( 'Нова заявка з сайту %s', 'scala' ), $site );

	$lines = array(
		__( 'Імʼя:', 'scala' ) . ' ' . ( $name ?: '—' ),
		__( 'Телефон:', 'scala' ) . ' ' . $phone,
		__( 'Потрібно:', 'scala' ) . ' ' . ( $need ?: '—' ),
		__( 'Приміщення:', 'scala' ) . ' ' . ( $place ?: '—' ),
		__( 'Коментар:', 'scala' ) . ' ' . ( $note ?: '—' ),
		__( 'Звідки:', 'scala' ) . ' ' . ( $label ?: '—' ),
		__( 'Сторінка:', 'scala' ) . ' ' . ( $src ?: '—' ),
	);

	foreach ( scala_traffic_rows( $traffic ) as $traffic_label => $traffic_value ) {
		$lines[] = $traffic_label . ': ' . $traffic_value;
	}

	$lines[] = '';
	$lines[] = __( 'Усі заявки:', 'scala' ) . ' ' . admin_url( 'edit.php?post_type=scala_lead' );

	wp_mail(
		array_map( 'trim', explode( ',', $to ) ),
		$subject,
		implode( "\n", $lines )
	);
}

/**
 * IP клієнта для обмеження частоти.
 *
 * @return string
 */
function scala_client_ip(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '0.0.0.0';

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Лічильник нових заявок біля пункту меню.
 *
 * @return void
 */
function scala_lead_menu_bubble(): void {
	global $menu, $submenu;

	$count = wp_count_posts( 'scala_lead' );
	$new   = isset( $count->publish ) ? (int) $count->publish : 0;

	if ( ! $new || empty( $submenu['scala-settings'] ) ) {
		return;
	}

	foreach ( $submenu['scala-settings'] as $index => $item ) {
		if ( isset( $item[2] ) && 'edit.php?post_type=scala_lead' === $item[2] ) {
			$submenu['scala-settings'][ $index ][0] .= sprintf(
				' <span class="awaiting-mod"><span class="pending-count">%d</span></span>',
				$new
			);
			break;
		}
	}
}
add_action( 'admin_menu', 'scala_lead_menu_bubble', 999 );
