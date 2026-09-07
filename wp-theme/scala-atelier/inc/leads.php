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
	$need  = isset( $_POST['need'] ) ? sanitize_text_field( wp_unslash( $_POST['need'] ) ) : '';
	$note  = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$src   = isset( $_POST['source'] ) ? esc_url_raw( wp_unslash( $_POST['source'] ) ) : '';
	// Назва блоку, з якого відкрито форму: «Hero — головна кнопка» тощо.
	$label = isset( $_POST['source_label'] ) ? sanitize_text_field( wp_unslash( $_POST['source_label'] ) ) : '';

	if ( '' === trim( $phone ) ) {
		wp_send_json_error( __( 'Вкажіть, будь ласка, номер телефону.', 'scala' ), 400 );
	}

	// Мінімальна перевірка: у номері має бути хоча б 9 цифр.
	if ( strlen( preg_replace( '~\D~', '', $phone ) ) < 9 ) {
		wp_send_json_error( __( 'Схоже, у номері помилка. Перевірте, будь ласка.', 'scala' ), 400 );
	}

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
	update_post_meta( $post_id, '_scala_note', $note );
	update_post_meta( $post_id, '_scala_source', $src );
	update_post_meta( $post_id, '_scala_source_label', $label );

	// 6. Лист.
	scala_notify_lead( $name, $phone, $need, $note, $src, $label );

	/**
	 * Для інтеграцій: CRM, Telegram-бот тощо.
	 *
	 * @param int   $post_id ID заявки.
	 * @param array $data    Дані заявки.
	 */
	do_action(
		'scala_lead_received',
		$post_id,
		compact( 'name', 'phone', 'need', 'note', 'src', 'label' )
	);

	wp_send_json_success( array( 'id' => $post_id ) );
}
add_action( 'wp_ajax_scala_lead', 'scala_handle_lead' );
add_action( 'wp_ajax_nopriv_scala_lead', 'scala_handle_lead' );

/**
 * Надсилає лист про нову заявку.
 *
 * @param string $name  Імʼя.
 * @param string $phone Телефон.
 * @param string $need  Що потрібно.
 * @param string $note  Коментар.
 * @param string $src   Сторінка-джерело.
 * @param string $label Блок, з якого відкрито форму.
 * @return void
 */
function scala_notify_lead( string $name, string $phone, string $need, string $note, string $src, string $label = '' ): void {
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
		__( 'Коментар:', 'scala' ) . ' ' . ( $note ?: '—' ),
		__( 'Звідки:', 'scala' ) . ' ' . ( $label ?: '—' ),
		__( 'Сторінка:', 'scala' ) . ' ' . ( $src ?: '—' ),
		'',
		__( 'Усі заявки:', 'scala' ) . ' ' . admin_url( 'edit.php?post_type=scala_lead' ),
	);

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
