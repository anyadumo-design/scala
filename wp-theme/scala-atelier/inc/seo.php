<?php
/**
 * Мета-теги, Open Graph і структуровані дані.
 *
 * У клієнта встановлений Yoast SEO. Якщо він активний — тема не додає
 * нічого, крім розмітки FAQ, якої Yoast сам не збирає з наших питань.
 * Дублювати canonical, OG і Organization не можна: пошуковик отримає
 * суперечливі дані.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Чи працює зовнішній SEO-плагін.
 *
 * @return bool
 */
function scala_seo_plugin_active(): bool {
	return defined( 'WPSEO_VERSION' )            // Yoast SEO.
		|| class_exists( 'RankMath' )            // Rank Math.
		|| defined( 'SEOPRESS_VERSION' )         // SEOPress.
		|| defined( 'AIOSEO_VERSION' );          // All in One SEO.
}

/**
 * Open Graph і canonical — лише коли SEO-плагіна немає.
 *
 * @return void
 */
function scala_head_meta(): void {
	if ( scala_seo_plugin_active() ) {
		return;
	}

	$title = wp_get_document_title();

	/*
	 * НЕ home_url( add_query_arg( array() ) ): цей вираз повертає весь
	 * REQUEST_URI разом із query-рядком, а home_url() ще раз додає шлях
	 * інсталяції. У canonical потрапляли б utm-мітки — і він переставав
	 * би склеювати дублі, заради чого його й ставлять.
	 */
	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_singular() ) {
		$url = (string) get_permalink();
	} elseif ( is_post_type_archive() ) {
		$url = (string) get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
	} else {
		$url = home_url( user_trailingslashit( $GLOBALS['wp']->request ?? '' ) );
	}

	// На 404 канонічної адреси не існує — не вигадуємо її.
	if ( is_404() ) {
		$url = '';
	}

	$description = scala_meta_description();
	$image       = scala_share_image();

	if ( $url ) {
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $url ) );
	}

	if ( $description ) {
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
	}

	printf( '<meta property="og:type" content="%s" />' . "\n", is_singular() && ! is_front_page() ? 'article' : 'website' );
	printf( '<meta property="og:locale" content="%s" />' . "\n", esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) );
	printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	if ( $url ) {
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
	}
	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );

	if ( $description ) {
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
	}

	if ( $image ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	}
}
add_action( 'wp_head', 'scala_head_meta', 2 );

/* ------------------------------------------ Заголовок сторінки ---- */

/**
 * Місто у фразі заголовка.
 *
 * Береться з налаштувань, щоб у заголовку не було міста, якого немає
 * в контактах. Для Києва — усталена форма, яку вже вживає сайт.
 *
 * @return string
 */
function scala_geo_phrase(): string {
	$city = trim( wp_strip_all_tags( (string) scala_opt( 'city', '' ) ) );

	if ( '' === $city ) {
		return '';
	}

	if ( 'Київ' === $city ) {
		return __( 'на замовлення в Києві', 'scala' );
	}

	/* translators: %s — місто з налаштувань */
	return sprintf( __( 'на замовлення, %s', 'scala' ), $city );
}

/**
 * Заголовок сторінки для пошуку й соцмереж.
 *
 * WordPress і неналаштований Yoast дають «Каталог - Scala»: ні
 * послуги, ні міста, ні причини клікнути. Тут той самий заголовок
 * збирається з тексту, який на сторінці вже є — H1 головної, назва
 * виду штор, назва сторінки — і доповнюється містом там, де це правда.
 *
 * @return string
 */
function scala_document_title(): string {
	$site = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
	$geo  = scala_geo_phrase();
	$sep  = ' — ';

	if ( is_front_page() ) {
		$h1 = trim( wp_strip_all_tags( (string) scala_opt( 'h1', '' ) ) );

		return $h1 ? $h1 . $sep . $site : $site;
	}

	if ( is_post_type_archive( 'scala_type' ) ) {
		return trim( __( 'Види штор', 'scala' ) . ' ' . $geo ) . $sep . $site;
	}

	if ( is_singular( 'scala_type' ) ) {
		return trim( get_the_title() . ' ' . $geo ) . $sep . $site;
	}

	if ( is_page_template( 'template-catalog.php' ) ) {
		return trim( get_the_title() . ' ' . __( 'тканин і видів штор', 'scala' ) ) . $sep . $site;
	}

	if ( is_page_template( 'template-contacts.php' ) ) {
		$city = trim( wp_strip_all_tags( (string) scala_opt( 'city', '' ) ) );

		return get_the_title() . $sep . $site . ( $city ? ', ' . $city : '' );
	}

	if ( is_singular( 'scala_project' ) ) {
		return get_the_title() . $sep . __( 'реалізований проєкт', 'scala' ) . ', ' . $site;
	}

	if ( is_singular( 'scala_fabric' ) ) {
		return get_the_title() . $sep . __( 'тканина для штор', 'scala' ) . ', ' . $site;
	}

	if ( is_singular() ) {
		return get_the_title() . $sep . $site;
	}

	if ( is_search() ) {
		/* translators: %s — пошуковий запит */
		return sprintf( __( 'Пошук: %s', 'scala' ), get_search_query() ) . $sep . $site;
	}

	if ( is_404() ) {
		return __( 'Сторінку не знайдено', 'scala' ) . $sep . $site;
	}

	if ( is_home() ) {
		$blog_id = (int) get_option( 'page_for_posts' );
		$blog    = $blog_id ? get_the_title( $blog_id ) : __( 'Журнал', 'scala' );

		return $blog . $sep . $site;
	}

	if ( is_post_type_archive() ) {
		$label = trim( wp_strip_all_tags( (string) post_type_archive_title( '', false ) ) );

		return $label ? $label . $sep . $site : '';
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$term = trim( wp_strip_all_tags( (string) single_term_title( '', false ) ) );

		return $term ? $term . $sep . $site : '';
	}

	/*
	 * Далі — усе, для чого в теми немає власного заголовка: архіви за
	 * датою, вкладення, службові сторінки. Порожній рядок означає
	 * «не втручаємось»: заголовок лишиться той, що порахував Yoast.
	 * Раніше тут поверталась сама назва сайту, і архіви тканин та
	 * проєктів отримували <title>Scala</title> замість власної назви.
	 */
	return '';
}

/**
 * Опис сторінки для сніпета.
 *
 * Тільки справжній текст сторінки: лід, вступ виду штор, анонс запису.
 * Нічого не вигадуємо — опис має збігатися з тим, що людина побачить,
 * інакше пошуковик його перепише, а відвідувач відчує підміну.
 *
 * @return string
 */
function scala_meta_description(): string {
	if ( is_front_page() ) {
		$text = (string) scala_opt( 'sub', '' );

		return scala_trim_text( $text ?: (string) get_bloginfo( 'description' ) );
	}

	if ( is_post_type_archive( 'scala_type' ) ) {
		return scala_trim_text( scala_types_summary() );
	}

	if ( is_post_type_archive( 'scala_project' ) ) {
		return scala_trim_text( scala_archive_summary( 'scala_project', __( 'Реалізовані проєкти SCALA', 'scala' ) ) );
	}

	if ( is_post_type_archive( 'scala_fabric' ) ) {
		return scala_trim_text( scala_archive_summary( 'scala_fabric', __( 'Тканини, з якими працюємо', 'scala' ) ) );
	}

	if ( is_home() ) {
		$blog_id = (int) get_option( 'page_for_posts' );
		$text    = $blog_id ? (string) get_post_field( 'post_excerpt', $blog_id ) : '';

		return scala_trim_text( $text ?: __( 'Матеріали про вибір штор: види конструкцій, тканини, заміри й догляд.', 'scala' ) );
	}

	if ( is_singular( 'scala_type' ) ) {
		$id   = get_queried_object_id();
		$text = (string) scala_meta( $id, 'lead', '' );
		$text = $text ?: (string) scala_meta( $id, 'short', '' );

		if ( $text ) {
			return scala_trim_text( $text );
		}
	}

	if ( is_singular( array( 'scala_project', 'scala_fabric' ) ) ) {
		$id   = get_queried_object_id();
		$text = (string) scala_meta( $id, 'short', '' );
		$text = $text ?: (string) get_the_excerpt( $id );

		if ( $text ) {
			return scala_trim_text( $text );
		}
	}

	$leads = array(
		'template-catalog.php'  => 'catalog_lead',
		'template-about.php'    => 'about_lead',
		'template-contacts.php' => 'contacts_lead',
	);

	foreach ( $leads as $template => $option ) {
		if ( is_page_template( $template ) ) {
			$text = (string) scala_opt( $option, '' );

			if ( $text ) {
				return scala_trim_text( $text );
			}
		}
	}

	if ( is_singular() ) {
		$post = get_post();

		if ( $post ) {
			$text = has_excerpt( $post )
				? (string) get_the_excerpt( $post )
				: strip_shortcodes( (string) $post->post_content );

			$text = trim( $text );

			if ( $text ) {
				return scala_trim_text( $text );
			}
		}
	}

	/*
	 * Свого опису для решти контекстів у теми немає. Вигадувати його
	 * з гасла сайту не варто: один і той самий рядок на десятку
	 * різних адрес — це не опис. Порожньо означає «хай вирішує Yoast».
	 */
	return '';
}

/**
 * Чи є сторінка порожньою по суті.
 *
 * Проєкт і тканина — це картка з фотографією: заголовок, знімок і,
 * якщо заповнили, короткий опис. Без опису показувати таку сторінку
 * в пошуку нема сенсу: людина прийде на голу картинку, а Google
 * порахує її тонкою і знизить довіру до всього сайту.
 *
 * Щойно для запису напишуть опис — він одразу стає повноцінною
 * сторінкою й повертається в пошук. Нічого перемикати вручну не треба.
 *
 * @param int $post_id ID запису.
 * @return bool
 */
function scala_is_thin( int $post_id ): bool {
	if ( ! in_array( get_post_type( $post_id ), array( 'scala_project', 'scala_fabric' ), true ) ) {
		return false;
	}

	$text = (string) scala_meta( $post_id, 'short', '' );
	$text = $text ?: (string) get_post_field( 'post_excerpt', $post_id );
	$text = $text ?: (string) get_post_field( 'post_content', $post_id );

	return mb_strlen( trim( wp_strip_all_tags( $text ) ) ) < 80;
}

/**
 * Опис архіву з назв того, що в ньому лежить.
 *
 * @param string $post_type Тип запису.
 * @param string $lead      Початок речення.
 * @return string
 */
function scala_archive_summary( string $post_type, string $lead ): string {
	$names = array();

	foreach ( scala_posts( $post_type ) as $one ) {
		$names[] = get_the_title( $one );
	}

	$names = array_slice( array_values( array_filter( $names ) ), 0, 8 );

	if ( ! $names ) {
		return $lead . '.';
	}

	return $lead . ': ' . implode( ', ', $names ) . '.';
}

/**
 * Перелік видів штор одним реченням — опис архіву.
 *
 * @return string
 */
function scala_types_summary(): string {
	$names = array();

	foreach ( scala_posts( 'scala_type' ) as $one ) {
		$names[] = get_the_title( $one );
	}

	$names = array_slice( array_filter( $names ), 0, 6 );

	if ( ! $names ) {
		return (string) scala_opt( 'sub', '' );
	}

	/* translators: %s — перелік видів штор */
	return sprintf( __( 'Що ми шиємо: %s. ', 'scala' ), implode( ', ', $names ) )
		. (string) scala_opt( 'sub', '' );
}

/**
 * Опис конкретного запису — поза поточним запитом.
 *
 * Потрібен, щоб заповнити порожнє поле опису в SEO-плагіні: там має
 * стояти той самий текст, який тема й так показує в розмітці, інакше
 * в редакторі здається, ніби нічого не заповнено.
 *
 * @param int $post_id ID запису.
 * @return string
 */
function scala_description_for( int $post_id ): string {
	$type = (string) get_post_type( $post_id );

	if ( 'scala_type' === $type ) {
		$text = (string) scala_meta( $post_id, 'lead', '' );
		$text = $text ?: (string) scala_meta( $post_id, 'short', '' );
	} elseif ( in_array( $type, array( 'scala_project', 'scala_fabric' ), true ) ) {
		$text = (string) scala_meta( $post_id, 'short', '' );
	} else {
		$text = '';
	}

	if ( ! $text ) {
		$text = (string) get_post_field( 'post_excerpt', $post_id );
	}

	/*
	 * Сторінки з власним шаблоном — «Каталог», «Про бренд», «Контакти» —
	 * тримають текст у налаштуваннях теми, а поле вмісту в них порожнє.
	 * Без цього опис для них не збирався взагалі.
	 */
	if ( ! $text ) {
		$leads = array(
			'template-catalog.php'  => 'catalog_lead',
			'template-about.php'    => 'about_lead',
			'template-contacts.php' => 'contacts_lead',
		);

		$template = (string) get_page_template_slug( $post_id );

		if ( isset( $leads[ $template ] ) ) {
			$text = (string) scala_opt( $leads[ $template ], '' );
		}
	}

	if ( ! $text ) {
		$text = strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) );
	}

	if ( ! $text && (int) get_option( 'page_on_front' ) === $post_id ) {
		$text = (string) scala_opt( 'sub', '' );
	}

	/*
	 * Проєкт без власного опису: збираємо зі згадки виду штор, який на
	 * ньому показано. Нічого про сам обʼєкт не вигадуємо — беремо опис
	 * конструкції зі сторінки цього виду.
	 */
	if ( ! $text && 'scala_project' === $type ) {
		$text = scala_project_fallback_text( $post_id );
	}

	return scala_trim_text( $text );
}

/**
 * Запасний опис проєкту з назви й виду штор на ньому.
 *
 * @param int $post_id ID проєкту.
 * @return string
 */
function scala_project_fallback_text( int $post_id ): string {
	$name = get_the_title( $post_id );
	$city = trim( wp_strip_all_tags( (string) scala_opt( 'city', '' ) ) );

	foreach ( scala_posts( 'scala_type' ) as $type ) {
		$stem = mb_substr( mb_strtolower( get_the_title( $type ) ), 0, 5 );

		if ( '' === $stem || false === mb_strpos( mb_strtolower( $name ), $stem ) ) {
			continue;
		}

		$short = trim( wp_strip_all_tags( (string) scala_meta( $type->ID, 'short', '' ) ) );

		if ( $short ) {
			/* translators: 1 — назва проєкту, 2 — опис виду штор */
			return sprintf( __( '%1$s — реалізований проєкт SCALA. %2$s', 'scala' ), $name, $short );
		}
	}

	return $city
		/* translators: 1 — назва проєкту, 2 — місто */
		? sprintf( __( '%1$s — реалізований проєкт ательє SCALA: пошиття, карниз і монтаж під конкретні вікна, %2$s.', 'scala' ), $name, $city )
		/* translators: %s — назва проєкту */
		: sprintf( __( '%s — реалізований проєкт ательє SCALA: пошиття, карниз і монтаж під конкретні вікна.', 'scala' ), $name );
}

/**
 * Заголовок конкретного запису — поза поточним запитом.
 *
 * Той самий, що тема віддає в розмітку. Потрібен, щоб покласти його в
 * поле SEO-плагіна: інакше в редакторі показується заготовка плагіна,
 * а на сайті стоїть інший рядок, і зрозуміти, що побачить людина в
 * пошуку, неможливо.
 *
 * @param int $post_id ID запису.
 * @return string
 */
function scala_title_for( int $post_id ): string {
	$site = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
	$sep  = ' — ';
	$type = (string) get_post_type( $post_id );
	$name = get_the_title( $post_id );

	if ( (int) get_option( 'page_on_front' ) === $post_id ) {
		$h1 = trim( wp_strip_all_tags( (string) scala_opt( 'h1', '' ) ) );

		return $h1 ? $h1 . $sep . $site : $site;
	}

	if ( 'scala_type' === $type ) {
		return trim( $name . ' ' . scala_geo_phrase() ) . $sep . $site;
	}

	if ( 'scala_project' === $type ) {
		return $name . $sep . __( 'реалізований проєкт', 'scala' ) . ', ' . $site;
	}

	if ( 'scala_fabric' === $type ) {
		return $name . $sep . __( 'тканина для штор', 'scala' ) . ', ' . $site;
	}

	if ( 'page' === $type ) {
		$template = (string) get_page_template_slug( $post_id );

		if ( 'template-catalog.php' === $template ) {
			return trim( $name . ' ' . __( 'тканин і видів штор', 'scala' ) ) . $sep . $site;
		}

		if ( 'template-contacts.php' === $template ) {
			$city = trim( wp_strip_all_tags( (string) scala_opt( 'city', '' ) ) );

			return $name . $sep . $site . ( $city ? ', ' . $city : '' );
		}
	}

	return $name . $sep . $site;
}

/**
 * Ключова фраза сторінки для аналізу в SEO-плагіні.
 *
 * Береться із заголовка без хвоста «на замовлення в Києві» й без назви
 * сайту: у пошуку люди набирають саме ядро фрази. Це відправна точка —
 * у редакторі її можна замінити на будь-яку іншу.
 *
 * @param int $post_id ID запису.
 * @return string
 */
function scala_keyphrase_for( int $post_id ): string {
	// Головна зветься «Головна» — ключова фраза в неї з H1.
	if ( (int) get_option( 'page_on_front' ) === $post_id ) {
		$h1 = trim( wp_strip_all_tags( (string) scala_opt( 'h1', '' ) ) );

		return $h1 ? mb_strtolower( $h1 ) : '';
	}

	/*
	 * Сторінки, які тема створює сама, оголошують фразу в заготовці:
	 * саме під неї писався текст, і вона не завжди дорівнює заголовку.
	 */
	if ( function_exists( 'scala_guide_seed_data' ) ) {
		$slug = (string) get_post_field( 'post_name', $post_id );

		foreach ( scala_guide_seed_data() as $guide ) {
			if ( $slug === ( $guide['slug'] ?? '' ) && ! empty( $guide['keyphrase'] ) ) {
				return (string) $guide['keyphrase'];
			}
		}
	}

	/*
	 * Де назва на сайті й запит у пошуку розходяться. «Класичні штори»
	 * ніхто не набирає — набирають «портьєри»; сторінку японських
	 * панелей шукають як «японські штори».
	 */
	$known = array(
		'portyery'          => 'портьєри на замовлення',
		'rulonni-shtory'    => 'рулонні штори',
		'yaponski-paneli'   => 'японські штори',
		'zhalyuzi'          => 'дерев\'яні жалюзі',
		'rymski-shtory'     => 'римські штори',
		'avstrijski-shtory' => 'австрійські штори',
		'blekaut'           => 'штори блекаут',
		'tyul'              => 'тюль на замовлення',
	);

	$slug = (string) get_post_field( 'post_name', $post_id );

	if ( isset( $known[ $slug ] ) ) {
		return $known[ $slug ];
	}

	$by_template = array(
		'template-catalog.php'  => __( 'каталог тканин для штор', 'scala' ),
		'template-about.php'    => __( 'ательє штор scala', 'scala' ),
		'template-contacts.php' => __( 'замовити штори в києві', 'scala' ),
	);

	$template = (string) get_page_template_slug( $post_id );

	if ( isset( $by_template[ $template ] ) ) {
		return $by_template[ $template ];
	}

	$name = wp_strip_all_tags( get_the_title( $post_id ) );
	$name = preg_replace( '~\s*(на замовлення|під ключ)?\s*(в|у)\s+Києві\s*$~ui', '', $name );
	$name = preg_replace( '~\s*[—–-]\s*' . preg_quote( (string) get_bloginfo( 'name' ), '~' ) . '\s*$~ui', '', (string) $name );
	$name = trim( (string) $name, " \t\n—–-:,." );

	return mb_strtolower( $name );
}

/**
 * Ріже текст під сніпет: по межі слова, без обірваних слів.
 *
 * @param string $text Сирий текст, можливо з тегами.
 * @param int    $max  Скільки символів лишити.
 * @return string
 */
function scala_trim_text( string $text, int $max = 155 ): string {
	$text = wp_strip_all_tags( $text );
	$text = wp_specialchars_decode( $text, ENT_QUOTES );
	$text = trim( preg_replace( '~\s+~u', ' ', $text ) );

	if ( '' === $text || mb_strlen( $text ) <= $max ) {
		return $text;
	}

	$cut = mb_substr( $text, 0, $max );

	/*
	 * Краще закінчити реченням, ніж трьома крапками посеред думки:
	 * «підбере тканину у…» в сніпеті виглядає як обрив, а закінчена
	 * фраза читається як опис.
	 */
	$stop = 0;

	foreach ( array( '. ', '! ', '? ', '.' ) as $mark ) {
		$at = mb_strrpos( $cut, $mark );

		if ( false !== $at && $at > $stop ) {
			$stop = $at;
		}
	}

	if ( $stop > $max * 0.55 ) {
		return mb_substr( $cut, 0, $stop + 1 );
	}

	$space = mb_strrpos( $cut, ' ' );

	if ( false !== $space && $space > $max * 0.6 ) {
		$cut = mb_substr( $cut, 0, $space );
	}

	/*
	 * Саме preg_replace, а не rtrim: другий аргумент rtrim — набір
	 * БАЙТІВ, і тире (E2 80 94) додає в нього байти 80 та 94. А це
	 * закінчення «р» (D1 80) і «є» (D1 54) — rtrim відкушував у них
	 * останній байт, рядок ставав битим UTF-8, і WordPress викидав
	 * такий опис цілком: у розмітці лишався порожній description.
	 */
	return preg_replace( '~[\s,.;:—–-]+$~u', '', $cut ) . '…';
}

/**
 * Заголовок, який людина вписала в Yoast для конкретної сторінки.
 *
 * Такий заголовок завжди має перевагу: його писали свідомо, і тема
 * не має права його перебивати.
 *
 * @param string $key Ключ поля Yoast без префікса.
 * @return string
 */
function scala_yoast_manual( string $key ): string {
	if ( ! is_singular() ) {
		return '';
	}

	$id = get_queried_object_id();

	if ( ! $id ) {
		return '';
	}

	return trim( (string) get_post_meta( $id, '_yoast_wpseo_' . $key, true ) );
}

/**
 * Підставляє наш заголовок замість «Сторінка - Сайт».
 *
 * @param mixed $title Заголовок, який порахували до нас.
 * @return mixed
 */
function scala_filter_document_title( $title ) {
	if ( '' !== scala_yoast_manual( 'title' ) ) {
		return $title;
	}

	$ours = scala_document_title();

	return $ours ?: $title;
}
add_filter( 'pre_get_document_title', 'scala_filter_document_title', 20 );
add_filter( 'wpseo_title', 'scala_filter_document_title', 20 );

/**
 * Той самий заголовок для Open Graph.
 *
 * @param mixed $title Заголовок від Yoast.
 * @return mixed
 */
function scala_filter_og_title( $title ) {
	if ( '' !== scala_yoast_manual( 'opengraph-title' ) || '' !== scala_yoast_manual( 'title' ) ) {
		return $title;
	}

	$ours = scala_document_title();

	return $ours ?: $title;
}
add_filter( 'wpseo_opengraph_title', 'scala_filter_og_title', 20 );

/**
 * Опис для Yoast — тільки коли свого опису немає.
 *
 * Якщо в Yoast заповнений опис сторінки або шаблон описів, лишаємо
 * його: людина або налаштування важливіші за наш автоматичний текст.
 *
 * @param mixed $desc Опис, порахований Yoast.
 * @return mixed
 */
function scala_filter_metadesc( $desc ) {
	if ( is_string( $desc ) && '' !== trim( $desc ) ) {
		return $desc;
	}

	$ours = scala_meta_description();

	return $ours ?: $desc;
}
add_filter( 'wpseo_metadesc', 'scala_filter_metadesc', 20 );
add_filter( 'wpseo_opengraph_desc', 'scala_filter_metadesc', 20 );
add_filter( 'wpseo_twitter_description', 'scala_filter_metadesc', 20 );

/**
 * Просимо браузер почати вантажити головне фото одразу.
 *
 * Це найбільший елемент першого екрана, і саме за ним Google міряє
 * LCP. Без підказки браузер доходить до нього аж коли розбере всю
 * розмітку; посилання в <head> дає йому фору. Список розмірів той
 * самий, що й у самого зображення, тож завантажується рівно той файл,
 * який і так буде показано.
 *
 * @return void
 */
function scala_preload_hero(): void {
	if ( ! is_front_page() ) {
		return;
	}

	$id = (int) scala_opt( 'room_image', 0 );

	if ( ! $id ) {
		return;
	}

	$src = wp_get_attachment_image_url( $id, 'scala-lg' );

	if ( ! $src ) {
		return;
	}

	$srcset = wp_get_attachment_image_srcset( $id, 'scala-lg' );

	printf(
		'<link rel="preload" as="image" href="%s"%s imagesizes="100vw" fetchpriority="high" />' . "\n",
		esc_url( $src ),
		$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : ''
	);
}
add_action( 'wp_head', 'scala_preload_hero', 1 );

/**
 * Картинка для соцмереж.
 *
 * @return string
 */
function scala_share_image(): string {
	if ( is_singular() && has_post_thumbnail() ) {
		return (string) get_the_post_thumbnail_url( null, 'scala-lg' );
	}

	$hero = (int) scala_opt( 'room_image', 0 );

	return $hero ? scala_image_url( $hero, 'scala-lg' ) : '';
}

/**
 * Чим саме займається ательє — для розмітки організації.
 *
 * Без адреси (її в ательє немає) головні орієнтири для пошуковика й
 * ШІ-асистента — телефон, місто обслуговування, соцмережі й перелік
 * того, що ми робимо. Усе береться з налаштувань: у розмітку не
 * потрапляє нічого, чого немає на сторінці контактів.
 *
 * @return array
 */
function scala_business_props(): array {
	$props = array();

	$phone = scala_tel( (string) scala_opt( 'phone', '' ) );
	if ( $phone ) {
		$props['telephone'] = $phone;
	}

	$email = (string) scala_opt( 'email', '' );
	if ( $email ) {
		$props['email'] = $email;
	}

	$city = trim( wp_strip_all_tags( (string) scala_opt( 'city', '' ) ) );
	if ( $city ) {
		$props['areaServed'] = array(
			'@type' => 'City',
			'name'  => $city,
		);
	}

	if ( $phone ) {
		$contact = array(
			'@type'             => 'ContactPoint',
			// Службове значення зі словника Google, не текст для людини.
			'contactType'       => 'sales',
			'telephone'         => $phone,
			'availableLanguage' => array( 'uk' ),
		);

		if ( $city ) {
			$contact['areaServed'] = $city;
		}

		$props['contactPoint'] = $contact;
	}

	$social = array_filter(
		array(
			(string) scala_opt( 'instagram', '' ),
			(string) scala_opt( 'telegram', '' ),
			(string) scala_opt( 'whatsapp', '' ),
		)
	);

	if ( $social ) {
		$props['sameAs'] = array_values( $social );
	}

	$knows = array();
	foreach ( scala_posts( 'scala_type' ) as $type ) {
		$knows[] = get_the_title( $type );
	}

	$knows = array_values( array_filter( $knows ) );
	if ( $knows ) {
		$props['knowsAbout'] = $knows;
	}

	return $props;
}

/**
 * Види штор як перелік послуг.
 *
 * @return array|null
 */
function scala_offer_catalog(): ?array {
	$offers = array();

	foreach ( scala_posts( 'scala_type' ) as $type ) {
		$offers[] = array(
			'@type'       => 'Offer',
			'itemOffered' => array(
				'@type' => 'Service',
				'name'  => get_the_title( $type ),
				'url'   => get_permalink( $type ),
			),
		);
	}

	if ( ! $offers ) {
		return null;
	}

	return array(
		'@type'           => 'OfferCatalog',
		'name'            => __( 'Види штор', 'scala' ),
		'itemListElement' => $offers,
	);
}

/**
 * Доповнює картку організації, яку малює Yoast.
 *
 * Сам Yoast знає лише назву, адресу сайту й логотип. Ні телефона, ні
 * міста, ні того, що ательє взагалі шиє, у розмітці немає — а саме це
 * читають пошуковик і ШІ-асистент, вирішуючи, кого порадити в Києві.
 * Своєї організації не додаємо: дві на сторінці сперечалися б між
 * собою. Те, що Yoast уже заповнив, не чіпаємо.
 *
 * @param mixed $data Дані вузла Organization.
 * @return mixed
 */
function scala_enrich_yoast_organization( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	foreach ( scala_business_props() as $key => $value ) {
		if ( 'sameAs' === $key ) {
			$saved = isset( $data['sameAs'] ) && is_array( $data['sameAs'] ) ? $data['sameAs'] : array();

			$data['sameAs'] = array_values( array_unique( array_merge( $saved, $value ) ) );
			continue;
		}

		if ( empty( $data[ $key ] ) ) {
			$data[ $key ] = $value;
		}
	}

	// Повний перелік послуг доречний на головній, а не на кожній сторінці.
	if ( is_front_page() && empty( $data['hasOfferCatalog'] ) ) {
		$catalog = scala_offer_catalog();

		if ( $catalog ) {
			$data['hasOfferCatalog'] = $catalog;
		}
	}

	return $data;
}
add_filter( 'wpseo_schema_organization', 'scala_enrich_yoast_organization', 20 );

/**
 * Структуровані дані головної сторінки.
 *
 * FAQ виводимо навіть за активного Yoast: питання зберігаються в
 * окремому типі записів, і Yoast про них не знає.
 *
 * @return void
 */
function scala_json_ld(): void {
	if ( ! is_front_page() ) {
		return;
	}

	$graph = array();
	$home  = trailingslashit( home_url( '/' ) );

	if ( ! scala_seo_plugin_active() ) {
		$business = array_merge(
			array(
				'@type'       => array( 'LocalBusiness', 'HomeAndConstructionBusiness' ),
				'@id'         => $home . '#business',
				'name'        => get_bloginfo( 'name' ),
				'url'         => $home,
				'description' => scala_meta_description(),
			),
			scala_business_props()
		);

		$image = scala_share_image();
		if ( $image ) {
			$business['image'] = $image;
		}

		// Адреса зʼявиться в розмітці лише коли її заповнять в адмінці.
		$address = (string) scala_opt( 'address', '' );
		if ( $address ) {
			$business['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $address,
				'addressLocality' => (string) scala_opt( 'city', '' ) ?: 'Київ',
				'addressCountry'  => 'UA',
			);
		}

		$catalog = scala_offer_catalog();
		if ( $catalog ) {
			$business['hasOfferCatalog'] = $catalog;
		}

		$graph[] = $business;

		$graph[] = array(
			'@type'      => 'WebSite',
			'@id'        => $home . '#website',
			'url'        => $home,
			'name'       => get_bloginfo( 'name' ),
			'inLanguage' => get_bloginfo( 'language' ),
			'publisher'  => array( '@id' => $home . '#business' ),
		);
	}

	// FAQ — з реальних питань на сторінці, слово в слово.
	$faq_items = array();
	foreach ( scala_posts( 'scala_faq' ) as $faq ) {
		$answer = wp_strip_all_tags( (string) $faq->post_content );

		if ( ! $answer ) {
			continue;
		}

		$faq_items[] = array(
			'@type'          => 'Question',
			'name'           => get_the_title( $faq ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	if ( $faq_items ) {
		$graph[] = array(
			'@type'      => 'FAQPage',
			'@id'        => $home . '#faq',
			'mainEntity' => $faq_items,
		);
	}

	if ( ! $graph ) {
		return;
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		)
	);
}
add_action( 'wp_head', 'scala_json_ld', 5 );

/**
 * Структуровані дані сторінки виду штор.
 *
 * Хлібні крихти дублюють видиму навігацію, а FAQ береться з тих самих
 * полів запису, які людина бачить на сторінці. Розійтися вони не
 * можуть за побудовою: джерело одне.
 *
 * Виводимо навіть за активного Yoast — він не знає ні про наші поля
 * питань, ні про архів /vydy-shtor/ як окремий рівень навігації.
 *
 * @return void
 */
function scala_type_json_ld(): void {
	if ( ! is_singular( 'scala_type' ) ) {
		return;
	}

	$post_id = get_the_ID();

	if ( ! $post_id ) {
		return;
	}

	$graph   = array();
	$archive = get_post_type_archive_link( 'scala_type' );

	$crumbs = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => __( 'Головна', 'scala' ),
			'item'     => home_url( '/' ),
		),
	);

	if ( $archive ) {
		$crumbs[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => __( 'Види штор', 'scala' ),
			'item'     => $archive,
		);
	}

	$crumbs[] = array(
		'@type'    => 'ListItem',
		'position' => count( $crumbs ) + 1,
		'name'     => get_the_title( $post_id ),
		'item'     => (string) get_permalink( $post_id ),
	);

	$graph[] = array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $crumbs,
	);

	/*
	 * Послуга окремим вузлом: «Рулонні системи» — це не товар з ціною,
	 * а робота під замовлення в конкретному місті. Саме так її має
	 * читати пошуковик і ШІ-асистент, якого питають про Київ.
	 */
	$service = array(
		'@type'       => 'Service',
		'@id'         => get_permalink( $post_id ) . '#service',
		'name'        => get_the_title( $post_id ),
		'serviceType' => get_the_title( $post_id ),
		'url'         => (string) get_permalink( $post_id ),
		'provider'    => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
	);

	$service_desc = scala_meta_description();
	if ( $service_desc ) {
		$service['description'] = $service_desc;
	}

	$service_city = trim( wp_strip_all_tags( (string) scala_opt( 'city', '' ) ) );
	if ( $service_city ) {
		$service['areaServed'] = array(
			'@type' => 'City',
			'name'  => $service_city,
		);
	}

	$service_image = scala_share_image();
	if ( $service_image ) {
		$service['image'] = $service_image;
	}

	$graph[] = $service;

	$faq_items = array();

	foreach ( scala_meta_rows( (int) $post_id, 'faq' ) as $row ) {
		$question = trim( wp_strip_all_tags( (string) ( $row['q'] ?? '' ) ) );
		$answer   = trim( wp_strip_all_tags( (string) ( $row['a'] ?? '' ) ) );

		if ( ! $question || ! $answer ) {
			continue;
		}

		$faq_items[] = array(
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	if ( $faq_items ) {
		$graph[] = array(
			'@type'      => 'FAQPage',
			'@id'        => get_permalink( $post_id ) . '#faq',
			'mainEntity' => $faq_items,
		);
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		)
	);
}
add_action( 'wp_head', 'scala_type_json_ld', 6 );

/**
 * Тонку сторінку ховаємо від пошуку.
 *
 * @param mixed $robots Значення від Yoast: рядок або масив.
 * @return mixed
 */
function scala_filter_robots( $robots ) {
	// Позначка, що плагін свій тег усе-таки друкує — щоб не додати другий.
	$GLOBALS['scala_robots_printed'] = true;

	if ( ! is_singular() || ! scala_is_thin( get_queried_object_id() ) ) {
		return $robots;
	}

	if ( is_array( $robots ) ) {
		$robots['index'] = 'noindex';

		return $robots;
	}

	return 'noindex, follow';
}
add_filter( 'wpseo_robots', 'scala_filter_robots', 20 );
add_filter( 'wpseo_robots_array', 'scala_filter_robots', 20 );

/**
 * Ставить noindex сам, якщо плагін цього не зробив.
 *
 * Yoast на цьому сайті тег robots не друкує взагалі, тож фільтрувати
 * нічого — фільтр просто не викликається. Тому друкуємо свій, але
 * пізнім пріоритетом: до цієї миті плагін уже висловився, і подвійного
 * тега не буде.
 *
 * @return void
 */
function scala_thin_noindex(): void {
	if ( ! empty( $GLOBALS['scala_robots_printed'] ) || ! is_singular() ) {
		return;
	}

	if ( scala_is_thin( get_queried_object_id() ) ) {
		echo '<meta name="robots" content="noindex, follow" />' . "\n";
	}
}
add_action( 'wp_head', 'scala_thin_noindex', 99 );

/**
 * Тонкі сторінки не потрапляють у карту сайту.
 *
 * @param array  $args      Аргументи запиту.
 * @param string $post_type Тип запису.
 * @return array
 */
function scala_sitemap_skip_thin( array $args, string $post_type ): array {
	if ( ! in_array( $post_type, array( 'scala_project', 'scala_fabric' ), true ) ) {
		return $args;
	}

	$ids = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	$thin = array_values( array_filter( $ids, 'scala_is_thin' ) );

	if ( $thin ) {
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $thin );
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'scala_sitemap_skip_thin', 10, 2 );

/**
 * Вбудована карта сайту WordPress: прибираємо зайве.
 *
 * Yoast-карта на цьому сайті вимкнена, тож працює вбудована — і вона
 * гарна, але за замовчуванням тягне в себе все публічне:
 *
 * - авторів: /author/admin/ і решту — це розкриває логіни адміністраторів
 *   і не має жодної пошукової цінності;
 * - службовий тип записів плагіна блоків (atfp_add_blocks);
 * - рубрики й мітки — одна рубрика на сім статей, тонка сторінка.
 *
 * Лишаються: сторінки, статті, види штор, тканини, проєкти.
 * Якщо колись увімкнуть карту Yoast, вона підмінить вбудовану сама,
 * і ці фільтри просто перестануть бути потрібні.
 */
add_filter(
	'wp_sitemaps_add_provider',
	static function ( $provider, string $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);

add_filter(
	'wp_sitemaps_post_types',
	static function ( array $types ): array {
		unset( $types['atfp_add_blocks'] );
		return $types;
	}
);

add_filter(
	'wp_sitemaps_taxonomies',
	static function ( array $taxonomies ): array {
		unset( $taxonomies['category'], $taxonomies['post_tag'] );
		return $taxonomies;
	}
);

/**
 * Сторінок авторів на сайті ательє не буває.
 *
 * /author/admin/ і /?author=1 — стандартний спосіб дізнатися логіни
 * для перебору паролів. Ведемо на головну.
 *
 * @return void
 */
function scala_no_author_pages(): void {
	if ( is_author() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'scala_no_author_pages', 1 );
