<?php
/**
 * Початкове наповнення при першій активації теми.
 *
 * Мета: після активації сайт одразу виглядає готовим — з текстами й
 * фотографіями з макета, а не порожнім каркасом. Усе, що створюється
 * тут, редагується в адмінці як звичайний контент.
 *
 * Запускається один раз: прапорець scala_seeded не дає перезаписати
 * зміни клієнта при повторній активації.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Точка входу: наповнення після активації теми.
 *
 * @return void
 */
function scala_seed_content(): void {
	if ( get_option( 'scala_seeded' ) ) {
		return;
	}

	update_option( 'scala_seeded', SCALA_VERSION );

	$images = scala_import_bundled_images();

	scala_seed_options( $images );
	scala_seed_types( $images );
	scala_seed_fabrics( $images );
	scala_seed_projects( $images );
	scala_seed_extras();
	scala_seed_faq();
	scala_seed_reviews();
	scala_seed_instagram( $images );
	scala_seed_pages();
}

/**
 * Створює сторінки сайту з текстами й призначає їм шаблони.
 *
 * Текст сторінок — це звичайний вміст редактора WordPress: клієнт
 * редагує його як будь-яку сторінку, з заголовками, списками й
 * посиланнями. Тема виводить його у двоколонковому текстовому блоці.
 *
 * @return void
 */
function scala_seed_pages(): void {
	$pages = array(
		'home' => array(
			'title'    => __( 'Головна', 'scala' ),
			'slug'     => 'holovna',
			'template' => '',
			'content'  => scala_prose_home(),
		),
		'catalog' => array(
			'title'    => __( 'Каталог', 'scala' ),
			'slug'     => 'catalog',
			'template' => 'template-catalog.php',
			'content'  => scala_prose_catalog(),
		),
		'about' => array(
			'title'    => __( 'Мистецтво тканини', 'scala' ),
			'slug'     => 'about',
			'template' => 'template-about.php',
			'content'  => scala_prose_about(),
		),
		'contacts' => array(
			'title'    => __( 'Напишіть або зателефонуйте', 'scala' ),
			'slug'     => 'contacts',
			'template' => 'template-contacts.php',
			'content'  => scala_prose_contacts(),
		),
	);

	$created = array();

	foreach ( $pages as $key => $page ) {
		$existing = get_page_by_path( $page['slug'] );

		if ( $existing ) {
			$created[ $key ] = (int) $existing->ID;
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page['title'],
				'post_name'    => $page['slug'],
				'post_content' => $page['content'],
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		if ( $page['template'] ) {
			update_post_meta( $post_id, '_wp_page_template', $page['template'] );
		}

		$created[ $key ] = (int) $post_id;
	}

	// Головною робимо створену сторінку, щоб працював front-page.php.
	if ( ! empty( $created['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $created['home'] );
	}

	scala_seed_menu( $created );
}

/**
 * Складає головне меню з новостворених сторінок.
 *
 * @param array $pages Мапа «ключ => ID сторінки».
 * @return void
 */
function scala_seed_menu( array $pages ): void {
	$menu_name = __( 'Головне меню', 'scala' );

	if ( wp_get_nav_menu_object( $menu_name ) ) {
		return;
	}

	$menu_id = wp_create_nav_menu( $menu_name );

	if ( is_wp_error( $menu_id ) ) {
		return;
	}

	// Якорі секцій головної.
	$anchors = array(
		'#solutions' => __( 'Рішення', 'scala' ),
		'#projects'  => __( 'Проєкти', 'scala' ),
		'#process'   => __( 'Як працюємо', 'scala' ),
		'#fabrics'   => __( 'Тканини', 'scala' ),
	);

	foreach ( $anchors as $url => $label ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $label,
				'menu-item-url'    => home_url( '/' ) . $url,
				'menu-item-type'   => 'custom',
				'menu-item-status' => 'publish',
			)
		);
	}

	foreach ( array( 'catalog', 'about', 'contacts' ) as $key ) {
		if ( empty( $pages[ $key ] ) ) {
			continue;
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-object-id' => $pages[ $key ],
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}
add_action( 'after_switch_theme', 'scala_seed_content' );

/**
 * Переносить фото теми в медіабібліотеку.
 *
 * @return array Мапа «імʼя файлу без розширення» => ID вкладення.
 */
function scala_import_bundled_images(): array {
	$dir = SCALA_DIR . '/assets/img';

	if ( ! is_dir( $dir ) ) {
		return array();
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$map   = array();
	$files = glob( $dir . '/*.webp' );

	if ( ! $files ) {
		return array();
	}

	foreach ( $files as $file ) {
		$name = basename( $file, '.webp' );

		// Половинні версії не потрібні: WordPress зробить свої розміри.
		if ( str_contains( $name, '@0.5x' ) ) {
			continue;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'name'           => sanitize_title( $name ),
				'fields'         => 'ids',
			)
		);

		if ( $existing ) {
			$map[ $name ] = (int) $existing[0];
			continue;
		}

		$tmp = wp_tempnam( $file );

		if ( ! $tmp || ! copy( $file, $tmp ) ) {
			continue;
		}

		$attachment_id = media_handle_sideload(
			array(
				'name'     => $name . '.webp',
				'tmp_name' => $tmp,
			),
			0,
			scala_image_alt_for( $name )
		);

		if ( is_wp_error( $attachment_id ) ) {
			// Тимчасовий файл прибираємо самі, якщо перенесення не вдалось.
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			continue;
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', scala_image_alt_for( $name ) );
		$map[ $name ] = (int) $attachment_id;
	}

	return $map;
}

/**
 * Осмислений alt для фото з комплекту.
 *
 * @param string $name Імʼя файлу без розширення.
 * @return string
 */
function scala_image_alt_for( string $name ): string {
	$alts = array(
		'hero-room'             => 'Тюль і портьєри на панорамному вікні',
		'hero-curtain'          => 'Закрите полотно штори',
		'scenario-tulle'        => 'Легкий тюль на вікні',
		'scenario-tulle-drapes' => 'Тюль і портьєри разом',
		'scenario-blackout'     => 'Щільні портьєри в затемненій кімнаті',
		'type-tulle'            => 'Тюль на замовлення',
		'type-classic'          => 'Класичні штори з драпіровкою',
		'type-roman'            => 'Римські штори на кухні',
		'type-wood-blinds'      => 'Дерев’яні жалюзі на вікні',
		'type-roller'           => 'Рулонні штори на стулці',
		'project-living'        => 'Штори у вітальні',
		'project-bedroom'       => 'Штори у спальні',
		'project-stairs'        => 'Штори на сходовому прольоті',
		'project-terrace'       => 'Штори на терасі',
		'project-commercial'    => 'Штори в комерційному просторі',
		'fabric-velvet-luxe'    => 'Тканина Velvet Luxe, оксамит',
		'fabric-light-air'      => 'Тканина Light Air, напівпрозоре полотно',
		'fabric-urban-gold'     => 'Тканина Urban Gold із золотистим відблиском',
		'work-01'               => 'Виконана робота Scala',
		'work-02'               => 'Виконана робота Scala',
	);

	if ( isset( $alts[ $name ] ) ) {
		return $alts[ $name ];
	}

	// Скріншоти відгуків: review-03 → «…переписки 3».
	if ( str_starts_with( $name, 'review-' ) ) {
		return sprintf(
			/* translators: %d — порядковий номер скріншота */
			__( 'Відгук клієнта, скріншот переписки %d', 'scala' ),
			(int) substr( $name, 7 )
		);
	}

	// Фото тканин: fab-oksamyt → «Тканина Оксамит».
	if ( str_starts_with( $name, 'fab-' ) ) {
		return 'Тканина ' . ucfirst( str_replace( '-', ' ', substr( $name, 4 ) ) );
	}

	return 'Штори Scala';
}

/**
 * Значення налаштувань за замовчуванням.
 *
 * @param array $img Мапа зображень.
 * @return void
 */
function scala_seed_options( array $img ): void {
	$values = array();

	// Спершу все, що описано в схемі як default.
	foreach ( scala_options_schema() as $tab ) {
		foreach ( $tab['fields'] as $field ) {
			if ( isset( $field['default'] ) ) {
				$values[ $field['key'] ] = $field['default'];
			}
		}
	}

	$values['room_image']    = $img['hero-room'] ?? 0;
	$values['curtain_image'] = $img['hero-curtain'] ?? 0;
	$values['photo']         = $img['fabric-urban-gold'] ?? 0;
	$values['about_image']   = $img['project-living'] ?? 0;

	$values['benefits'] = array(
		array( 'title' => 'Світло', 'text' => 'Від мʼякого розсіяного до повного затемнення — ви керуєте ранком.' ),
		array( 'title' => 'Приватність', 'text' => 'Щільність тканини підбираємо під поверх, сторону світу й сусідні вікна.' ),
		array( 'title' => 'Атмосфера', 'text' => 'Фактура й колір текстилю задають настрій кімнати сильніше за меблі.' ),
		array( 'title' => 'Пропорції кімнати', 'text' => 'Правильна висота карниза й довжина полотна витягують стелю та вікно.' ),
	);

	$values['scenarios'] = array(
		array(
			'title' => 'Легкий тюль',
			'text'  => 'Максимум денного світла: полотно розсіює сонце й приховує кімнату від вулиці.',
			'image' => $img['scenario-tulle'] ?? 0,
			'dim'   => 0,
		),
		array(
			'title' => 'Тюль + портьєри',
			'text'  => 'Вдень — світло крізь тюль, увечері портьєри закривають вікно й завершують інтерʼєр.',
			'image' => $img['scenario-tulle-drapes'] ?? 0,
			'dim'   => 0,
		),
		array(
			'title' => 'Щільні портьєри',
			'text'  => 'Глибоке затемнення для спальні: кімната лишається темною навіть уранці влітку.',
			'image' => $img['scenario-blackout'] ?? 0,
			'dim'   => 1,
		),
	);

	$values['process'] = array(
		array( 'title' => 'Заявка', 'text' => 'Ви залишаєте телефон — менеджер уточнює кімнату, вікна та зручний час.' ),
		array( 'title' => 'Виїзд дизайнера і замір', 'text' => 'Дизайнер приїжджає зі зразками тканин, робить точні заміри вікон і карнизів.' ),
		array( 'title' => 'Підбір та пошиття', 'text' => 'Погоджуємо тканину, конструкцію та кошторис — і передаємо виріб у пошиття.' ),
		array( 'title' => 'Монтаж', 'text' => 'Встановлюємо карниз, вішаємо полотна й формуємо складку на місці.' ),
	);

	$values['includes'] = array(
		array( 'text' => 'Виїзд дизайнера зі зразками тканин' ),
		array( 'text' => 'Заміри вікон, карнизів і ніш' ),
		array( 'text' => 'Індивідуальний підбір тканини під ваше освітлення' ),
		array( 'text' => 'Кошторис до початку робіт' ),
		array( 'text' => 'Пошиття та монтаж «під ключ»' ),
	);

	$values['works'] = array(
		array( 'image' => $img['work-01'] ?? 0, 'alt' => 'Виконана робота Scala' ),
		array( 'image' => $img['work-02'] ?? 0, 'alt' => 'Виконана робота Scala' ),
		array( 'image' => $img['project-commercial'] ?? 0, 'alt' => 'Виконана робота Scala' ),
	);

	// Скріншоти переписок з клієнтами. У сітці показуються шість,
	// решта відкривається в перегляді на весь екран.
	$values['screenshots'] = array(
		array( 'image' => $img['review-01'] ?? 0 ),
		array( 'image' => $img['review-02'] ?? 0 ),
		array( 'image' => $img['review-03'] ?? 0 ),
		array( 'image' => $img['review-04'] ?? 0 ),
		array( 'image' => $img['review-05'] ?? 0 ),
		array( 'image' => $img['review-06'] ?? 0 ),
		array( 'image' => $img['review-07'] ?? 0 ),
		array( 'image' => $img['review-08'] ?? 0 ),
		array( 'image' => $img['review-09'] ?? 0 ),
		array( 'image' => $img['review-10'] ?? 0 ),
		array( 'image' => $img['review-11'] ?? 0 ),
	);

	$values['needs'] = array(
		array( 'text' => 'Тюль' ),
		array( 'text' => 'Класичні штори' ),
		array( 'text' => 'Римські штори' ),
		array( 'text' => 'Дерев’яні жалюзі' ),
		array( 'text' => 'Рулонні системи' ),
		array( 'text' => 'Кілька вікон / вся квартира' ),
		array( 'text' => 'Комерційний простір' ),
		array( 'text' => 'Ще не визначився' ),
	);

	$values['about_body'] = 'Вікно визначає, як виглядає кімната протягом дня: скільки в ній світла, наскільки вона приватна, якою здається її висота. Тому ми починаємо не з тканини, а з приміщення.';

	$values['about_services'] = array(
		array( 'title' => 'Виїзд дизайнера', 'text' => 'Приїжджаємо зі зразками тканин, робимо заміри вікон і карнизів.' ),
		array( 'title' => 'Індивідуальний підбір тканини', 'text' => 'Фактура, щільність і колір — під ваше освітлення та інтерʼєр.' ),
		array( 'title' => 'Пошиття та монтаж «під ключ»', 'text' => 'Від розкрою до складки на вікні — з одним відповідальним за результат.' ),
		array( 'title' => 'Співпраця для дизайнерів', 'text' => 'Окремі умови для студій та проєктів інтерʼєру.' ),
	);

	update_option( SCALA_OPT_KEY, $values );
}

/**
 * Створює запис і повертає його ID.
 *
 * @param string $post_type Тип запису.
 * @param string $title     Заголовок.
 * @param array  $args      Додаткові поля: content, excerpt, order, thumb, meta.
 * @return int
 */
function scala_seed_post( string $post_type, string $title, array $args = array() ): int {
	$post = array(
		'post_type'    => $post_type,
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_content' => $args['content'] ?? '',
		'post_excerpt' => $args['excerpt'] ?? '',
		'menu_order'   => $args['order'] ?? 0,
	);

	/*
	 * Латинський слаг задаємо самі. Автоматичний робиться з української
	 * назви й перетворюється на %d1%82%d1%8e... — така адреса нечитабельна
	 * і в пошуку, і в аналітиці.
	 */
	if ( ! empty( $args['slug'] ) ) {
		$post['post_name'] = sanitize_title( (string) $args['slug'] );
	}

	$post_id = wp_insert_post( $post );

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return 0;
	}

	if ( ! empty( $args['thumb'] ) ) {
		set_post_thumbnail( $post_id, (int) $args['thumb'] );
	}

	foreach ( (array) ( $args['meta'] ?? array() ) as $key => $value ) {
		update_post_meta( $post_id, '_scala_' . $key, $value );
	}

	return (int) $post_id;
}

/**
 * Види штор.
 *
 * @param array $img Мапа зображень.
 * @return void
 */
function scala_seed_types( array $img ): void {
	$order = 0;

	foreach ( scala_type_seed_data() as $item ) {
		$fabrics = array();

		foreach ( (array) ( $item['fabrics'] ?? array() ) as $name ) {
			$fabrics[] = array( 'name' => $name );
		}

		scala_seed_post(
			'scala_type',
			$item['title'],
			array(
				'order'   => $order++,
				'slug'    => $item['slug'],
				'excerpt' => $item['excerpt'],
				'content' => $item['content'],
				'thumb'   => $img[ $item['image'] ] ?? 0,
				'meta'    => array(
					'h1'         => $item['h1'],
					'short'      => $item['short'],
					'lead'       => $item['lead'],
					'tag'        => 'Докладно →',
					'in_catalog' => 1,
					'on_home'    => (int) $item['on_home'],
					'fabrics'    => $fabrics,
					'faq'        => (array) ( $item['faq'] ?? array() ),
				),
			)
		);
	}
}

/**
 * Тканини — реальний каталог із чинного сайту клієнта.
 *
 * Поділ на портьєрні (textile) та оббивні відповідає категоріям
 * на scalahome.com.ua. На головній показуються лише портьєрні:
 * оббивні до штор стосунку не мають.
 *
 * @param array $img Мапа зображень.
 * @return void
 */
function scala_seed_fabrics( array $img ): void {
	$items = array(
		array( 'Anatolia', 'anatolia', '', '', false ),
		array( 'Avis', 'avis', '', '', false ),
		array( 'Carys', 'carys', '', '', false ),
		array( 'Gallery', 'gallery', '', '', false ),
		array( 'Happy', 'happy', '— бархатна радість у вашому інтер’єрі Відчуйте новий рівень комфорту та розкоші з тканиною Happy.', '— бархатна радість у вашому інтер’єрі Відчуйте новий рівень комфорту та розкоші з тканиною Happy. Вона нагадує класичний оксамит своєю глибокою, м’якою ворсовою фактурою, але при цьому більш практична та довговічна. Неперевершена м’якість – ідеальна для штор, які хочеться торкатися. Багата бархатна фактура – створює ефект розкоші та тепла у кімнаті. Практичність та легкість у догляді – тканина менше мнеться, добре тримає форму і легко драпірується. Happy — це не просто тканина. Це радість, яку ви відчуваєте кожного дня, теплий акцент вашого дому та стильна деталь, що перетворює інтер’єр на справжнє мистецтво.', true ),
		array( 'ICON', 'icon', 'Тканина Icon – це втілення сучасного преміального текстилю, де ідеально поєднуються естетика, щільність та фун…', 'Тканина Icon – це втілення сучасного преміального текстилю, де ідеально поєднуються естетика, щільність та функціональність. Вона створена для інтер’єрів, у яких важлива не лише краса, а й відчуття затишку, приватності та тиші. Головні переваги: Максимальне затемнення (blackout 90-100%) Надійно блокує денне світло, створюючи комфортну атмосферу для відпочинку у будь-який час доби. Щільна багатошарова структура Забезпечує відмінну шумоізоляцію та терморегуляцію – зберігає прохолоду влітку та тепло взимку. Шляхетна фактура М’яка, трохи матова поверхня з глибокою текстурою виглядає дорого і стримано, легко вписується в сучасні та класичні інтер’єри. Ідеальне драпірування Тканина формує рівні, важкі складки, створюючи ефект дорогих штор без зайвого блиску. Зносостійкість та практичність Стійка до вигоряння, не втрачає форми та зберігає презентабельний зовнішній вигляд довгі роки. Icon — вибір для тих, хто хоче отримати максимум: повне затемнення, преміальну тактильність і візуальний статус в одному рішенні.', true ),
		array( 'Jelly Roll', 'jelly-roll', '', '', false ),
		array( 'Jolly', 'jolly', '– двостороння тканина преміум-класу Пориньте у світ комфорту та елегантності з тканиною Jolly.', '– двостороння тканина преміум-класу Пориньте у світ комфорту та елегантності з тканиною Jolly. Її двостороння структура створює унікальну гру текстур та відтінків, а м’якість і тактильна приємність перетворюють кожен дотик на задоволення. Ідеальний вибір для стильних інтер’єрів, де цінують якість, розкіш та увагу до деталей.', true ),
		array( 'Канвас', 'kanvas', '– це щільна шторна тканина з виразною текстурою, що візуально нагадує мікровелюр.', '– це щільна шторна тканина з виразною текстурою, що візуально нагадує мікровелюр. За рахунок щільнішого переплетення виглядає дорожче і служить значно довше. Чому вибирають канвас: Підвищена щільність – тримає форму, красиво лягає у складки. Хороше затемнення – краще захищає від світла, ніж бюджетні тканини. Зносостійкість – не затирається і довше зберігає зовнішній вигляд Фактура “під текстиль преміум” – виглядає дорого без переплати.', true ),
		array( 'Kenobi', 'kenobi', '', '', false ),
		array( 'Light Air', 'light-air', 'легка повітряна тканина з м’якою, напівпрозорою фактурою.', 'легка повітряна тканина з м’якою, напівпрозорою фактурою. Забезпечує природне розсіювання світла, створюючи атмосферу затишку та простору. Ідеально підходить для пошиття гардин, декоративних елементів та текстильного оформлення світлих інтер’єрів.', true ),
		array( 'Lion', 'lion', '– це не просто блекаут, а повноцінний інтер’єрний текстиль преміум-класу, в якому поєднуються технологія затем…', '– це не просто блекаут, а повноцінний інтер’єрний текстиль преміум-класу, в якому поєднуються технологія затемнення та естетика дорогої фактури. Тканина виконана з щільним багатошаровим переплетенням, завдяки чому забезпечує практично повне блокування світла, створюючи ідеальні умови для відпочинку та усамітнення. Сучасні blackout-тканини досягають такого ефекту за рахунок багатошарової структури, яка може перекривати до 95-100% світла. 💎 Візуал та фактура Lion відрізняється благородною, трохи зернистою текстурою з ефектом натурального переплетення. Це робить тканину візуально “дорогою” – вона не плоска, а жива, з глибиною кольору та м’якими переходами відтінків. Палітра – складні інтер’єрні тони: теплі бежеві та пісочні приглушені тауп та капучино м’які сіро-блакитні Такі кольори легко інтегруються у сучасні інтер’єри преміум-сегменту.', true ),
		array( 'Мікровелюр', 'mikrovelyur', '— це бюджетна шторна тканина на ринку, виконана з тонкого синтетичного матеріалу без вираженої фактури.', '— це бюджетна шторна тканина на ринку, виконана з тонкого синтетичного матеріалу без вираженої фактури. Вона дуже легка і представлена в широкій палітрі кольорів, що робить її доступним рішенням для простих інтер’єрів. Її основні переваги – мінімальна ціна та великий вибір відтінків. До недоліків відносяться тонкість тканини, низька зносостійкість, схильність до вигоряння на сонці та швидка втрата зовнішнього вигляду. Додатково: може просвічувати погано тримає форму виглядає досить просто і не дає «дорогого» ефекту обмежений термін служби', true ),
		array( 'Museum', 'museum', '', '', false ),
		array( 'Оксамит', 'oksamyt', '– це щільна ворсова тканина з м’якою, глибокою фактурою та характерним благородним блиском.', '– це щільна ворсова тканина з м’якою, глибокою фактурою та характерним благородним блиском. Він асоціюється з преміальністю, затишком та «важким» інтер’єром. Ключові характеристики: Ворсиста поверхня – короткий густий ворс, який дає м’якість та гру світла. Щільність – добре тримає форму і гарно драпірується Глибина кольору – відтінки виглядають насиченими та «дорогими» Затемнення – рівень світлозахисту не високий, але у темних кольорах до 70%. Переваги: Виглядає дорого та статусно Відмінно підходить для спалень та віталень Добре гасить звук (частково покращує акустику) Створює відчуття тепла та затишку Недоліки: Може збирати пил (важливий догляд) Важкий – вимагає надійних карнизів Ціна вища за середню Може “затиратися” у місцях частого контакту', true ),
		array( 'Pool', 'pool', '', '', false ),
		array( 'Портьєрна тканина', 'portyerna-tkanyna', 'Тонка портьєрна тканина з фактурою під льон – це доступне рішення для оформлення вікон у сучасному інтер’єрі.', 'Тонка портьєрна тканина з фактурою під льон – це доступне рішення для оформлення вікон у сучасному інтер’єрі. Матеріал імітує натуральну лляну структуру з характерним переплетенням ниток, створюючи візуально живу поверхню. ✔️ Переваги доступна ціна (одна з найбільш бюджетних категорій) велика палітра кольорів легкість та простота в драпіруванні універсальний зовнішній вигляд «під натуральний льон» ❌ Недоліки тонке полотно, може просвічувати низька зносостійкість швидше вигоряє на сонці простіше виглядає в порівнянні з більш щільними тканинами не дає відчуття «преміальності»', true ),
		array( 'Resort', 'resort', '', '', false ),
		array( 'Thakur', 'thakur', '', '', false ),
		array( 'Urban Gold', 'urban-gold', 'елегантна тканина з гладкою текстурою та витонченим металевим відблиском у золотистих тонах.', 'елегантна тканина з гладкою текстурою та витонченим металевим відблиском у золотистих тонах. Сучасне поєднання розкоші та мінімалізму робить її ідеальною для акцентної оббивки меблів, декоративних подушок та стильних інтер’єрних рішень. Має міцність і стійкість до зносу.', true ),
		array( 'Velvet Luxe', 'velvet-luxe', 'Тканина – м’яка органічна бавовна преміальної якості.', 'Тканина – м’яка органічна бавовна преміальної якості. Вона приємна на дотик, має природну еластичність і забезпечує комфорт протягом усього дня. Ідеально підходить для повсякденного носіння завдяки властивостям, що дихають, і м’якій текстурі. Зовнішній: Шкіра 100%, Поліамід 100% Підкладка: Поліестер 100% SounSolety: Гума 100%', true ),
	);

	$order = 0;

	foreach ( $items as $item ) {
		list( $title, $slug, $short, $full, $is_textile ) = $item;

		scala_seed_post(
			'scala_fabric',
			$title,
			array(
				'order'   => $order++,
				'excerpt' => $short,
				'content' => $full,
				'thumb'   => $img[ 'fab-' . $slug ] ?? 0,
				'meta'    => array(
					'tag'     => $is_textile ? 'Портьєрна' : 'Оббивна',
					'group'   => $is_textile ? 'textile' : 'upholstery',
					// На головній — лише три перші портьєрні, щоб секція
					// лишалась оглядовою; решта живе в каталозі.
					'on_home' => ( $is_textile && $order <= 3 ) ? 1 : 0,
				),
			)
		);
	}
}

/**
 * Проєкти для стрічки на головній.
 *
 * @param array $img Мапа зображень.
 * @return void
 */
function scala_seed_projects( array $img ): void {
	$items = array(
		array( 'Вітальня', 'project-living', 1 ),
		array( 'Спальня', 'project-bedroom', 0 ),
		array( 'Сходовий проліт', 'project-stairs', 0 ),
		array( 'Тераса', 'project-terrace', 0 ),
		array( 'Комерційний простір', 'project-commercial', 0 ),
	);

	$order = 0;

	foreach ( $items as $item ) {
		list( $title, $slug, $wide ) = $item;

		scala_seed_post(
			'scala_project',
			$title,
			array(
				'order' => $order++,
				'thumb' => $img[ $slug ] ?? 0,
				'meta'  => array(
					'wide' => $wide,
					'alt'  => scala_image_alt_for( $slug ),
				),
			)
		);
	}
}

/**
 * Комплектація для каталогу.
 *
 * @return void
 */
function scala_seed_extras(): void {
	$items = array(
		array( 'Карнизи', 'Приховані ніші, стельові треки, електрокарнизи.' ),
		array( 'Блекаут-підкладка', 'Затемнення для спальні й дитячої, підшивається до портьєр.' ),
	);

	$order = 0;

	foreach ( $items as $item ) {
		scala_seed_post(
			'scala_extra',
			$item[0],
			array(
				'order'   => $order++,
				'excerpt' => $item[1],
				'meta'    => array(
					'tag'   => 'Комплектація',
					'short' => $item[1],
				),
			)
		);
	}
}

/**
 * Часті питання.
 *
 * @return void
 */
function scala_seed_faq(): void {
	$items = array(
		array(
			'Скільки коштують штори',
			'Вартість залежить від розміру вікна, типу конструкції, тканини, карниза та складності монтажу. Точну суму дизайнер розраховує після заміру — до цього будь-яка цифра була б умовною.',
		),
		array(
			'Що входить у виїзд дизайнера',
			'Зразки тканин, заміри вікон і карнизів, підбір конструкції під ваш інтерʼєр і освітлення, розрахунок кошторису.',
		),
		array(
			'Чи можна замовити лише пошиття',
			'Так. Можливе як обслуговування «під ключ» — від підбору тканини до монтажу, так і окремо пошиття чи індивідуальний підбір тканини.',
		),
		array(
			'Ви працюєте з дизайнерами інтерʼєрів',
			'Так, для дизайнерів та студій діють окремі умови співпраці — напишіть нам у Telegram або WhatsApp, щоб обговорити деталі.',
		),
	);

	$order = 0;

	foreach ( $items as $item ) {
		scala_seed_post(
			'scala_faq',
			$item[0],
			array(
				'order'   => $order++,
				'content' => $item[1],
			)
		);
	}
}

/**
 * Відгуки.
 *
 * @return void
 */
function scala_seed_reviews(): void {
	scala_seed_post(
		'scala_review',
		'Оформлення кімнат «під ключ»',
		array(
			'order'   => 0,
			'content' => '«Вчора все було завершено, кімнати виглядають на всі сто, наша родина дуже вдячна вам за цю прекрасну послугу. Ми взагалі не переймались — ви нам допомогли і з вибором, і з монтажем. Повністю впевнена у вашій команді, буду радити усім друзям і знайомим.»',
			'meta'    => array( 'author' => 'Клієнтка · оформлення кімнат «під ключ»' ),
		)
	);
}

/**
 * Плитки Instagram.
 *
 * @param array $img Мапа зображень.
 * @return void
 */
function scala_seed_instagram( array $img ): void {
	$tiles = array(
		'project-terrace',
		'type-roman',
		'fabric-velvet-luxe',
		'project-stairs',
		'type-wood-blinds',
		'fabric-urban-gold',
	);

	$order = 0;

	foreach ( $tiles as $slug ) {
		scala_seed_post(
			'scala_ig',
			scala_image_alt_for( $slug ),
			array(
				'order' => $order++,
				'thumb' => $img[ $slug ] ?? 0,
				'meta'  => array(
					'url' => '',
					'alt' => scala_image_alt_for( $slug ),
				),
			)
		);
	}
}
