<?php
/**
 * Типи записів.
 *
 * Види штор і тканини — публічні: у них є власні сторінки, під які
 * планується структура /vydy-shtor/ і /tkanyny/. Решта службова:
 * показується лише в адмінці й виводиться шаблонами.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Реєструє всі типи записів теми.
 *
 * @return void
 */
function scala_register_post_types(): void {

	/* ---- Види штор: тюль, портьєри, римські… ---------------------- */
	register_post_type(
		'scala_type',
		array(
			'labels'        => scala_cpt_labels(
				__( 'Види штор', 'scala' ),
				__( 'Вид штор', 'scala' )
			),
			'public'        => true,
			'has_archive'   => 'vydy-shtor',
			'rewrite'       => array(
				'slug'       => 'vydy-shtor',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-align-wide',
			'show_in_menu'  => 'scala-settings',
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest'  => true,
			'menu_position' => 5,
		)
	);

	/* ---- Тканини --------------------------------------------------- */
	register_post_type(
		'scala_fabric',
		array(
			'labels'       => scala_cpt_labels(
				__( 'Тканини', 'scala' ),
				__( 'Тканина', 'scala' )
			),
			'public'       => true,
			'has_archive'  => 'tkanyny',
			'rewrite'      => array(
				'slug'       => 'tkanyny',
				'with_front' => false,
			),
			'menu_icon'    => 'dashicons-art',
			'show_in_menu' => 'scala-settings',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest' => true,
		)
	);

	/* ---- Проєкти (портфоліо) --------------------------------------- */
	register_post_type(
		'scala_project',
		array(
			'labels'       => scala_cpt_labels(
				__( 'Проєкти', 'scala' ),
				__( 'Проєкт', 'scala' )
			),
			'public'       => true,
			'has_archive'  => 'roboty',
			'rewrite'      => array(
				'slug'       => 'roboty',
				'with_front' => false,
			),
			'menu_icon'    => 'dashicons-format-gallery',
			'show_in_menu' => 'scala-settings',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest' => true,
		)
	);

	/* ---- Комплектація: карнизи, підкладка (лише в каталозі) -------- */
	register_post_type(
		'scala_extra',
		array(
			'labels'       => scala_cpt_labels(
				__( 'Комплектація', 'scala' ),
				__( 'Позиція', 'scala' )
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'scala-settings',
			'menu_icon'    => 'dashicons-screenoptions',
			'supports'     => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
		)
	);

	/* ---- Відгуки --------------------------------------------------- */
	register_post_type(
		'scala_review',
		array(
			'labels'       => scala_cpt_labels(
				__( 'Відгуки', 'scala' ),
				__( 'Відгук', 'scala' )
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'scala-settings',
			'menu_icon'    => 'dashicons-format-quote',
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
		)
	);

	/* ---- Часті питання --------------------------------------------- */
	register_post_type(
		'scala_faq',
		array(
			'labels'       => scala_cpt_labels(
				__( 'Часті питання', 'scala' ),
				__( 'Питання', 'scala' )
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'scala-settings',
			'menu_icon'    => 'dashicons-editor-help',
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
		)
	);

	/* ---- Instagram: фото + посилання на допис ---------------------- */
	register_post_type(
		'scala_ig',
		array(
			'labels'       => scala_cpt_labels(
				__( 'Instagram', 'scala' ),
				__( 'Допис', 'scala' )
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'scala-settings',
			'menu_icon'    => 'dashicons-instagram',
			'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);

	/* ---- Заявки з форми (тільки читання) --------------------------- */
	register_post_type(
		'scala_lead',
		array(
			'labels'       => scala_cpt_labels(
				__( 'Заявки', 'scala' ),
				__( 'Заявка', 'scala' )
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'scala-settings',
			'menu_icon'    => 'dashicons-email-alt',
			'supports'     => array( 'title' ),
			/*
			 * Заявки містять персональні дані клієнтів. З типовими правами
			 * типу «post» будь-який Редактор бачив би імена й телефони всіх
			 * звернень і міг їх видаляти. Зводимо всі права до тієї ж
			 * можливості, що відкриває налаштування теми.
			 */
			'capability_type' => array( 'scala_lead', 'scala_leads' ),
			'map_meta_cap'    => true,
			'capabilities'    => array(
				'create_posts'           => 'do_not_allow',
				'edit_post'              => 'edit_theme_options',
				'read_post'              => 'edit_theme_options',
				'delete_post'            => 'edit_theme_options',
				'edit_posts'             => 'edit_theme_options',
				'edit_others_posts'      => 'edit_theme_options',
				'edit_published_posts'   => 'edit_theme_options',
				'publish_posts'          => 'edit_theme_options',
				'read_private_posts'     => 'edit_theme_options',
				'delete_posts'           => 'edit_theme_options',
				'delete_others_posts'    => 'edit_theme_options',
				'delete_published_posts' => 'edit_theme_options',
				'delete_private_posts'   => 'edit_theme_options',
				'edit_private_posts'     => 'edit_theme_options',
			),
		)
	);
}
add_action( 'init', 'scala_register_post_types' );

/**
 * Складає підписи типу запису.
 *
 * @param string $plural   Множина.
 * @param string $singular Однина.
 * @return array
 */
function scala_cpt_labels( string $plural, string $singular ): array {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'menu_name'          => $plural,
		'add_new'            => __( 'Додати', 'scala' ),
		/* translators: %s — назва в однині */
		'add_new_item'       => sprintf( __( 'Додати: %s', 'scala' ), $singular ),
		/* translators: %s — назва в однині */
		'edit_item'          => sprintf( __( 'Редагувати: %s', 'scala' ), $singular ),
		/* translators: %s — назва в однині */
		'new_item'           => sprintf( __( 'Нова позиція: %s', 'scala' ), $singular ),
		/* translators: %s — назва в однині */
		'view_item'          => sprintf( __( 'Переглянути: %s', 'scala' ), $singular ),
		/* translators: %s — назва в множині */
		'search_items'       => sprintf( __( 'Шукати: %s', 'scala' ), $plural ),
		'not_found'          => __( 'Поки порожньо', 'scala' ),
		'not_found_in_trash' => __( 'У кошику порожньо', 'scala' ),
		'all_items'          => $plural,
	);
}

/**
 * Сортування в адмінці — за menu_order, як на сайті.
 *
 * @param WP_Query $query Запит.
 * @return void
 */
function scala_admin_order( $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$post_type = $query->get( 'post_type' );

	if ( is_string( $post_type ) && str_starts_with( $post_type, 'scala_' ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'scala_admin_order' );

/**
 * Разово оновлює правила посилань після активації теми.
 *
 * @return void
 */
function scala_maybe_flush_rewrites(): void {
	if ( get_option( 'scala_rewrites_flushed' ) === SCALA_VERSION ) {
		return;
	}

	scala_register_post_types();
	flush_rewrite_rules();
	update_option( 'scala_rewrites_flushed', SCALA_VERSION );
}
add_action( 'init', 'scala_maybe_flush_rewrites', 99 );
