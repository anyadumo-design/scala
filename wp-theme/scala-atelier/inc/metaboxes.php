<?php
/**
 * Додаткові поля записів. Використовує той самий фреймворк, що й
 * сторінка налаштувань, тож поведінка полів усюди однакова.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Опис полів для кожного типу записів.
 *
 * @return array
 */
function scala_meta_schema(): array {
	return array(

		'scala_type' => array(
			'title'  => __( 'Дані виду штор', 'scala' ),
			'fields' => array(
				array(
					'key'     => 'tag',
					'type'    => 'text',
					'label'   => __( 'Позначка на картці', 'scala' ),
					'default' => 'Прорахунок →',
				),
				array(
					'key'   => 'short',
					'type'  => 'textarea',
					'rows'  => 2,
					'label' => __( 'Короткий опис (на картці)', 'scala' ),
					'help'  => __( 'Один-два рядки. Показується на головній і в каталозі.', 'scala' ),
				),
				array(
					'key'     => 'in_catalog',
					'type'    => 'checkbox',
					'label'   => __( 'Показувати в каталозі', 'scala' ),
					'default' => 1,
				),
				array(
					'key'     => 'on_home',
					'type'    => 'checkbox',
					'label'   => __( 'Показувати на головній', 'scala' ),
					'default' => 1,
				),
			),
		),

		'scala_fabric' => array(
			'title'  => __( 'Дані тканини', 'scala' ),
			'fields' => array(
				array(
					'key'     => 'tag',
					'type'    => 'text',
					'label'   => __( 'Позначка', 'scala' ),
					'default' => 'Колекція',
				),
				array(
					'key'       => 'gallery',
					'type'      => 'repeater',
					'label'     => __( 'Додаткові кадри', 'scala' ),
					'row_label' => __( 'Кадр', 'scala' ),
					'fields'    => array(
						array(
							'key'   => 'image',
							'type'  => 'image',
							'label' => __( 'Зображення', 'scala' ),
						),
						array(
							'key'   => 'alt',
							'type'  => 'text',
							'label' => __( 'Опис для пошуку (alt)', 'scala' ),
						),
					),
				),
				array(
					'key'   => 'video',
					'type'  => 'url',
					'label' => __( 'Відео тканини', 'scala' ),
					'help'  => __( 'Посилання на YouTube або Vimeo. Порожньо — блок відео не показується.', 'scala' ),
				),
				array(
					'key'     => 'on_home',
					'type'    => 'checkbox',
					'label'   => __( 'Показувати на головній', 'scala' ),
					'default' => 1,
				),
			),
		),

		'scala_project' => array(
			'title'  => __( 'Дані проєкту', 'scala' ),
			'fields' => array(
				array(
					'key'   => 'wide',
					'type'  => 'checkbox',
					'label' => __( 'Широка картка у стрічці', 'scala' ),
					'help'  => __( 'Зазвичай широким роблять лише перший кадр.', 'scala' ),
				),
				array(
					'key'   => 'alt',
					'type'  => 'text',
					'label' => __( 'Опис фото для пошуку (alt)', 'scala' ),
				),
			),
		),

		'scala_extra' => array(
			'title'  => __( 'Дані позиції', 'scala' ),
			'fields' => array(
				array(
					'key'     => 'tag',
					'type'    => 'text',
					'label'   => __( 'Позначка', 'scala' ),
					'default' => 'Комплектація',
				),
				array(
					'key'   => 'short',
					'type'  => 'textarea',
					'rows'  => 2,
					'label' => __( 'Короткий опис', 'scala' ),
				),
			),
		),

		'scala_review' => array(
			'title'  => __( 'Автор відгуку', 'scala' ),
			'fields' => array(
				array(
					'key'   => 'author',
					'type'  => 'text',
					'label' => __( 'Підпис', 'scala' ),
					'help'  => __( 'Наприклад: Клієнтка · оформлення кімнат «під ключ»', 'scala' ),
				),
			),
		),

		'scala_ig' => array(
			'title'  => __( 'Допис Instagram', 'scala' ),
			'fields' => array(
				array(
					'key'   => 'url',
					'type'  => 'url',
					'label' => __( 'Посилання на допис', 'scala' ),
					'help'  => __( 'Порожньо — плитка веде на профіль.', 'scala' ),
				),
				array(
					'key'   => 'alt',
					'type'  => 'text',
					'label' => __( 'Опис фото для пошуку (alt)', 'scala' ),
				),
			),
		),
	);
}

/**
 * Додає метабокси до відповідних типів записів.
 *
 * @return void
 */
function scala_add_meta_boxes(): void {
	foreach ( scala_meta_schema() as $post_type => $box ) {
		add_meta_box(
			'scala_meta_' . $post_type,
			$box['title'],
			'scala_render_meta_box',
			$post_type,
			'normal',
			'high'
		);
	}

	add_meta_box(
		'scala_lead_data',
		__( 'Дані заявки', 'scala' ),
		'scala_render_lead_box',
		'scala_lead',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'scala_add_meta_boxes' );

/**
 * Малює метабокс за схемою.
 *
 * @param WP_Post $post Запис.
 * @return void
 */
function scala_render_meta_box( $post ): void {
	$schema = scala_meta_schema();
	$fields = $schema[ $post->post_type ]['fields'] ?? array();

	if ( ! $fields ) {
		return;
	}

	wp_nonce_field( 'scala_save_meta', 'scala_meta_nonce' );

	$values = array();
	foreach ( $fields as $field ) {
		$stored = get_post_meta( $post->ID, '_scala_' . $field['key'], true );

		if ( '' === $stored && isset( $field['default'] ) && ! metadata_exists( 'post', $post->ID, '_scala_' . $field['key'] ) ) {
			$stored = $field['default'];
		}

		$values[ $field['key'] ] = $stored;
	}

	echo '<div class="scala-metabox">';
	scala_render_fields( $fields, $values, 'scala_meta' );
	echo '</div>';
}

/**
 * Зберігає поля метабоксів.
 *
 * @param int $post_id ID запису.
 * @return void
 */
function scala_save_meta( int $post_id ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['scala_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['scala_meta_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'scala_save_meta' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$post_type = get_post_type( $post_id );
	$schema    = scala_meta_schema();
	$fields    = $schema[ $post_type ]['fields'] ?? array();

	if ( ! $fields ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- чистить scala_sanitize_fields за схемою.
	$raw   = isset( $_POST['scala_meta'] ) ? wp_unslash( $_POST['scala_meta'] ) : array();
	$clean = scala_sanitize_fields( $fields, $raw );

	foreach ( $clean as $key => $value ) {
		update_post_meta( $post_id, '_scala_' . $key, $value );
	}
}
add_action( 'save_post', 'scala_save_meta' );

/**
 * Показує дані заявки тільки для читання.
 *
 * @param WP_Post $post Запис.
 * @return void
 */
function scala_render_lead_box( $post ): void {
	$rows = array(
		__( 'Імʼя', 'scala' )      => scala_meta( $post->ID, 'name', '—' ),
		__( 'Телефон', 'scala' )   => scala_meta( $post->ID, 'phone', '—' ),
		__( 'Потрібно', 'scala' )  => scala_meta( $post->ID, 'need', '—' ),
		__( 'Коментар', 'scala' )  => scala_meta( $post->ID, 'note', '—' ),
		__( 'Звідки', 'scala' )    => scala_meta( $post->ID, 'source_label', '—' ),
		__( 'Сторінка', 'scala' )  => scala_meta( $post->ID, 'source', '—' ),
		__( 'Отримано', 'scala' )  => get_the_date( 'd.m.Y H:i', $post ),
	);

	echo '<table class="widefat striped scala-lead">';

	foreach ( $rows as $label => $value ) {
		printf(
			'<tr><th style="width:160px">%s</th><td>%s</td></tr>',
			esc_html( $label ),
			esc_html( (string) $value )
		);
	}

	echo '</table>';

	$phone = scala_meta( $post->ID, 'phone', '' );

	if ( $phone ) {
		printf(
			'<p style="margin-top:12px"><a class="button button-primary" href="tel:%s">%s</a></p>',
			esc_attr( scala_tel( (string) $phone ) ),
			esc_html__( 'Подзвонити', 'scala' )
		);
	}
}

/**
 * Колонки списку заявок — щоб було видно телефон без відкривання.
 *
 * @param array $columns Наявні колонки.
 * @return array
 */
function scala_lead_columns( array $columns ): array {
	return array(
		'cb'           => $columns['cb'] ?? '',
		'title'        => __( 'Заявка', 'scala' ),
		'scala_phone'  => __( 'Телефон', 'scala' ),
		'scala_need'   => __( 'Потрібно', 'scala' ),
		'scala_from'   => __( 'Звідки', 'scala' ),
		'date'         => __( 'Отримано', 'scala' ),
	);
}
add_filter( 'manage_scala_lead_posts_columns', 'scala_lead_columns' );

/**
 * Вміст колонок списку заявок.
 *
 * @param string $column  Ключ колонки.
 * @param int    $post_id ID запису.
 * @return void
 */
function scala_lead_column_content( string $column, int $post_id ): void {
	if ( 'scala_phone' === $column ) {
		$phone = (string) scala_meta( $post_id, 'phone', '' );

		if ( $phone ) {
			printf(
				'<a href="tel:%s">%s</a>',
				esc_attr( scala_tel( $phone ) ),
				esc_html( $phone )
			);
		} else {
			echo '—';
		}
	}

	if ( 'scala_need' === $column ) {
		echo esc_html( (string) scala_meta( $post_id, 'need', '—' ) );
	}

	if ( 'scala_from' === $column ) {
		echo esc_html( (string) scala_meta( $post_id, 'source_label', '—' ) );
	}
}
add_action( 'manage_scala_lead_posts_custom_column', 'scala_lead_column_content', 10, 2 );
