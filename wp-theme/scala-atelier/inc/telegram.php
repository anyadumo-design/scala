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

/** Транзієнт зі списком чатів, які знайшов бот. */
define( 'SCALA_TG_CHATS', 'scala_telegram_chats' );

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
 * @param string $method Назва методу: sendMessage, getUpdates…
 * @param array  $body   Параметри.
 * @return mixed Поле result відповіді або WP_Error.
 */
function scala_telegram_api( string $method, array $body = array() ) {
	$token = scala_telegram_token();

	if ( '' === $token ) {
		return new WP_Error( 'scala_tg_token', __( 'Не вказано токен бота.', 'scala' ) );
	}

	$response = wp_remote_post(
		'https://api.telegram.org/bot' . rawurlencode( $token ) . '/' . $method,
		array(
			'timeout' => 8,
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'scala_tg_http', $response->get_error_message() );
	}

	$json = json_decode( (string) wp_remote_retrieve_body( $response ), true );

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
		return __( 'Бот у каналі, але без права писати. Зробіть його адміністратором з правом публікувати повідомлення.', 'scala' );
	}

	if ( str_contains( $lower, 'bot was kicked' ) || str_contains( $lower, 'bot is not a member' ) ) {
		return __( 'Бота немає в цьому чаті. Додайте його як адміністратора.', 'scala' );
	}

	if ( str_contains( $lower, 'webhook is active' ) ) {
		return __( 'Для цього бота увімкнено webhook, і «Знайти чат» не працює. Впишіть ID чату вручну.', 'scala' );
	}

	return $why;
}

/**
 * Надсилає текст у налаштований чат і запамʼятовує результат.
 *
 * @param string $text Повідомлення (Telegram-HTML: лише b, i, a, code).
 * @param string $what Що це було — для рядка статусу в адмінці.
 * @return true|WP_Error
 */
function scala_telegram_send( string $text, string $what ) {
	$chat = scala_telegram_chat();

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
		)
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
		__( 'Імʼя', 'scala' )     => $data['name'] ?? '',
		__( 'Телефон', 'scala' )  => $data['phone'] ?? '',
		__( 'Потрібно', 'scala' ) => $data['need'] ?? '',
		__( 'Коментар', 'scala' ) => $data['note'] ?? '',
		__( 'Звідки', 'scala' )   => $data['label'] ?? '',
		__( 'Сторінка', 'scala' ) => $data['src'] ?? '',
	);

	/* translators: %s — назва сайту */
	$lines = array( '<b>' . scala_telegram_esc( sprintf( __( 'Нова заявка з сайту %s', 'scala' ), $site ) ) . '</b>', '' );

	foreach ( $rows as $label => $value ) {
		$value   = trim( (string) $value );
		$lines[] = '<b>' . scala_telegram_esc( $label ) . ':</b> ' . ( '' !== $value ? scala_telegram_esc( $value ) : '—' );
	}

	$lines[] = '';
	$lines[] = sprintf(
		'<a href="%s">%s</a>',
		htmlspecialchars( get_edit_post_link( $post_id, 'raw' ) ?: admin_url( 'edit.php?post_type=scala_lead' ), ENT_QUOTES, 'UTF-8' ),
		scala_telegram_esc( __( 'Відкрити заявку в адмінці', 'scala' ) )
	);

	scala_telegram_send( implode( "\n", $lines ), __( 'заявка', 'scala' ) );
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
 * Кнопка «Надіслати тест».
 *
 * @return void
 */
function scala_telegram_action_test(): void {
	scala_telegram_guard( 'scala_telegram_test' );

	$site = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );

	$result = scala_telegram_send(
		/* translators: %s — назва сайту */
		'<b>' . scala_telegram_esc( sprintf( __( 'Перевірка звʼязку з сайтом %s', 'scala' ), $site ) ) . '</b>' . "\n"
		. scala_telegram_esc( __( 'Сюди приходитимуть заявки з форми на сайті.', 'scala' ) ),
		__( 'тест', 'scala' )
	);

	wp_safe_redirect(
		scala_telegram_back_url(
			is_wp_error( $result )
				? array(
					'scala-tg'     => 'err',
					'scala-tg-msg' => rawurlencode( $result->get_error_message() ),
				)
				: array( 'scala-tg' => 'sent' )
		)
	);
	exit;
}
add_action( 'admin_post_scala_telegram_test', 'scala_telegram_action_test' );

/**
 * Кнопка «Знайти чат»: питає бота, де він бачив повідомлення
 * або куди його додавали, і показує список для вибору.
 *
 * @return void
 */
function scala_telegram_action_discover(): void {
	scala_telegram_guard( 'scala_telegram_discover' );

	$updates = scala_telegram_api(
		'getUpdates',
		array(
			'limit'           => 100,
			'allowed_updates' => wp_json_encode( array( 'message', 'channel_post', 'my_chat_member' ) ),
		)
	);

	if ( is_wp_error( $updates ) ) {
		wp_safe_redirect(
			scala_telegram_back_url(
				array(
					'scala-tg'     => 'err',
					'scala-tg-msg' => rawurlencode( $updates->get_error_message() ),
				)
			)
		);
		exit;
	}

	$chats = array();

	foreach ( (array) $updates as $update ) {
		foreach ( array( 'message', 'channel_post', 'edited_message', 'edited_channel_post', 'my_chat_member' ) as $kind ) {
			if ( empty( $update[ $kind ]['chat']['id'] ) ) {
				continue;
			}

			$chat = $update[ $kind ]['chat'];
			$id   = (string) $chat['id'];
			$name = $chat['title'] ?? ( $chat['username'] ?? ( $chat['first_name'] ?? $id ) );

			$chats[ $id ] = array(
				'id'    => $id,
				'title' => sanitize_text_field( (string) $name ),
				'type'  => sanitize_key( (string) ( $chat['type'] ?? '' ) ),
			);
		}
	}

	set_transient( SCALA_TG_CHATS, array_values( $chats ), HOUR_IN_SECONDS );

	wp_safe_redirect(
		scala_telegram_back_url(
			array(
				'scala-tg'   => 'found',
				'scala-tg-n' => count( $chats ),
			)
		)
	);
	exit;
}
add_action( 'admin_post_scala_telegram_discover', 'scala_telegram_action_discover' );

/**
 * Кнопка «Використати» біля знайденого чату: зберігає ID і одразу шле тест.
 *
 * @return void
 */
function scala_telegram_action_use_chat(): void {
	scala_telegram_guard( 'scala_telegram_use_chat' );

	$chat = isset( $_POST['chat'] ) ? sanitize_text_field( wp_unslash( $_POST['chat'] ) ) : '';

	if ( '' === $chat ) {
		wp_safe_redirect( scala_telegram_back_url( array( 'scala-tg' => 'err', 'scala-tg-msg' => rawurlencode( __( 'Порожній ID чату.', 'scala' ) ) ) ) );
		exit;
	}

	/*
	 * Пишемо весь масив, а не одне поле: без _scala_tab фільтр
	 * очищення проганяє всю схему, і поля, яких немає у вхідних
	 * даних, стали б порожніми.
	 */
	$options                  = get_option( SCALA_OPT_KEY, array() );
	$options                  = is_array( $options ) ? $options : array();
	$options['telegram_chat'] = $chat;

	update_option( SCALA_OPT_KEY, $options );

	// Сюди приходимо з admin-post, а кеш scala_options() уже прочитав старе.
	add_filter(
		'option_' . SCALA_OPT_KEY,
		static function ( $value ) use ( $chat ) {
			if ( is_array( $value ) ) {
				$value['telegram_chat'] = $chat;
			}

			return $value;
		}
	);

	scala_telegram_action_test();
}
add_action( 'admin_post_scala_telegram_use_chat', 'scala_telegram_action_use_chat' );

/**
 * Повідомлення після дій — угорі сторінки налаштувань.
 *
 * @return void
 */
function scala_telegram_notice(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- лише показ результату після редиректу.
	if ( ! isset( $_GET['page'], $_GET['scala-tg'] ) || 'scala-settings' !== $_GET['page'] ) {
		return;
	}

	$state = sanitize_key( wp_unslash( $_GET['scala-tg'] ) );
	$msg   = isset( $_GET['scala-tg-msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['scala-tg-msg'] ) ) ) : '';
	$n     = isset( $_GET['scala-tg-n'] ) ? (int) $_GET['scala-tg-n'] : 0;
	// phpcs:enable

	switch ( $state ) {
		case 'sent':
			$class = 'notice-success';
			$text  = __( 'Тестове повідомлення надіслано — перевірте канал.', 'scala' );
			break;

		case 'found':
			$class = $n ? 'notice-success' : 'notice-warning';
			$text  = $n
				/* translators: %d — кількість чатів */
				? sprintf( _n( 'Бот бачить %d чат — оберіть його нижче.', 'Бот бачить %d чати — оберіть потрібний нижче.', $n, 'scala' ), $n )
				: __( 'Бот ще ніде не бачив повідомлень. Додайте його адміністратором у канал, напишіть там що-небудь і натисніть «Знайти чат» ще раз.', 'scala' );
			break;

		default:
			$class = 'notice-error';
			$text  = __( 'Telegram: ', 'scala' ) . $msg;
	}

	printf( '<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr( $class ), esc_html( $text ) );
}
add_action( 'admin_notices', 'scala_telegram_notice' );

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

	$token  = scala_telegram_token();
	$chat   = scala_telegram_chat();
	$status = get_option( SCALA_TG_STATUS, array() );
	$status = is_array( $status ) ? $status : array();
	$found  = get_transient( SCALA_TG_CHATS );
	$found  = is_array( $found ) ? $found : array();
	?>
	<div class="scala-tgbox" id="scala-telegram">
		<h2 class="scala-tgbox__title"><?php esc_html_e( 'Заявки в Telegram', 'scala' ); ?></h2>

		<p class="scala-tgbox__status">
			<?php if ( '' === $token ) : ?>
				<?php esc_html_e( 'Не підключено. Впишіть токен бота в поле вище й натисніть «Зберегти».', 'scala' ); ?>
			<?php elseif ( '' === $chat ) : ?>
				<?php esc_html_e( 'Токен є, канал ще не обрано. Натисніть «Знайти чат» або впишіть ID вручну й збережіть.', 'scala' ); ?>
			<?php elseif ( empty( $status['time'] ) ) : ?>
				<?php esc_html_e( 'Підключено, але ще нічого не надсилали. Натисніть «Надіслати тест».', 'scala' ); ?>
			<?php else : ?>
				<?php
				$when = wp_date( 'd.m.Y H:i', (int) $status['time'] );

				if ( ! empty( $status['ok'] ) ) {
					/* translators: 1 — що надсилали (заявка/тест), 2 — дата й час */
					echo esc_html( sprintf( __( 'Працює. Останнє надсилання (%1$s): %2$s.', 'scala' ), (string) $status['what'], $when ) );
				} else {
					/* translators: 1 — дата й час, 2 — текст помилки */
					echo esc_html( sprintf( __( 'Помилка при останньому надсиланні (%1$s): %2$s', 'scala' ), $when, (string) $status['error'] ) );
				}
				?>
			<?php endif; ?>
		</p>

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

		<details class="scala-tgbox__howto">
			<summary><?php esc_html_e( 'Як підключити', 'scala' ); ?></summary>
			<ol>
				<li><?php esc_html_e( 'У Telegram відкрийте @BotFather, надішліть /newbot і дайте боту назву. У відповідь прийде токен — довгий рядок з двокрапкою всередині.', 'scala' ); ?></li>
				<li><?php esc_html_e( 'Вставте токен у поле «Telegram: токен бота» вище й натисніть «Зберегти».', 'scala' ); ?></li>
				<li><?php esc_html_e( 'Створіть канал або групу для заявок і додайте туди бота як адміністратора з правом публікувати повідомлення.', 'scala' ); ?></li>
				<li><?php esc_html_e( 'Натисніть «Знайти чат» і біля потрібного каналу — «Використати». Тестове повідомлення прийде одразу.', 'scala' ); ?></li>
			</ol>
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
