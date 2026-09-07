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

	// Яку саме вкладку зберігають. Nonce перевіряє Settings API до цього моменту.
	$tab = isset( $_POST['_scala_tab'] ) ? sanitize_key( wp_unslash( $_POST['_scala_tab'] ) ) : '';

	$schema = scala_options_schema();

	if ( ! isset( $schema[ $tab ] ) ) {
		// Невідома вкладка — нічого не міняємо, ніж зіпсувати збережене.
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

		<form method="post" action="options.php" class="scala-settings__form">
			<?php
			settings_fields( 'scala_settings_group' );
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
	</div>
	<?php
}
