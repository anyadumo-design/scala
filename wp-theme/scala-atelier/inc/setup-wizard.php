<?php
/**
 * Покрокове наповнення сайту.
 *
 * Чому не при активації: у комплекті теми 53 фотографії, і WordPress
 * робить із кожної девʼять розмірів. Це майже пʼятсот операцій зміни
 * розміру — в один запит вони не влазять, PHP обривається за 30 секунд,
 * і активація падає з критичною помилкою.
 *
 * Тому фото заливаються порціями по кілька штук за запит, а браузер
 * повторює запити, доки не закінчаться. Кожен окремий запит триває
 * менше секунди, і хостингу байдуже, скільки їх було.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/** Скільки фото заливати за один запит — і скільки секунд на це давати. */
const SCALA_BATCH   = 4;
const SCALA_SECONDS = 8.0;

/**
 * Імена всіх фото з комплекту (без половинних версій).
 *
 * @return array
 */
function scala_bundled_names(): array {
	$files = glob( SCALA_DIR . '/assets/img/*.webp' );

	if ( ! $files ) {
		return array();
	}

	$names = array();

	foreach ( $files as $file ) {
		$name = basename( $file, '.webp' );

		if ( ! str_contains( $name, '@0.5x' ) ) {
			$names[] = $name;
		}
	}

	return $names;
}

/**
 * Скільки фото вже в медіатеці.
 *
 * Одним запитом замість 53 окремих: на екрані налаштування це
 * викликається часто.
 *
 * @return int
 */
function scala_images_done(): int {
	global $wpdb;

	$names = scala_bundled_names();

	if ( ! $names ) {
		return 0;
	}

	$slugs = array_map( 'sanitize_title', $names );
	$holes = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- плейсхолдери зібрані вище.
	$sql = $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts}
		 WHERE post_type = 'attachment' AND post_name IN ( $holes )",
		$slugs
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- разовий підрахунок на екрані налаштування.
	return (int) $wpdb->get_var( $sql );
}

/**
 * Знімає брехливий прапорець «наповнено».
 *
 * Перша активація впала посеред заливання фото, але прапорець на той
 * час уже стояв — він писався на початку, а не в кінці. Код я виправив,
 * а значення лишилось у базі, і воно назавжди блокує наповнення.
 *
 * Ознака брехні однозначна: прапорець стоїть, а видів штор нема жодного.
 * Такого стану після справжнього наповнення не буває.
 *
 * @return void
 */
function scala_heal_seeded_flag(): void {
	if ( ! get_option( 'scala_seeded' ) ) {
		return;
	}

	$types = wp_count_posts( 'scala_type' );

	if ( isset( $types->publish ) && 0 === (int) $types->publish ) {
		delete_option( 'scala_seeded' );
		update_option( 'scala_setup_needed', 1 );
	}
}
add_action( 'admin_init', 'scala_heal_seeded_flag' );

/**
 * Записує стан меню адмінки, щоб його можна було прочитати ззовні.
 *
 * Пункти меню не зʼявляються, хоч функції визначені й гачки навішені.
 * Отже, щось прибирає їх уже після реєстрації. З адмінки я цього не бачу,
 * тому тема сама занотовує, що сталося з її меню, а діагностика на
 * фронті це показує.
 *
 * ПРИБРАТИ разом із inc/diagnose.php.
 *
 * @return void
 */
function scala_record_menu_state(): void {
	global $submenu, $menu;

	$mine = array();

	foreach ( (array) $menu as $item ) {
		if ( isset( $item[2] ) && 'scala-settings' === $item[2] ) {
			$mine[] = 'top:' . $item[1];
		}
	}

	foreach ( (array) ( $submenu['scala-settings'] ?? array() ) as $item ) {
		$mine[] = $item[2] . '(' . $item[1] . ')';
	}

	/*
	 * Перелік прав, а не одне: рівно тут ми двічі вгадали неправильно.
	 * Хай сайт сам скаже, що в нього є, і тоді сторінки теми вимагатимуть
	 * саме того права, яке на цьому сайті справді працює.
	 */
	$caps = array(
		'manage_options',
		'edit_theme_options',
		'switch_themes',
		'install_themes',
		'edit_pages',
		'publish_pages',
		'list_users',
		'activate_plugins',
		'upload_files',
		'edit_posts',
	);

	$have = array();

	foreach ( $caps as $cap ) {
		$have[] = $cap . ( current_user_can( $cap ) ? '+' : '−' );
	}

	$user = wp_get_current_user();

	update_option(
		'scala_menu_debug',
		array(
			'user'  => $user->user_login,
			'roles' => implode( ',', (array) $user->roles ),
			'caps'  => implode( ' ', $have ),
			'items' => $mine ? implode( ' | ', $mine ) : 'ПОРОЖНЬО',
			'when'  => gmdate( 'H:i:s' ),
		)
	);
}
add_action( 'admin_menu', 'scala_record_menu_state', 9999 );

/**
 * Стан наповнення для екрана й для AJAX.
 *
 * @return array
 */
function scala_setup_state(): array {
	$total = count( scala_bundled_names() );
	$done  = scala_images_done();

	return array(
		'images_total' => $total,
		'images_done'  => $done,
		'content_done' => (bool) get_option( 'scala_seeded' ),
		'finished'     => $done >= $total && get_option( 'scala_seeded' ),
	);
}

/**
 * Один крок наповнення.
 *
 * @return void
 */
function scala_setup_step(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( __( 'Недостатньо прав.', 'scala' ), 403 );
	}

	check_ajax_referer( 'scala_setup' );

	$state = scala_setup_state();

	// Спершу фото — порціями.
	if ( $state['images_done'] < $state['images_total'] ) {
		scala_import_bundled_images( SCALA_BATCH, SCALA_SECONDS );

		$state          = scala_setup_state();
		$state['stage'] = __( 'Переносимо фотографії в медіатеку', 'scala' );

		wp_send_json_success( $state );
	}

	// Фото на місці — створюємо контент. Це робота з базою, вона швидка.
	if ( ! $state['content_done'] ) {
		scala_seed_content();

		$state          = scala_setup_state();
		$state['stage'] = __( 'Створюємо сторінки й розділи', 'scala' );

		wp_send_json_success( $state );
	}

	$state['stage'] = __( 'Готово', 'scala' );

	wp_send_json_success( $state );
}
add_action( 'wp_ajax_scala_setup_step', 'scala_setup_step' );

/**
 * Нагадування в адмінці, поки сайт не наповнено.
 *
 * @return void
 */
function scala_setup_notice(): void {
	if ( ! get_option( 'scala_setup_needed' ) || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( $screen && 'scala_page_scala-migrate' === $screen->id ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>%s</strong> %s <a class="button button-primary" href="%s">%s</a></p></div>',
		esc_html__( 'SCALA:', 'scala' ),
		esc_html__( 'тему активовано, але сайт ще не наповнено.', 'scala' ),
		esc_url( admin_url( 'admin.php?page=scala-migrate' ) ),
		esc_html__( 'Наповнити сайт', 'scala' )
	);
}
add_action( 'admin_notices', 'scala_setup_notice' );

/**
 * Блок наповнення на екрані «Переїзд».
 *
 * @return void
 */
function scala_render_setup_block(): void {
	$state = scala_setup_state();
	?>
	<h2><?php esc_html_e( 'Наповнення сайту', 'scala' ); ?></h2>

	<?php $scala_err = get_option( 'scala_setup_error' ); ?>
	<?php if ( $scala_err ) : ?>
		<div class="notice notice-warning inline"><p>
			<strong><?php esc_html_e( 'Останнє попередження:', 'scala' ); ?></strong>
			<?php echo esc_html( $scala_err ); ?>
		</p></div>
	<?php endif; ?>

	<?php if ( $state['finished'] ) : ?>
		<p>
			<?php
			printf(
				/* translators: %d — кількість фотографій */
				esc_html__( 'Сайт наповнено: %d фотографій у медіатеці, розділи створені.', 'scala' ),
				(int) $state['images_done']
			);
			?>
		</p>
	<?php else : ?>
		<p>
			<?php esc_html_e( 'Тема принесла з собою фотографії, види штор, тканини, проєкти, питання й тексти сторінок. Натисніть кнопку — і вони зʼявляться на сайті. Фото заливаються порціями, тож не закривайте вкладку, доки не дійде до кінця.', 'scala' ); ?>
		</p>

		<p id="scala-setup-progress" style="font-size:15px">
			<?php
			printf(
				esc_html__( 'Фотографій: %1$d з %2$d', 'scala' ),
				(int) $state['images_done'],
				(int) $state['images_total']
			);
			?>
		</p>

		<p>
			<button type="button" class="button button-primary" id="scala-setup-start">
				<?php esc_html_e( 'Наповнити сайт', 'scala' ); ?>
			</button>
		</p>

		<script>
		( function () {
			var btn  = document.getElementById( 'scala-setup-start' );
			var out  = document.getElementById( 'scala-setup-progress' );
			var url  = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			var nonce = <?php echo wp_json_encode( wp_create_nonce( 'scala_setup' ) ); ?>;

			if ( ! btn ) { return; }

			function step() {
				var body = new FormData();
				body.append( 'action', 'scala_setup_step' );
				body.append( '_wpnonce', nonce );

				fetch( url, { method: 'POST', body: body, credentials: 'same-origin' } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						if ( ! res || ! res.success ) {
							out.textContent = 'Щось пішло не так. Оновіть сторінку й натисніть ще раз — почнеться з того місця, де спинилось.';
							btn.disabled = false;
							return;
						}

						var d = res.data;
						out.textContent = d.stage + ' — ' + d.images_done + ' з ' + d.images_total;

						if ( d.finished ) {
							out.textContent = 'Готово. Сайт наповнено.';
							btn.disabled = false;
							setTimeout( function () { location.reload(); }, 900 );
							return;
						}

						step();
					} )
					.catch( function () {
						out.textContent = 'Звʼязок обірвався. Натисніть кнопку ще раз — продовжить з того ж місця.';
						btn.disabled = false;
					} );
			}

			btn.addEventListener( 'click', function () {
				btn.disabled = true;
				out.textContent = 'Починаємо…';
				step();
			} );
		} )();
		</script>
	<?php endif; ?>
	<?php
}
