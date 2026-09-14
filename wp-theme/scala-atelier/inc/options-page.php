<?php
/**
 * Сторінка налаштувань теми (меню SCALA).
 *
 * Усі вкладки лежать в одній опції. Під час збереження вкладки решта
 * значень зливається зі збереженими — інакше збереження однієї вкладки
 * стирало б решту.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Пункт меню верхнього рівня + сторінка налаштувань.
 *
 * Права — edit_theme_options. Це правильний рівень для налаштувань
 * теми, і саме його адміністратор має.
 *
 * Чому не manage_options, як зазвичай роблять: на сайті клієнта
 * з ролі «Адміністратор» знято рівно одне право — manage_options.
 * Решта на місці. Тому сторінка, яка вимагає manage_options, там
 * не відкривається взагалі.
 *
 * Наслідок: зберігати через options.php не можна — той жорстко
 * вимагає manage_options. Тому форма йде на admin-post.php,
 * а перевірку прав робимо самі. Див. scala_save_options().
 *
 * Це не милиця під один сайт: налаштування теми й мають вимагати
 * права на тему, а не права на весь WordPress.
 *
 * @return void
 */
function scala_admin_menu(): void {
	add_menu_page(
		__( 'SCALA — налаштування сайту', 'scala' ),
		'SCALA',
		'edit_theme_options',
		'scala-settings',
		'scala_render_options_page',
		'dashicons-admin-customizer',
		3
	);

	add_submenu_page(
		'scala-settings',
		__( 'Налаштування сайту', 'scala' ),
		__( 'Налаштування', 'scala' ),
		'edit_theme_options',
		'scala-settings',
		'scala_render_options_page'
	);
}
add_action( 'admin_menu', 'scala_admin_menu', 9 );

/**
 * Реєстрація опції.
 *
 * @return void
 */
function scala_register_settings(): void {
	register_setting(
		'scala_settings_group',
		SCALA_OPT_KEY,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'scala_sanitize_options',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'scala_register_settings' );

/**
 * Зберігає налаштування.
 *
 * Власний обробник замість options.php: той жорстко вимагає
 * manage_options, а налаштування теми мають вимагати права на тему.
 * На сайті клієнта це не теорія — там manage_options знято з ролі
 * адміністратора, і через options.php зберегти було б неможливо.
 *
 * @return void
 */
function scala_save_options(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'scala' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( 'scala_save_options' );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- чистить scala_sanitize_options за схемою.
	$raw = isset( $_POST[ SCALA_OPT_KEY ] ) ? wp_unslash( $_POST[ SCALA_OPT_KEY ] ) : array();

	/*
	 * Передаємо сирі дані: register_setting вішає фільтр
	 * sanitize_option_*, і update_option чистить сам, тією ж
	 * scala_sanitize_options. Чистити ще й тут — робити це двічі.
	 */
	update_option( SCALA_OPT_KEY, $raw );

	$tab = isset( $_POST['_scala_tab'] ) ? sanitize_key( wp_unslash( $_POST['_scala_tab'] ) ) : '';

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'          => 'scala-settings',
				'tab'           => $tab,
				'scala-updated' => 1,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_post_scala_save_options', 'scala_save_options' );

/**
 * Активна вкладка з адреси.
 *
 * @return string
 */
function scala_current_tab(): string {
	$schema = scala_options_schema();
	$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

	return isset( $schema[ $tab ] ) ? $tab : array_key_first( $schema );
}

/**
 * Чистить надіслані значення й зливає їх зі збереженими.
 *
 * @param mixed $input Сирі дані форми.
 * @return array
 */
function scala_sanitize_options( $input ): array {
	$saved = get_option( SCALA_OPT_KEY, array() );
	$saved = is_array( $saved ) ? $saved : array();

	// Яку саме вкладку зберігають. Нонс перевірено в scala_save_options().
	$tab = isset( $_POST['_scala_tab'] ) ? sanitize_key( wp_unslash( $_POST['_scala_tab'] ) ) : '';

	$schema = scala_options_schema();

	/*
	 * Без вкладки — це не форма, а програмний запис: наповнення при
	 * переїзді або інший код. Раніше тут стояло «повернути збережене»,
	 * і майстер наповнення записував тексти й фото головної в нікуди:
	 * hero порожній, кнопки без підписів, секції без назв. Чистимо
	 * весь масив по всій схемі й пишемо.
	 */
	if ( '' === $tab ) {
		$all = array();

		foreach ( $schema as $one ) {
			$all = array_merge( $all, (array) $one['fields'] );
		}

		return array_merge( $saved, scala_sanitize_fields( $all, $input ) );
	}

	if ( ! isset( $schema[ $tab ] ) ) {
		// Невідома вкладка з форми — нічого не міняємо, ніж зіпсувати збережене.
		return $saved;
	}

	$clean = scala_sanitize_fields( scala_tab_fields( $tab ), $input );

	return array_merge( $saved, $clean );
}

/**
 * Малює сторінку налаштувань.
 *
 * @return void
 */
function scala_render_options_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$schema  = scala_options_schema();
	$current = scala_current_tab();
	$values  = get_option( SCALA_OPT_KEY, array() );
	$values  = is_array( $values ) ? $values : array();
	?>
	<div class="wrap scala-settings">
		<h1><?php esc_html_e( 'Налаштування сайту SCALA', 'scala' ); ?></h1>

		<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- лише показ повідомлення. ?>
		<?php if ( isset( $_GET['scala-updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php esc_html_e( 'Збережено.', 'scala' ); ?>
			</p></div>
		<?php endif; ?>
		<p class="scala-settings__intro">
			<?php esc_html_e( 'Тексти й фото головної сторінки. Списки, що ростуть — проєкти, види штор, тканини, каталог, відгуки, питання та Instagram — редагуються в окремих розділах меню SCALA.', 'scala' ); ?>
		</p>

		<nav class="nav-tab-wrapper scala-settings__tabs">
			<?php foreach ( $schema as $tab_key => $tab ) : ?>
				<a
					href="<?php echo esc_url( admin_url( 'admin.php?page=scala-settings&tab=' . $tab_key ) ); ?>"
					class="nav-tab<?php echo $tab_key === $current ? ' nav-tab-active' : ''; ?>">
					<?php echo esc_html( $tab['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="scala-settings__form">
			<?php
			wp_nonce_field( 'scala_save_options' );
			printf( '<input type="hidden" name="action" value="scala_save_options">' );
			printf( '<input type="hidden" name="_scala_tab" value="%s">', esc_attr( $current ) );

			echo '<div class="scala-settings__body">';
			scala_render_fields(
				scala_tab_fields( $current ),
				$values,
				SCALA_OPT_KEY
			);
			echo '</div>';

			submit_button( __( 'Зберегти', 'scala' ) );
			?>
		</form>

		<?php
		/**
		 * Блоки під формою вкладки: перевірка Telegram тощо.
		 *
		 * @param string $current Ключ вкладки.
		 */
		do_action( 'scala_options_after_form', $current );
		?>
	</div>
	<?php
}
