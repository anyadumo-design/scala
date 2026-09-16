<?php
/**
 * Заявки в Telegram: бот надсилає повідомлення в канал або чат.
 *
 * Токен і чат вводяться в SCALA → Налаштування → Форма заявки.
 * Модуль вішається на гачок scala_lead_received, тож лист і Telegram
 * працюють незалежно: якщо Telegram не налаштовано або не відповів —
 * заявка все одно збережена і лист пішов.
 *
 * Під формою вкладки є свій блок: «Знайти чат» (бот сам каже, в які
 * канали його додали, — не треба шукати ID вручну) і «Надіслати тест».
 *
 * @package scala
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Опція з результатом останнього надсилання. */
define( 'SCALA_TG_STATUS', 'scala_telegram_status' );

/** Опція зі списком чатів, які знайшов бот. */
define( 'SCALA_TG_CHATS', 'scala_telegram_chats' );

/** Скільки секунд чекати на Telegram, коли надсилаємо заявку відвідувача. */
define( 'SCALA_TG_LEAD_TIMEOUT', 5 );

/* ------------------------------------------------------- Надсилання ---- */

/**
 * Токен бота без пробілів.
 *
 * @return string
 */
function scala_telegram_token(): string {
	return trim( (string) scala_opt( 'telegram_token', '' ) );
}

/**
 * Куди слати: -100… для каналу чи групи, @назва для публічного каналу.
 *
 * @return string
 */
function scala_telegram_chat(): string {
	return trim( (string) scala_opt( 'telegram_chat', '' ) );
}

/**
 * Чи заповнено і токен, і чат.
 *
 * @return bool
 */
function scala_telegram_configured(): bool {
	return '' !== scala_telegram_token() && '' !== scala_telegram_chat();
}

/**
 * Виклик методу Bot API.
 *
 * @param string $method  Назва методу: sendMessage, getUpdates…
 * @param array  $body    Параметри.
 * @param int    $timeout Скільки секунд чекати на відповідь.
 * @return mixed Поле result відповіді або WP_Error.
 */
function scala_telegram_api( string $method, array $body = array(), int $timeout = 8 ) {
	$token = scala_telegram_token();

	if ( '' === $token ) {
		return new WP_Error( 'scala_tg_token', __( 'Не вказано токен бота.', 'scala' ) );
	}

	$response = wp_remote_post(
		'https://api.telegram.org/bot' . rawurlencode( $token ) . '/' . $method,
		array(
			'timeout' => $timeout,
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'scala_tg_http', $response->get_error_message() );
	}

	/*
	 * JSON_BIGINT_AS_STRING: id каналу (-1001234567890) не вміщається
	 * в ціле число на 32-бітному PHP і став би дробовим, а після
	 * (string) — «-1.0012345679E+12». Рядком він лишається точним.
	 */
	$json = json_decode( (string) wp_remote_retrieve_body( $response ), true, 512, JSON_BIGINT_AS_STRING );

	if ( ! is_array( $json ) ) {
		return new WP_Error( 'scala_tg_json', __( 'Telegram відповів не JSON.', 'scala' ) );
	}

	if ( empty( $json['ok'] ) ) {
		$why = isset( $json['description'] ) ? (string) $json['description'] : __( 'невідома помилка', 'scala' );

		return new WP_Error( 'scala_tg_api', scala_telegram_explain( $why ) );
	}

	return $json['result'] ?? true;
}

/**
 * Перекладає типові відповіді Telegram на людську мову.
 *
 * @param string $why Поле description з відповіді.
 * @return string
 */
function scala_telegram_explain( string $why ): string {
	$lower = strtolower( $why );

	if ( str_contains( $lower, 'unauthorized' ) ) {
		return __( 'Telegram не приймає токен. Перевірте, чи скопійовано його повністю з @BotFather.', 'scala' );
	}

	if ( str_contains( $lower, 'chat not found' ) ) {
		return __( 'Чат не знайдено. Перевірте ID або натисніть «Знайти чат» — бот сам скаже, куди його додали.', 'scala' );
	}

	if ( str_contains( $lower, 'not enough rights' ) || str_contains( $lower, 'have no rights' ) ) {
		return __( 'Бот у чаті, але без права писати. Зробіть його адміністратором з правом надсилати повідомлення.', 'scala' );
	}

	if ( str_contains( $lower, 'bot was kicked' ) || str_contains( $lower, 'bot is not a member' ) ) {
		return __( 'Бота немає в цьому чаті. Додайте його як адміністратора.', 'scala' );
	}

	if ( str_contains( $lower, 'webhook is active' ) ) {
		return __( 'Для цього бота увімкнено webhook, і «Знайти чат» не працює. Впишіть ID чату вручну.', 'scala' );
	}

	if ( str_contains( $lower, 'too long' ) ) {
		return __( 'Повідомлення задовге для Telegram.', 'scala' );
	}

	return $why;
}

/**
 * Надсилає текст у налаштований чат і запамʼятовує результат.
 *
 * @param string $text    Повідомлення (Telegram-HTML: лише b, i, a, code).
 * @param string $what    Що це було — для рядка статусу в адмінці.
 * @param string $chat    Куди слати; порожньо — у налаштований чат.
 * @param int    $timeout Скільки секунд чекати на Telegram.
 * @return true|WP_Error
 */
function scala_telegram_send( string $text, string $what, string $chat = '', int $timeout = 8 ) {
	if ( '' === $chat ) {
		$chat = scala_telegram_chat();
	}

	if ( '' === $chat ) {
		return new WP_Error( 'scala_tg_chat', __( 'Не вказано канал або чат.', 'scala' ) );
	}

	$result = scala_telegram_api(
		'sendMessage',
		array(
			'chat_id'                  => $chat,
			'text'                     => $text,
			'parse_mode'               => 'HTML',
			'disable_web_page_preview' => 'true',
		),
		$timeout
	);

	$ok = ! is_wp_error( $result );

	update_option(
		SCALA_TG_STATUS,
		array(
			'time'  => time(),
			'ok'    => $ok,
			'what'  => $what,
			'error' => $ok ? '' : $result->get_error_message(),
		),
		false
	);

	return $ok ? true : $result;
}

/**
 * Екранує текст для Telegram-HTML: там особливі лише <, > та &.
 *
 * @param string $text Сирий текст.
 * @return string
 */
function scala_telegram_esc( string $text ): string {
	return htmlspecialchars( $text, ENT_NOQUOTES, 'UTF-8' );
}

/**
 * Обрізає значення до розумної довжини.
 *
 * Повідомлення в Telegram не може бути довшим за 4096 символів, а в
 * коментарі відвідувач може написати скільки завгодно. Ріжемо сире
 * значення до екранування: обрізати вже готовий HTML небезпечно —
 * можна розсікти сутність на кшталт &amp; і зламати розмітку.
 *
 * @param mixed $value Значення.
 * @param int   $max   Скільки символів лишити.
 * @return string
 */
function scala_telegram_cut( $value, int $max ): string {
	$value = trim( (string) $value );

	return mb_strlen( $value ) > $max ? mb_substr( $value, 0, $max - 1 ) . '…' : $value;
}

/**
 * Нова заявка → повідомлення в канал.
 *
 * @param int   $post_id ID заявки.
 * @param array $data    name, phone, need, note, src, label.
 * @return void
 */
function scala_telegram_lead( int $post_id, array $data ): void {
	if ( ! scala_telegram_configured() ) {
		return;
	}

	$site = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );

	$rows = array(
		__( 'Імʼя', 'scala' )     => scala_telegram_cut( $data['name'] ?? '', 200 ),
		__( 'Телефон', 'scala' )  => scala_telegram_cut( $data['phone'] ?? '', 50 ),
		__( 'Потрібно', 'scala' ) => scala_telegram_cut( $data['need'] ?? '', 400 ),
		__( 'Приміщення', 'scala' ) => scala_telegram_cut( $data['place'] ?? '', 60 ),
		__( 'Коментар', 'scala' ) => scala_telegram_cut( $data['note'] ?? '', 2000 ),
		__( 'Звідки', 'scala' )   => scala_telegram_cut( $data['label'] ?? '', 200 ),
		__( 'Сторінка', 'scala' ) => scala_telegram_cut( $data['src'] ?? '', 500 ),
	);

	/* translators: %s — назва сайту */
	$lines = array( '<b>' . scala_telegram_esc( sprintf( __( 'Нова заявка з сайту %s', 'scala' ), $site ) ) . '</b>', '' );

	foreach ( $rows as $label => $value ) {
		$lines[] = '<b>' . scala_telegram_esc( $label ) . ':</b> ' . ( '' !== $value ? scala_telegram_esc( $value ) : '—' );
	}

	// Звідки прийшли: канал, кампанія, перша сторінка.
	$traffic = isset( $data['traffic'] ) && is_array( $data['traffic'] ) ? $data['traffic'] : array();

	foreach ( scala_traffic_rows( $traffic ) as $label => $value ) {
		$lines[] = '<b>' . scala_telegram_esc( $label ) . ':</b> ' . scala_telegram_esc( scala_telegram_cut( $value, 300 ) );
	}

	/*
	 * Посилання збираємо самі, без get_edit_post_link(): той перевіряє
	 * права поточного користувача, а тут це відвідувач сайту, і для
	 * нього функція повертає null. Права перевіряться в браузері того,
	 * хто відкриє посилання.
	 */
	$edit = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

	$lines[] = '';
	$lines[] = sprintf(
		'<a href="%s">%s</a>',
		htmlspecialchars( $edit, ENT_QUOTES, 'UTF-8' ),
		scala_telegram_esc( __( 'Відкрити заявку в адмінці', 'scala' ) )
	);

	// Коротший таймаут: на цей виклик чекає відвідувач, який щойно натиснув «Надіслати».
	scala_telegram_send( implode( "\n", $lines ), __( 'заявка', 'scala' ), '', SCALA_TG_LEAD_TIMEOUT );
}
add_action( 'scala_lead_received', 'scala_telegram_lead', 10, 2 );

/* ---------------------------------------------------------- Адмінка ---- */

/**
 * Куди повертати після дій у блоці.
 *
 * @param array $args Параметри статусу для повідомлення.
 * @return string
 */
function scala_telegram_back_url( array $args ): string {
	return add_query_arg(
		array_merge(
			array(
				'page' => 'scala-settings',
				'tab'  => 'form',
			),
			$args
		),
		admin_url( 'admin.php' )
	) . '#scala-telegram';
}

/**
 * Спільна перевірка для дій: права + нонс.
 *
 * @param string $action Назва дії.
 * @return void
 */
function scala_telegram_guard( string $action ): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'scala' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( $action );
}

/**
 * Повертає на вкладку з повідомленням про помилку.
 *
 * @param string $message Текст помилки.
 * @return void
 */
function scala_telegram_fail( string $message ): void {
	wp_safe_redirect(
		scala_telegram_back_url(
			array(
				'scala-tg'     => 'err',
				'scala-tg-msg' => rawurlencode( $message ),
			)
		)
	);
	exit;
}

/**
 * Шле тестове повідомлення і повертає на сторінку з результатом.
 *
 * @param string $chat Куди; порожньо — у налаштований чат.
 * @return void
 */
function scala_telegram_run_test( string $chat = '' ): void {
	$site = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );

	$result = scala_telegram_send(
		/* translators: %s — назва сайту */
		'<b>' . scala_telegram_esc( sprintf( __( 'Перевірка звʼязку з сайтом %s', 'scala' ), $site ) ) . '</b>' . "\n"
		. scala_telegram_esc( __( 'Сюди приходитимуть заявки з форми на сайті.', 'scala' ) ),
		__( 'тест', 'scala' ),
		$chat
	);

	if ( is_wp_error( $result ) ) {
		scala_telegram_fail( $result->get_error_message() );
	}

	wp_safe_redirect( scala_telegram_back_url( array( 'scala-tg' => 'sent' ) ) );
	exit;
}

/**
 * Кнопка «Надіслати тест».
 *
 * @return void
 */
function scala_telegram_action_test(): void {
	scala_telegram_guard( 'scala_telegram_test' );
	scala_telegram_run_test();
}
add_action( 'admin_post_scala_telegram_test', 'scala_telegram_action_test' );

/**
 * Кнопка «Знайти чат»: питає бота, де він бачив повідомлення
 * або куди його додавали, і показує список для вибору.
 *
 * Telegram віддає чергу подій сторінками по 100 і лише за останню
 * добу. Тому проходимо чергу до кінця, а знайдені чати складаємо в
 * опцію: підтверджені сторінки Telegram більше не віддасть, але
 * список у таблиці лишиться.
 *
 * @return void
 */
function scala_telegram_action_discover(): void {
	scala_telegram_guard( 'scala_telegram_discover' );

	$known  = scala_telegram_known_chats();
	$before = count( $known );
	$fresh  = array();
	$offset = 0;
	$page   = array();
	$pages  = 0;

	do {
		$body = array(
			'limit'           => 100,
			'allowed_updates' => wp_json_encode(
				array( 'message', 'edited_message', 'channel_post', 'edited_channel_post', 'my_chat_member', 'chat_member' )
			),
		);

		if ( $offset ) {
			$body['offset'] = $offset;
		}

		$result = scala_telegram_api( 'getUpdates', $body, 12 );

		if ( is_wp_error( $result ) ) {
			scala_telegram_fail( $result->get_error_message() );
		}

		$page = (array) $result;

		foreach ( $page as $update ) {
			if ( isset( $update['update_id'] ) ) {
				$offset = max( $offset, (int) $update['update_id'] + 1 );
			}

			$chat = scala_telegram_chat_from_update( (array) $update );

			if ( $chat ) {
				$fresh[ $chat['id'] ] = $chat;
			}
		}

		++$pages;
	} while ( count( $page ) >= 100 && $pages < 10 );

	// Щойно знайдені — зверху, решта як була; довгий хвіст не тримаємо.
	$known = array_slice( $fresh + $known, 0, 20, true );

	update_option( SCALA_TG_CHATS, $known, false );

	wp_safe_redirect(
		scala_telegram_back_url(
			array(
				'scala-tg'     => 'found',
				'scala-tg-n'   => count( $known ),
				'scala-tg-new' => max( 0, count( $known ) - $before ),
			)
		)
	);
	exit;
}
add_action( 'admin_post_scala_telegram_discover', 'scala_telegram_action_discover' );

/**
 * Витягає чат з однієї події Telegram.
 *
 * @param array $update Подія.
 * @return array|null id, title, type — або null, якщо чату немає.
 */
function scala_telegram_chat_from_update( array $update ) {
	$kinds = array( 'message', 'edited_message', 'channel_post', 'edited_channel_post', 'my_chat_member', 'chat_member' );

	foreach ( $kinds as $kind ) {
		if ( empty( $update[ $kind ]['chat']['id'] ) ) {
			continue;
		}

		$chat = $update[ $kind ]['chat'];
		$name = $chat['title'] ?? ( $chat['username'] ?? ( $chat['first_name'] ?? $chat['id'] ) );

		return array(
			'id'    => (string) $chat['id'],
			'title' => sanitize_text_field( (string) $name ),
			'type'  => sanitize_key( (string) ( $chat['type'] ?? '' ) ),
		);
	}

	return null;
}

/**
 * Збережений список знайдених чатів.
 *
 * @return array id => (id, title, type)
 */
function scala_telegram_known_chats(): array {
	$saved = get_option( SCALA_TG_CHATS, array() );
	$clean = array();

	foreach ( (array) $saved as $one ) {
		if ( empty( $one['id'] ) ) {
			continue;
		}

		$clean[ (string) $one['id'] ] = array(
			'id'    => (string) $one['id'],
			'title' => (string) ( $one['title'] ?? $one['id'] ),
			'type'  => (string) ( $one['type'] ?? '' ),
		);
	}

	return $clean;
}

/**
 * Кнопка «Використати» біля знайденого чату: зберігає ID і одразу шле тест.
 *
 * @return void
 */
function scala_telegram_action_use_chat(): void {
	scala_telegram_guard( 'scala_telegram_use_chat' );

	$chat = isset( $_POST['chat'] ) ? sanitize_text_field( wp_unslash( $_POST['chat'] ) ) : '';

	if ( '' === $chat ) {
		scala_telegram_fail( __( 'Порожній ID чату.', 'scala' ) );
	}

	$options                  = get_option( SCALA_OPT_KEY, array() );
	$options                  = is_array( $options ) ? $options : array();
	$options['telegram_chat'] = $chat;

	/*
	 * Пишемо повз фільтр очищення. Він розрахований на форму: без
	 * позначки вкладки чистить усю схему й полям, яких ще немає в
	 * базі, проставив би порожні значення — тобто одне натискання
	 * «Використати» могло б збити тексти вкладок, яких ще не
	 * відкривали. Тут чистити нічого: значення або з бази, або вже
	 * пройшли sanitize_text_field().
	 */
	remove_filter( 'sanitize_option_' . SCALA_OPT_KEY, 'scala_sanitize_options' );
	update_option( SCALA_OPT_KEY, $options );
	add_filter( 'sanitize_option_' . SCALA_OPT_KEY, 'scala_sanitize_options' );

	// Кеш scala_options() уже прочитав старе, тому чат передаємо явно.
	scala_telegram_run_test( $chat );
}
add_action( 'admin_post_scala_telegram_use_chat', 'scala_telegram_action_use_chat' );

/**
 * Результат останньої дії — з адреси сторінки.
 *
 * @return array class, text — або порожній масив.
 */
function scala_telegram_result_message(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- лише показ результату після редиректу.
	if ( ! isset( $_GET['page'], $_GET['scala-tg'] ) || 'scala-settings' !== $_GET['page'] ) {
		return array();
	}

	$state = sanitize_key( wp_unslash( $_GET['scala-tg'] ) );
	$msg   = isset( $_GET['scala-tg-msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['scala-tg-msg'] ) ) ) : '';
	$n     = isset( $_GET['scala-tg-n'] ) ? (int) $_GET['scala-tg-n'] : 0;
	$new   = isset( $_GET['scala-tg-new'] ) ? (int) $_GET['scala-tg-new'] : 0;
	// phpcs:enable

	switch ( $state ) {
		case 'sent':
			return array(
				'class' => 'notice-success',
				'text'  => __( 'Тестове повідомлення надіслано — перевірте чат.', 'scala' ),
			);

		case 'found':
			if ( ! $n ) {
				return array(
					'class' => 'notice-warning',
					'text'  => __( 'Бот поки нікого не бачить. Що робити — написано нижче, під кнопками.', 'scala' ),
				);
			}

			return array(
				'class' => 'notice-success',
				'text'  => $new
					/* translators: %d — скільки нових чатів */
					? sprintf( _n( 'Знайдено %d новий чат — він у списку нижче.', 'Знайдено нових чатів: %d — вони у списку нижче.', $new, 'scala' ), $new )
					: __( 'Нових чатів немає. У списку нижче — ті, що бот бачив раніше.', 'scala' ),
			);

		default:
			return array(
				'class' => 'notice-error',
				'text'  => __( 'Telegram: ', 'scala' ) . $msg,
			);
	}
}

/**
 * Повідомлення після дій — угорі сторінки налаштувань.
 *
 * @return void
 */
function scala_telegram_notice(): void {
	$message = scala_telegram_result_message();

	if ( ! $message ) {
		return;
	}

	printf(
		'<div class="notice %s is-dismissible"><p>%s</p></div>',
		esc_attr( $message['class'] ),
		esc_html( $message['text'] )
	);
}
add_action( 'admin_notices', 'scala_telegram_notice' );

/**
 * Рядок стану: що і коли востаннє надсилали.
 *
 * @return string
 */
function scala_telegram_status_line(): string {
	$token  = scala_telegram_token();
	$chat   = scala_telegram_chat();
	$status = get_option( SCALA_TG_STATUS, array() );
	$status = is_array( $status ) ? $status : array();

	if ( '' === $token ) {
		return __( 'Не підключено. Впишіть токен бота в поле вище й натисніть «Зберегти».', 'scala' );
	}

	if ( '' === $chat ) {
		return __( 'Токен є, чат ще не обрано. Натисніть «Знайти чат» або впишіть ID вручну й збережіть.', 'scala' );
	}

	if ( empty( $status['time'] ) ) {
		return __( 'Підключено, але ще нічого не надсилали. Натисніть «Надіслати тест».', 'scala' );
	}

	$when = wp_date( 'd.m.Y H:i', (int) $status['time'] );

	if ( ! empty( $status['ok'] ) ) {
		/* translators: 1 — що надсилали (заявка/тест), 2 — дата й час */
		return sprintf( __( 'Працює. Останнє надсилання (%1$s): %2$s.', 'scala' ), (string) $status['what'], $when );
	}

	/* translators: 1 — дата й час, 2 — текст помилки */
	return sprintf( __( 'Помилка при останньому надсиланні (%1$s): %2$s', 'scala' ), $when, (string) $status['error'] );
}

/**
 * Блок під формою вкладки «Форма заявки».
 *
 * @param string $tab Поточна вкладка.
 * @return void
 */
function scala_telegram_box( string $tab ): void {
	if ( 'form' !== $tab ) {
		return;
	}

	$token   = scala_telegram_token();
	$chat    = scala_telegram_chat();
	$found   = scala_telegram_known_chats();
	$message = scala_telegram_result_message();
	?>
	<div class="scala-tgbox" id="scala-telegram">
		<h2 class="scala-tgbox__title"><?php esc_html_e( 'Заявки в Telegram', 'scala' ); ?></h2>

		<?php if ( $message ) : ?>
			<div class="notice inline <?php echo esc_attr( $message['class'] ); ?> scala-tgbox__result">
				<p><?php echo esc_html( $message['text'] ); ?></p>
			</div>
		<?php endif; ?>

		<p class="scala-tgbox__status"><?php echo esc_html( scala_telegram_status_line() ); ?></p>

		<div class="scala-tgbox__actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'scala_telegram_discover' ); ?>
				<input type="hidden" name="action" value="scala_telegram_discover">
				<button type="submit" class="button" <?php disabled( '' === $token ); ?>>
					<?php esc_html_e( 'Знайти чат', 'scala' ); ?>
				</button>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'scala_telegram_test' ); ?>
				<input type="hidden" name="action" value="scala_telegram_test">
				<button type="submit" class="button button-primary" <?php disabled( ! scala_telegram_configured() ); ?>>
					<?php esc_html_e( 'Надіслати тест', 'scala' ); ?>
				</button>
			</form>
		</div>

		<?php if ( $found ) : ?>
			<table class="widefat striped scala-tgbox__found">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Чат', 'scala' ); ?></th>
						<th><?php esc_html_e( 'Тип', 'scala' ); ?></th>
						<th><?php esc_html_e( 'ID', 'scala' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $found as $one ) : ?>
						<tr>
							<td><?php echo esc_html( $one['title'] ); ?></td>
							<td><?php echo esc_html( scala_telegram_chat_type( $one['type'] ) ); ?></td>
							<td><code><?php echo esc_html( $one['id'] ); ?></code></td>
							<td>
								<?php if ( $one['id'] === $chat ) : ?>
									<span class="scala-tgbox__current"><?php esc_html_e( 'обрано', 'scala' ); ?></span>
								<?php else : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'scala_telegram_use_chat' ); ?>
										<input type="hidden" name="action" value="scala_telegram_use_chat">
										<input type="hidden" name="chat" value="<?php echo esc_attr( $one['id'] ); ?>">
										<button type="submit" class="button button-small"><?php esc_html_e( 'Використати', 'scala' ); ?></button>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<details class="scala-tgbox__howto" <?php echo $found ? '' : 'open'; ?>>
			<summary><?php esc_html_e( 'Як підключити', 'scala' ); ?></summary>

			<ol>
				<li><?php esc_html_e( 'У Telegram відкрийте @BotFather, надішліть /newbot і дайте боту назву. У відповідь прийде токен — довгий рядок з двокрапкою всередині. (Якщо бот уже є: /mybots → бот → API Token.)', 'scala' ); ?></li>
				<li><?php esc_html_e( 'Вставте токен у поле «Telegram: токен бота» вище й натисніть «Зберегти».', 'scala' ); ?></li>
				<li><?php esc_html_e( 'Додайте бота туди, куди мають приходити заявки — у групу або в канал.', 'scala' ); ?></li>
				<li><?php esc_html_e( 'Дайте боту побачити чат: у групі надішліть команду /start@назва_бота (звичайні повідомлення бот у групах не бачить, а команду бачить завжди), у каналі зробіть бота адміністратором і опублікуйте будь-який пост.', 'scala' ); ?></li>
				<li><?php esc_html_e( 'Натисніть «Знайти чат», а біля потрібного — «Використати». Тестове повідомлення прийде одразу.', 'scala' ); ?></li>
			</ol>

			<p><?php esc_html_e( 'Щоб бот міг писати в канал, він має бути адміністратором з правом надсилати повідомлення. У групі досить звичайного учасника.', 'scala' ); ?></p>
			<p><?php esc_html_e( 'Telegram памʼятає такі події лише добу: якщо бота додали давно й відтоді в чаті нічого не було, просто надішліть у чат /start@назва_бота і натисніть «Знайти чат» ще раз.', 'scala' ); ?></p>
			<p><?php esc_html_e( 'Лист на пошту приходить незалежно від Telegram: якщо одне з двох не спрацює, заявка все одно збережеться в розділі «Заявки».', 'scala' ); ?></p>
		</details>
	</div>
	<?php
}
add_action( 'scala_options_after_form', 'scala_telegram_box' );

/**
 * Назва типу чату для таблиці.
 *
 * @param string $type Тип із Bot API.
 * @return string
 */
function scala_telegram_chat_type( string $type ): string {
	$names = array(
		'channel'    => __( 'канал', 'scala' ),
		'group'      => __( 'група', 'scala' ),
		'supergroup' => __( 'група', 'scala' ),
		'private'    => __( 'особистий чат', 'scala' ),
	);

	return $names[ $type ] ?? $type;
}
