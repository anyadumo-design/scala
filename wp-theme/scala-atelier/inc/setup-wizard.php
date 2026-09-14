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
	// Завжди свіжий індекс: викликається до і після порції, і між ними
	// щось залилося.
	return count( array_filter( scala_bundled_index( true ) ) );
}

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
		$before = $state['images_done'];

		scala_import_bundled_images( SCALA_BATCH, SCALA_SECONDS );

		$state          = scala_setup_state();
		$state['stage'] = __( 'Переносимо фотографії в медіатеку', 'scala' );

		/*
		 * Запобіжник. Одного разу перевірка «чи вже залито» не знаходила
		 * щойно залите, і ті самі фото заливались по колу, поки хтось
		 * не закрив вкладку. Якщо після порції лічильник не зрушив —
		 * зупиняємось і кажемо про це, а не крутимось далі.
		 */
		if ( $state['images_done'] <= $before ) {
			$state['stuck']    = true;
			$state['finished'] = true;
			$state['stage']    = __( 'Заливання не просувається — зупинено', 'scala' );
		}

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

	<?php $scala_dupes = scala_count_duplicate_images(); ?>
	<?php if ( $scala_dupes ) : ?>
		<div class="notice notice-warning inline"><p>
			<?php
			printf(
				/* translators: %d — кількість зайвих файлів */
				esc_html__( 'У медіатеці %d зайвих копій фото з комплекту — наслідок помилки в заливанні. Їх можна прибрати: залишиться по одному файлу на кожне фото.', 'scala' ),
				(int) $scala_dupes
			);
			?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="scala_clean_dupes" />
			<?php wp_nonce_field( 'scala_clean_dupes' ); ?>
			<button type="submit" class="button">
				<?php
				printf(
					/* translators: %d — кількість зайвих файлів */
					esc_html__( 'Прибрати зайві копії (%d)', 'scala' ),
					(int) $scala_dupes
				);
				?>
			</button>
		</form>
		</div>
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

						if ( d.stuck ) {
							out.textContent = 'Заливання не просувається — зупинив, щоб не плодити копій. Напишіть про це.';
							btn.disabled = false;
							return;
						}

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

/**
 * Скільки зайвих копій фото з комплекту лежить у медіатеці.
 *
 * @return int
 */
function scala_count_duplicate_images(): int {
	$extra = 0;

	foreach ( scala_bundled_index( true ) as $ids ) {
		if ( count( $ids ) > 1 ) {
			$extra += count( $ids ) - 1;
		}
	}

	return $extra;
}

/**
 * Прибирає зайві копії за кнопкою.
 *
 * @return void
 */
function scala_handle_clean_dupes(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'scala' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( 'scala_clean_dupes' );

	$removed = scala_delete_duplicate_images();

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'          => 'scala-migrate',
				'scala_cleaned' => $removed,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_post_scala_clean_dupes', 'scala_handle_clean_dupes' );
