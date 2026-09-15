<?php
/**
 * Додавання підготовлених матеріалів у блог.
 *
 * Кнопка в налаштуваннях створює записи з заготовок у seed-guides.php.
 * Створює чернетками: текст зʼявиться на сайті лише після того, як
 * його прочитають і натиснуть «Опублікувати». Запис, який уже є,
 * не чіпається — кнопку можна натискати скільки завгодно разів.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Підставляє справжні адреси замість міток у тексті.
 *
 * У заготовці посилання записані мітками {{type:slug}}, {{archive}},
 * {{page:catalog}}. Інакше довелося б зашивати в текст домен і слаги,
 * а вони можуть змінитися. Якщо сторінки, на яку веде мітка, немає,
 * посилання прибирається, а текст лишається — щоб не з\'явилось
 * посилання в нікуди.
 *
 * @param string $content Текст із мітками.
 * @return string
 */
function scala_expand_guide_links( string $content ): string {
	return (string) preg_replace_callback(
		'~<a href="\{\{([a-z]+)(?::([a-z0-9-]+))?\}\}">(.*?)</a>~u',
		static function ( array $m ): string {
			$kind = $m[1];
			$key  = $m[2] ?? '';
			$text = $m[3];
			$url  = '';

			if ( 'type' === $kind ) {
				$found = get_posts(
					array(
						'name'           => $key,
						'post_type'      => 'scala_type',
						'post_status'    => 'publish',
						'posts_per_page' => 1,
						'fields'         => 'ids',
					)
				);

				$url = $found ? (string) get_permalink( $found[0] ) : '';
			} elseif ( 'archive' === $kind ) {
				$url = (string) get_post_type_archive_link( 'scala_type' );
			} elseif ( 'page' === $kind ) {
				$url = (string) scala_page_url( $key );
			}

			return $url ? sprintf( '<a href="%s">%s</a>', esc_url( $url ), $text ) : $text;
		},
		$content
	);
}

/**
 * Тексти, які тема вставляла раніше.
 *
 * Перші записи додавалися ще без позначки про походження, тож інакше
 * їх не відрізнити від тих, які редагувала людина. Хеш тут означає
 * «це рівно той текст, який ми колись вставили, і його не чіпали».
 *
 * @return array
 */
function scala_guide_known_hashes(): array {
	return array(
		// yaki-shtory-obraty, перша версія — ще без посилань у тексті.
		'b919da22c969ad75cd0f85a3f90962f0',
	);
}

/**
 * Створює ті матеріали, яких ще немає, і оновлює нерушені.
 *
 * Якщо запис уже є, але його жодного разу не редагували — текст
 * оновлюється. Слід редагування (текст відрізняється від того, що
 * тема вставила) означає «не чіпати»: правка людини важливіша.
 *
 * @return array Ключі added, updated і skipped зі списками назв.
 */
function scala_insert_missing_guides(): array {
	$added   = array();
	$updated = array();
	$skipped = array();

	foreach ( scala_guide_seed_data() as $guide ) {
		$slug = sanitize_title( (string) ( $guide['slug'] ?? '' ) );

		if ( '' === $slug ) {
			continue;
		}

		// Шукаємо в будь-якому статусі: чернетка чи кошик — теж «уже є».
		$exists = get_posts(
			array(
				'name'             => $slug,
				'post_type'        => 'post',
				// 'any' не бачить кошика, а викинутий запис — теж «уже є».
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// ID потрібен, щоб порівняти текст і оновити нерушений.

			)
		);

		$content = scala_expand_guide_links( (string) ( $guide['content'] ?? '' ) );
		$title   = (string) ( $guide['title'] ?? $slug );

		if ( $exists ) {
			$post_id = (int) $exists[0];
			$stored  = (string) get_post_meta( $post_id, '_scala_guide_hash', true );
			$current = md5( (string) get_post_field( 'post_content', $post_id ) );
			$known   = in_array( $current, scala_guide_known_hashes(), true );

			// Текст редагували — лишаємо як є: правка людини важливіша.
			if ( $current !== $stored && ! $known ) {
				$skipped[] = $title;
				continue;
			}

			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $content,
					'post_excerpt' => (string) ( $guide['excerpt'] ?? '' ),
				)
			);

			update_post_meta( $post_id, '_scala_guide_hash', md5( $content ) );
			$updated[] = $title;
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_excerpt' => (string) ( $guide['excerpt'] ?? '' ),
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			continue;
		}

		update_post_meta( (int) $post_id, '_scala_guide_hash', md5( $content ) );
		$added[] = $title;
	}

	return compact( 'added', 'updated', 'skipped' );
}

/**
 * Посилання на матеріал, якщо він опублікований.
 *
 * Поки запис лежить чернеткою, повертає null — і шаблон не малює
 * нічого. Так посилання не веде в нікуди до того, як текст прочитали
 * й випустили на сайт.
 *
 * @param string $slug Слаг матеріалу.
 * @return array|null Ключі url і title.
 */
function scala_guide_link( string $slug = 'yaki-shtory-obraty' ): ?array {
	static $cache = array();

	if ( array_key_exists( $slug, $cache ) ) {
		return $cache[ $slug ];
	}

	$found = get_posts(
		array(
			'name'             => $slug,
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'suppress_filters' => false,
		)
	);

	$cache[ $slug ] = $found
		? array(
			'url'   => (string) get_permalink( $found[0] ),
			'title' => get_the_title( $found[0] ),
		)
		: null;

	return $cache[ $slug ];
}

/**
 * Обробник кнопки.
 *
 * @return void
 */
function scala_guides_action(): void {
	if ( ! current_user_can( 'publish_posts' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'scala' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( 'scala_add_guides' );

	$result = scala_insert_missing_guides();

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'            => 'scala-settings',
				'tab'             => 'pages',
				'scala-guides'     => count( $result['added'] ),
				'scala-guides-upd' => count( $result['updated'] ),
				'scala-guides-had' => count( $result['skipped'] ),
			),
			admin_url( 'admin.php' )
		) . '#scala-guides'
	);
	exit;
}
add_action( 'admin_post_scala_add_guides', 'scala_guides_action' );

/**
 * Повідомлення після натискання.
 *
 * @return void
 */
function scala_guides_notice(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- лише показ результату.
	if ( ! isset( $_GET['page'], $_GET['scala-guides'] ) || 'scala-settings' !== $_GET['page'] ) {
		return;
	}

	$added = (int) $_GET['scala-guides'];
	$upd   = isset( $_GET['scala-guides-upd'] ) ? (int) $_GET['scala-guides-upd'] : 0;
	$had   = isset( $_GET['scala-guides-had'] ) ? (int) $_GET['scala-guides-had'] : 0;
	// phpcs:enable

	if ( $upd > 0 && 0 === $added ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d — кількість оновлених матеріалів */
					_n( 'Оновлено %d матеріал: у тексті зʼявилися посилання на сторінки видів штор.', 'Оновлено матеріалів: %d.', $upd, 'scala' ),
					$upd
				)
			)
		);

		return;
	}

	if ( $added > 0 ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
			esc_html(
				sprintf(
					/* translators: %d — кількість доданих матеріалів */
					_n( 'Додано %d матеріал — чернеткою.', 'Додано матеріалів: %d, чернетками.', $added, 'scala' ),
					$added
				)
			),
			esc_url( admin_url( 'edit.php?post_status=draft&post_type=post' ) ),
			esc_html__( 'Переглянути чернетки', 'scala' )
		);

		return;
	}

	printf(
		'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
		esc_html(
			$had
				? __( 'Нових матеріалів немає: усі вже є на сайті.', 'scala' )
				: __( 'Немає що додавати.', 'scala' )
		)
	);
}
add_action( 'admin_notices', 'scala_guides_notice' );

/**
 * Блок із кнопкою під формою вкладки «Внутрішні сторінки».
 *
 * @param string $tab Поточна вкладка.
 * @return void
 */
function scala_guides_box( string $tab ): void {
	if ( 'pages' !== $tab ) {
		return;
	}

	$guides = scala_guide_seed_data();

	if ( ! $guides ) {
		return;
	}
	?>
	<div class="scala-tgbox" id="scala-guides">
		<h2 class="scala-tgbox__title"><?php esc_html_e( 'Готові матеріали для пошуку', 'scala' ); ?></h2>

		<p class="scala-tgbox__status">
			<?php esc_html_e( 'Сторінки видів штор описують кожен тип окремо. Ці тексти відповідають на питання вибору — «які штори обрати для спальні», «чим римські відрізняються від рулонних», — з якими приходять у пошук і до ШІ-асистентів.', 'scala' ); ?>
		</p>

		<ul class="scala-guides__list">
			<?php foreach ( $guides as $guide ) : ?>
				<?php
				$slug   = sanitize_title( (string) ( $guide['slug'] ?? '' ) );
				$kind   = 'page' === ( $guide['type'] ?? 'post' ) ? 'page' : 'post';
				$exists = $slug ? get_posts(
					array(
						'name'           => $slug,
						'post_type'      => $kind,
						'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
						'posts_per_page' => 1,
						'fields'         => 'ids',
					)
				) : array();
				?>
				<li>
					<strong><?php echo esc_html( (string) ( $guide['title'] ?? '' ) ); ?></strong>
					<span class="scala-guides__kind"><?php echo esc_html( 'page' === $kind ? __( 'сторінка', 'scala' ) : __( 'стаття', 'scala' ) ); ?></span>
					<?php if ( $exists ) : ?>
						<span class="scala-tgbox__current"><?php esc_html_e( '— уже на сайті', 'scala' ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'scala_add_guides' ); ?>
			<input type="hidden" name="action" value="scala_add_guides">
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Додати або оновити', 'scala' ); ?>
			</button>
		</form>

		<p class="scala-tgbox__hint">
			<?php esc_html_e( 'Нові матеріали додаються чернетками: прочитайте текст і натисніть «Опублікувати», коли погодитесь із формулюваннями. Наявний матеріал оновлюється лише тоді, коли його не редагували вручну, — ваші правки не перезаписуються ніколи.', 'scala' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'scala_options_after_form', 'scala_guides_box' );
