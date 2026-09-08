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

/**
 * Опис сторінки для сніпета.
 *
 * @return string
 */
function scala_meta_description(): string {
	if ( is_front_page() ) {
		$text = (string) scala_opt( 'sub', '' );

		return $text ? wp_strip_all_tags( $text ) : (string) get_bloginfo( 'description' );
	}

	if ( is_singular() ) {
		$post = get_post();

		if ( $post ) {
			$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 28 );

			return trim( (string) $excerpt );
		}
	}

	return (string) get_bloginfo( 'description' );
}

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
		$business = array(
			'@type'       => array( 'LocalBusiness', 'HomeAndConstructionBusiness' ),
			'@id'         => $home . '#business',
			'name'        => get_bloginfo( 'name' ),
			'url'         => $home,
			'description' => scala_meta_description(),
		);

		$phone = scala_tel( (string) scala_opt( 'phone', '' ) );
		if ( $phone ) {
			$business['telephone'] = $phone;
		}

		$email = (string) scala_opt( 'email', '' );
		if ( $email ) {
			$business['email'] = $email;
		}

		$image = scala_share_image();
		if ( $image ) {
			$business['image'] = $image;
		}

		$city = (string) scala_opt( 'city', '' );
		if ( $city ) {
			$business['areaServed'] = array(
				'@type' => 'City',
				'name'  => $city,
			);
		}

		// Адреса зʼявиться в розмітці лише коли її заповнять в адмінці.
		$address = (string) scala_opt( 'address', '' );
		if ( $address ) {
			$business['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $address,
				'addressLocality' => $city ?: 'Київ',
				'addressCountry'  => 'UA',
			);
		}

		$social = array_filter(
			array(
				(string) scala_opt( 'instagram', '' ),
				(string) scala_opt( 'telegram', '' ),
				(string) scala_opt( 'whatsapp', '' ),
			)
		);

		if ( $social ) {
			$business['sameAs'] = array_values( $social );
		}

		// Види штор як перелік послуг.
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

		if ( $offers ) {
			$business['hasOfferCatalog'] = array(
				'@type'           => 'OfferCatalog',
				'name'            => __( 'Види штор', 'scala' ),
				'itemListElement' => $offers,
			);
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
