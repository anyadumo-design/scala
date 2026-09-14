<?php
/**
 * Екран «Переїзд»: прибирання російської версії в один клік.
 *
 * Сайт лишається лише українською. Російські сторінки й статті треба
 * прибрати — але шукати їх руками в списку з тридцяти позицій легко
 * помилитись і зачепити не те. Тому список складено заздалегідь,
 * зі знятої структури чинного сайту, і кнопка чіпає рівно його.
 *
 * Матеріали йдуть у кошик, а не видаляються: якщо щось піде не так,
 * вони повертаються звідти за секунду.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Слаги російських матеріалів чинного сайту.
 *
 * @return array Ключ — тип запису, значення — список слагів.
 */
function scala_ru_slugs(): array {
	return array(
		'page' => array(
			'yskusstvo-tkany',
			'galereya-2',
			'pro-brend-2',
			'sotrudnychestvo-dlya-dyzajnerov',
			'poshyv-y-montazh-pod-klyuch',
			'yndyvydualnyj-podbor-tkany',
			'vyezd-dyzajnera',
			'kontakty-2',
		),
		'post' => array(
			'avstryjskye-shtory',
			'yaponskye-shtory-mynymalyzm-styl-y-funkczyonalnost',
			'rulonnye-shtory',
			'elektrokarnyzy-sovremennoe-reshenye-dlya-upravlenyya-shtoramy',
			'kak-vybrat-ydealnye-tkany-dlya-shtor',
			'kak-pravylno-vybrat-karnyz',
			'novost-4',
		),
	);
}

/**
 * Старі українські сторінки й записи, які нова структура замінила.
 *
 * Поки вони існують, старі адреси віддають старий текст замість
 * редіректу на новий: редірект за задумом спрацьовує лише на 404.
 * pro-brend і kontakty сюди НЕ входять — їх нова тема використовує.
 *
 * @return array
 */
function scala_superseded_slugs(): array {
	return array(
		'page' => array(
			'golovna',        // стара головна; нова — holovna
			'galereya',       // → katalog
			'vyyizd-dyzajnera',
			'indyvidualnyj-pidbir-tkanyny',
			'poshyttya-ta-montazh-pid-klyuch',
			'spivpraczya-dlya-dyzajneriv',
			'sample-page',
		),
		'post' => array(
			'avstrijski-shtory', // → vydy-shtor/avstrijski-shtory
			'yaponski-shtory',   // → vydy-shtor/yaponski-paneli
			'rulonni-shtory',    // → vydy-shtor/rulonni-shtory
		),
	);
}

/**
 * Знаходить матеріали зі списку, які ще не в кошику.
 *
 * @param array $lists Ключ — тип запису, значення — список слагів.
 * @return array Список WP_Post.
 */
function scala_find_listed_content( array $lists ): array {
	$found = array();

	foreach ( $lists as $type => $slugs ) {
		foreach ( $slugs as $slug ) {
			$post = get_page_by_path( $slug, OBJECT, $type );

			if ( $post && 'trash' !== $post->post_status ) {
				$found[] = $post;
			}
		}
	}

	return $found;
}

/**
 * Російські матеріали, які ще не в кошику.
 *
 * @return array Список WP_Post.
 */
function scala_find_ru_content(): array {
	return scala_find_listed_content( scala_ru_slugs() );
}

/**
 * Старі українські матеріали, які ще не в кошику.
 *
 * @return array Список WP_Post.
 */
function scala_find_superseded_content(): array {
	return scala_find_listed_content( scala_superseded_slugs() );
}


/**
 * Пункт меню.
 *
 * @return void
 */
function scala_migrate_menu(): void {
	add_submenu_page(
		'scala-settings',
		__( 'Переїзд зі старого сайту', 'scala' ),
		__( 'Переїзд', 'scala' ),
		'edit_theme_options',
		'scala-migrate',
		'scala_render_migrate_page'
	);
}
add_action( 'admin_menu', 'scala_migrate_menu', 11 );

/**
 * Обробка натискання кнопки.
 *
 * @return void
 */
function scala_handle_migrate(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'scala' ) );
	}

	check_admin_referer( 'scala_migrate' );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- нонс перевірено вище.
	$which = isset( $_POST['which'] ) ? sanitize_key( wp_unslash( $_POST['which'] ) ) : 'ru';
	$items = 'old' === $which ? scala_find_superseded_content() : scala_find_ru_content();
	$moved = 0;

	foreach ( $items as $post ) {
		if ( wp_trash_post( $post->ID ) ) {
			++$moved;
		}
	}

	// Нова головна вже стоїть, але після кошика WordPress іноді скидає
	// вибір — перевіримо і повернемо.
	$home = get_page_by_path( 'holovna' );
	if ( $home && (int) get_option( 'page_on_front' ) !== (int) $home->ID ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home->ID );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'        => 'scala-migrate',
				'scala_moved' => $moved,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_post_scala_migrate', 'scala_handle_migrate' );

/**
 * Перелік перевірок стану сайту після активації теми.
 *
 * @return array Список array( 'ok' => bool, 'text' => string ).
 */
function scala_migrate_checks(): array {
	$front = (int) get_option( 'page_on_front' );
	$home  = get_page_by_path( 'holovna' );

	$checks = array(
		array(
			'ok'   => 'page' === get_option( 'show_on_front' ) && $home && $front === (int) $home->ID,
			'text' => __( 'Головною стоїть сторінка «Головна»', 'scala' ),
		),
		array(
			'ok'   => (bool) scala_posts( 'scala_type', 1 ),
			'text' => __( 'Види штор створені', 'scala' ),
		),
		array(
			'ok'   => (bool) scala_posts( 'scala_fabric', 1 ),
			'text' => __( 'Тканини створені', 'scala' ),
		),
		array(
			'ok'   => ! scala_plugin_active( 'polylang/polylang.php' ),
			'text' => __( 'Polylang вимкнено', 'scala' ),
		),
		array(
			'ok'   => ! scala_find_ru_content(),
			'text' => __( 'Російські сторінки прибрані', 'scala' ),
		),
		array(
			'ok'   => '' !== (string) scala_opt( 'notify_email', '' ),
			'text' => __( 'Вказано пошту для заявок', 'scala' ),
		),
		array(
			'ok'   => '' !== (string) scala_opt( 'address', '' ),
			'text' => __( 'Вказано адресу шоуруму', 'scala' ),
		),
	);

	return $checks;
}

/**
 * Чи активний плагін.
 *
 * Своя обгортка, бо is_plugin_active() із ядра доступна не на кожному
 * екрані адмінки, а без префікса ім'я легко зіткнеться з чужим.
 *
 * @param string $file Шлях до файлу плагіна.
 * @return bool
 */
function scala_plugin_active( string $file ): bool {
	$active = (array) get_option( 'active_plugins', array() );

	return in_array( $file, $active, true );
}

/**
 * Сам екран.
 *
 * @return void
 */
function scala_render_migrate_page(): void {
	$found = scala_find_ru_content();
	$moved = isset( $_GET['scala_moved'] ) ? (int) $_GET['scala_moved'] : -1; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap scala-settings">
		<h1><?php esc_html_e( 'Переїзд зі старого сайту', 'scala' ); ?></h1>

		<?php
		// Наповнення йде першим: поки його немає, решта кроків безпредметна.
		scala_render_setup_block();
		?>

		<hr style="margin:32px 0" />

		<?php if ( $moved > 0 ) : ?>
			<div class="notice notice-success"><p>
				<?php
				printf(
					/* translators: %d — кількість матеріалів */
					esc_html__( 'У кошик переміщено матеріалів: %d. Якщо щось зайве — поверніть із кошика.', 'scala' ),
					(int) $moved
				);
				?>
			</p></div>
		<?php elseif ( 0 === $moved ) : ?>
			<div class="notice notice-info"><p>
				<?php esc_html_e( 'Прибирати нічого: російських матеріалів не знайдено.', 'scala' ); ?>
			</p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Російська версія', 'scala' ); ?></h2>

		<?php if ( $found ) : ?>
			<p>
				<?php esc_html_e( 'Знайдено матеріали російської версії. Вони підуть у кошик — не будуть видалені назовсім, тож повернути можна будь-якої миті. Усі їхні адреси вже ведуть 301-м на українські відповідники.', 'scala' ); ?>
			</p>

			<table class="widefat striped" style="max-width:760px">
				<thead><tr>
					<th><?php esc_html_e( 'Назва', 'scala' ); ?></th>
					<th style="width:120px"><?php esc_html_e( 'Тип', 'scala' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $found as $post ) : ?>
					<tr>
						<td><?php echo esc_html( get_the_title( $post ) ); ?></td>
						<td><?php echo 'page' === $post->post_type ? esc_html__( 'Сторінка', 'scala' ) : esc_html__( 'Запис', 'scala' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:18px">
				<input type="hidden" name="action" value="scala_migrate" />
				<input type="hidden" name="which" value="ru" />
				<?php wp_nonce_field( 'scala_migrate' ); ?>
				<button type="submit" class="button button-primary">
					<?php
					printf(
						/* translators: %d — кількість матеріалів */
						esc_html__( 'Прибрати російську версію (%d)', 'scala' ),
						count( $found )
					);
					?>
				</button>
			</form>
		<?php else : ?>
			<p><?php esc_html_e( 'Російських матеріалів немає — або їх уже прибрано.', 'scala' ); ?></p>
		<?php endif; ?>

		<?php $scala_old = scala_find_superseded_content(); ?>
		<h2 style="margin-top:36px"><?php esc_html_e( 'Старі сторінки, які замінено', 'scala' ); ?></h2>

		<?php if ( $scala_old ) : ?>
			<p>
				<?php esc_html_e( 'Ці сторінки й записи старого сайту нова структура замінила: галерею — каталог, послуги — сторінка бренду, три «статті» про види штор — окремі сторінки видів. Поки вони існують, старі адреси показують старий текст замість того, щоб вести на новий. У кошик, не назовсім.', 'scala' ); ?>
			</p>

			<table class="widefat striped" style="max-width:760px">
				<thead><tr>
					<th><?php esc_html_e( 'Назва', 'scala' ); ?></th>
					<th style="width:120px"><?php esc_html_e( 'Тип', 'scala' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $scala_old as $post ) : ?>
					<tr>
						<td><?php echo esc_html( get_the_title( $post ) ); ?></td>
						<td><?php echo 'page' === $post->post_type ? esc_html__( 'Сторінка', 'scala' ) : esc_html__( 'Запис', 'scala' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:18px">
				<input type="hidden" name="action" value="scala_migrate" />
				<input type="hidden" name="which" value="old" />
				<?php wp_nonce_field( 'scala_migrate' ); ?>
				<button type="submit" class="button button-primary">
					<?php
					printf(
						/* translators: %d — кількість матеріалів */
						esc_html__( 'Прибрати старі сторінки (%d)', 'scala' ),
						count( $scala_old )
					);
					?>
				</button>
			</form>
		<?php else : ?>
			<p><?php esc_html_e( 'Старих сторінок не лишилось — усі адреси ведуть на нові.', 'scala' ); ?></p>
		<?php endif; ?>

		<h2 style="margin-top:36px"><?php esc_html_e( 'Стан сайту', 'scala' ); ?></h2>

		<table class="widefat striped" style="max-width:760px">
			<tbody>
			<?php foreach ( scala_migrate_checks() as $check ) : ?>
				<tr>
					<td style="width:34px;font-size:16px">
						<?php echo $check['ok'] ? '<span style="color:#1a7f37">&#10003;</span>' : '<span style="color:#b3261e">&#10007;</span>'; ?>
					</td>
					<td><?php echo esc_html( $check['text'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
