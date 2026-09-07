<?php
/**
 * Фреймворк полів: рендер і санітизація.
 *
 * Одне джерело правди — масив опису полів. З нього будуються і сторінка
 * налаштувань, і метабокси записів, і перевірка збережених значень.
 *
 * Опис поля:
 *   key       — ключ у масиві значень
 *   type      — text | textarea | url | tel | email | number | select | checkbox | image | repeater
 *   label     — підпис
 *   help      — пояснення під полем
 *   default   — значення за замовчуванням
 *   options   — [значення => підпис] для select
 *   fields    — вкладені поля для repeater
 *   row_label — як називати рядок репітера
 *   rows      — висота textarea
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Малює список полів.
 *
 * @param array  $fields    Опис полів.
 * @param array  $values    Поточні значення.
 * @param string $name_base Базове імʼя для атрибута name.
 * @return void
 */
function scala_render_fields( array $fields, array $values, string $name_base ): void {
	foreach ( $fields as $field ) {
		$key   = $field['key'];
		$value = $values[ $key ] ?? ( $field['default'] ?? '' );

		scala_render_field( $field, $value, $name_base . '[' . $key . ']' );
	}
}

/**
 * Малює одне поле разом із підписом.
 *
 * @param array  $field Опис поля.
 * @param mixed  $value Поточне значення.
 * @param string $name  Атрибут name.
 * @return void
 */
function scala_render_field( array $field, $value, string $name ): void {
	$type = $field['type'] ?? 'text';

	// Підкреслення лишаємо навмисно: у шаблонному рядку репітера id
	// містить маркер __INDEX__, який JS замінює на номер рядка. Якби
	// маркер зіпсувався, усі клоновані рядки отримали б однакові id.
	$id = 'scala-' . preg_replace( '~[^a-z0-9_]+~i', '-', $name );

	echo '<div class="scala-field scala-field--' . esc_attr( $type ) . '">';

	if ( ! empty( $field['label'] ) && 'checkbox' !== $type ) {
		printf(
			'<label class="scala-field__label" for="%s">%s</label>',
			esc_attr( $id ),
			esc_html( $field['label'] )
		);
	}

	switch ( $type ) {
		case 'textarea':
			printf(
				'<textarea id="%s" name="%s" rows="%d" class="widefat">%s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				(int) ( $field['rows'] ?? 3 ),
				esc_textarea( (string) $value )
			);
			break;

		case 'select':
			printf( '<select id="%s" name="%s" class="widefat">', esc_attr( $id ), esc_attr( $name ) );
			foreach ( (array) ( $field['options'] ?? array() ) as $opt_value => $opt_label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( (string) $opt_value ),
					selected( (string) $value, (string) $opt_value, false ),
					esc_html( (string) $opt_label )
				);
			}
			echo '</select>';
			break;

		case 'checkbox':
			printf(
				'<label class="scala-field__check"><input type="checkbox" id="%s" name="%s" value="1"%s> %s</label>',
				esc_attr( $id ),
				esc_attr( $name ),
				checked( (bool) $value, true, false ),
				esc_html( $field['label'] ?? '' )
			);
			break;

		case 'image':
			scala_render_image_field( $id, $name, (int) $value );
			break;

		case 'repeater':
			scala_render_repeater( $field, is_array( $value ) ? $value : array(), $name );
			break;

		case 'number':
			printf(
				'<input type="number" id="%s" name="%s" value="%s" class="widefat">',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( (string) $value )
			);
			break;

		default: // text, url, tel, email
			$input_type = in_array( $type, array( 'url', 'tel', 'email' ), true ) ? $type : 'text';
			printf(
				'<input type="%s" id="%s" name="%s" value="%s" class="widefat">',
				esc_attr( $input_type ),
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( (string) $value )
			);
	}

	if ( ! empty( $field['help'] ) ) {
		printf( '<p class="scala-field__help">%s</p>', esc_html( $field['help'] ) );
	}

	echo '</div>';
}

/**
 * Поле вибору зображення з медіабібліотеки.
 *
 * @param string $id            HTML id.
 * @param string $name          Атрибут name.
 * @param int    $attachment_id Поточне вкладення.
 * @return void
 */
function scala_render_image_field( string $id, string $name, int $attachment_id ): void {
	$thumb = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';

	printf(
		'<div class="scala-image" data-scala-image>
			<div class="scala-image__preview">%s</div>
			<input type="hidden" id="%s" name="%s" value="%d" data-scala-image-input>
			<button type="button" class="button" data-scala-image-pick>%s</button>
			<button type="button" class="button-link scala-image__clear" data-scala-image-clear%s>%s</button>
		</div>',
		$thumb
			? '<img src="' . esc_url( $thumb ) . '" alt="">'
			: '<span class="scala-image__empty">' . esc_html__( 'Зображення не обрано', 'scala' ) . '</span>',
		esc_attr( $id ),
		esc_attr( $name ),
		$attachment_id,
		esc_html__( 'Обрати', 'scala' ),
		$attachment_id ? '' : ' hidden',
		esc_html__( 'Прибрати', 'scala' )
	);
}

/**
 * Повторюваний блок: рядки + прихований шаблон для додавання нових.
 *
 * @param array  $field Опис поля.
 * @param array  $rows  Наявні рядки.
 * @param string $name  Атрибут name.
 * @return void
 */
function scala_render_repeater( array $field, array $rows, string $name ): void {
	$sub_fields = (array) ( $field['fields'] ?? array() );
	$row_label  = $field['row_label'] ?? __( 'Блок', 'scala' );

	echo '<div class="scala-rep" data-scala-repeater>';
	echo '<div class="scala-rep__rows" data-scala-rep-rows>';

	$index = 0;
	foreach ( array_values( $rows ) as $row ) {
		scala_render_repeater_row( $sub_fields, (array) $row, $name . '[' . $index . ']', $row_label, $index );
		$index++;
	}

	echo '</div>';

	// Шаблон рядка. __INDEX__ підміняє JS при додаванні.
	echo '<script type="text/html" data-scala-rep-template>';
	scala_render_repeater_row( $sub_fields, array(), $name . '[__INDEX__]', $row_label, 0 );
	echo '</script>';

	printf(
		'<button type="button" class="button scala-rep__add" data-scala-rep-add>+ %s</button>',
		esc_html( sprintf( /* translators: назва блоку */ __( 'Додати: %s', 'scala' ), $row_label ) )
	);

	echo '</div>';
}

/**
 * Один рядок репітера.
 *
 * @param array  $sub_fields Вкладені поля.
 * @param array  $values     Значення рядка.
 * @param string $name       Атрибут name рядка.
 * @param string $row_label  Підпис рядка.
 * @param int    $index      Порядковий номер.
 * @return void
 */
function scala_render_repeater_row( array $sub_fields, array $values, string $name, string $row_label, int $index ): void {
	echo '<div class="scala-rep__row" data-scala-rep-row>';
	echo '<div class="scala-rep__head">';
	printf(
		'<span class="scala-rep__title">%s <span class="scala-rep__num">%d</span></span>',
		esc_html( $row_label ),
		$index + 1
	);
	echo '<span class="scala-rep__tools">';
	printf( '<button type="button" class="button-link" data-scala-rep-up title="%s">↑</button>', esc_attr__( 'Вище', 'scala' ) );
	printf( '<button type="button" class="button-link" data-scala-rep-down title="%s">↓</button>', esc_attr__( 'Нижче', 'scala' ) );
	printf( '<button type="button" class="button-link scala-rep__del" data-scala-rep-del title="%s">×</button>', esc_attr__( 'Видалити', 'scala' ) );
	echo '</span></div>';

	echo '<div class="scala-rep__body">';
	scala_render_fields( $sub_fields, $values, $name );
	echo '</div>';

	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Санітизація
 * ---------------------------------------------------------------------- */

/**
 * Рекурсивно чистить значення за описом полів.
 *
 * Усе, чого немає в описі, відкидається — у базу не потрапляє нічого,
 * про що тема не знає.
 *
 * @param array $fields Опис полів.
 * @param mixed $raw    Сирі дані з форми.
 * @return array
 */
function scala_sanitize_fields( array $fields, $raw ): array {
	$raw   = is_array( $raw ) ? $raw : array();
	$clean = array();

	foreach ( $fields as $field ) {
		$key   = $field['key'];
		$type  = $field['type'] ?? 'text';
		$value = $raw[ $key ] ?? null;

		switch ( $type ) {
			case 'repeater':
				$rows      = is_array( $value ) ? $value : array();
				$sub       = (array) ( $field['fields'] ?? array() );
				$collected = array();

				foreach ( $rows as $row_key => $row ) {
					// Шаблонний рядок ніколи не зберігається.
					if ( '__INDEX__' === (string) $row_key ) {
						continue;
					}
					$collected[] = scala_sanitize_fields( $sub, $row );
				}

				$clean[ $key ] = $collected;
				break;

			case 'image':
			case 'number':
				$clean[ $key ] = (int) $value;
				break;

			case 'checkbox':
				$clean[ $key ] = ! empty( $value ) ? 1 : 0;
				break;

			case 'url':
				$clean[ $key ] = esc_url_raw( (string) $value );
				break;

			case 'email':
				$clean[ $key ] = sanitize_email( (string) $value );
				break;

			case 'textarea':
				// Дозволяємо інлайнові теги: <br>, <span>, посилання.
				$clean[ $key ] = wp_kses( (string) $value, scala_inline_tags() );
				break;

			case 'select':
				$allowed       = array_map( 'strval', array_keys( (array) ( $field['options'] ?? array() ) ) );
				$clean[ $key ] = in_array( (string) $value, $allowed, true ) ? (string) $value : '';
				break;

			default:
				$clean[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	return $clean;
}
