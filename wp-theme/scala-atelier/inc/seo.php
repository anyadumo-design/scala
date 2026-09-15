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
		return __( 'Журнал', 'scala' ) . $sep . $site;
	}

	return $site;
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

	if ( is_singular( 'scala_type' ) ) {
		$id   = get_queried_object_id();
		$text = (string) scala_meta( $id, 'lead', '' );
		$text = $text ?: (string) scala_meta( $id, 'short', '' );

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

	return scala_trim_text( (string) get_bloginfo( 'description' ) );
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
 * Ріже текст під сніпет: по межі слова, без обірваних слів.
 *
 * @param string $text Сирий текст, можливо з тегами.
 * @param int    $max  Скільки символів лишити.
 * @return string
 */
function scala_trim_text( string $text, int $max = 158 ): string {
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

	return rtrim( $cut, " ,.;:—-" ) . '…';
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
