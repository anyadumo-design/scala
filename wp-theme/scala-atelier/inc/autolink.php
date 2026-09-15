<?php
/**
 * Посилання всередині статей.
 *
 * Перенесені статті клієнта не містять жодного посилання: ні на види
 * штор, ні на кімнати. Для читача це глухий кут, для пошуковика —
 * сторінка, яка нікуди не передає ваги.
 *
 * Тут перше згадування ключового слова в тексті статті стає
 * посиланням. Робимо це на виводі, а не в базі: текст лишається таким,
 * яким його написали, і будь-яку правку в редакторі видно без
 * сюрпризів. Заголовки й наявні посилання не чіпаємо.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Що на що заміняти: основа слова => слаг сторінки.
 *
 * Основи, а не повні слова: українська відмінює, і «портьєри»,
 * «портьєрами», «портьєрну» — те саме слово.
 *
 * @return array
 */
function scala_autolink_map(): array {
	return array(
		'тюл'       => array( 'scala_type', 'tyul' ),
		'портьєр'   => array( 'scala_type', 'portyery' ),
		'римськ'    => array( 'scala_type', 'rymski-shtory' ),
		'рулонн'    => array( 'scala_type', 'rulonni-shtory' ),
		'жалюзі'    => array( 'scala_type', 'zhalyuzi' ),
		'японськ'   => array( 'scala_type', 'yaponski-paneli' ),
		'австрійськ' => array( 'scala_type', 'avstrijski-shtory' ),
		'блекаут'   => array( 'scala_type', 'blekaut' ),
		'спальн'    => array( 'page', 'shtory-dlya-spalni' ),
		'кухн'      => array( 'page', 'shtory-na-kuhnyu' ),
		'дитяч'     => array( 'page', 'shtory-v-dytyachu' ),
		'вітальн'   => array( 'page', 'shtory-u-vitalnyu' ),
		'кабінет'   => array( 'page', 'shtory-v-kabinet' ),
		'балкон'    => array( 'page', 'shtory-na-balkon' ),
	);
}

/**
 * Адреса цілі, якщо сторінка існує й опублікована.
 *
 * @param string $type Тип запису.
 * @param string $slug Слаг.
 * @return string
 */
function scala_autolink_url( string $type, string $slug ): string {
	static $cache = array();

	$key = $type . '/' . $slug;

	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$found = get_posts(
		array(
			'name'             => $slug,
			'post_type'        => $type,
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'suppress_filters' => false,
		)
	);

	$cache[ $key ] = $found ? (string) get_permalink( $found[0] ) : '';

	return $cache[ $key ];
}

/**
 * Ставить посилання на перше згадування кожного слова.
 *
 * @param string $html Вміст статті.
 * @return string
 */
function scala_autolink_content( $html ) {
	if ( ! is_singular( 'post' ) || ! is_main_query() || ! in_the_loop() ) {
		return $html;
	}

	$map  = scala_autolink_map();
	$done = array();
	$left = 5; // більше посилань у короткій статті виглядає як спам

	/*
	 * Якщо автор уже послався на це слово сам — другого посилання на
	 * те саме не додаємо.
	 */
	if ( preg_match_all( '~<a\s[^>]*>(.*?)</a>~isu', (string) $html, $links ) ) {
		$inside = mb_strtolower( implode( ' ', $links[1] ) );

		foreach ( array_keys( $map ) as $stem ) {
			if ( false !== mb_strpos( $inside, $stem ) ) {
				$done[ $stem ] = true;
			}
		}
	}

	// Розрізаємо на теги й текст, щоб не лізти всередину розмітки.
	$parts  = preg_split( '~(<[^>]+>)~u', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$skip   = 0; // глибина всередині <a> або заголовка
	$result = '';

	foreach ( $parts as $part ) {
		if ( '' === $part ) {
			continue;
		}

		if ( '<' === $part[0] ) {
			if ( preg_match( '~^<(a|h[1-6])[\s>]~i', $part ) ) {
				++$skip;
			} elseif ( preg_match( '~^</(a|h[1-6])>~i', $part ) ) {
				$skip = max( 0, $skip - 1 );
			}

			$result .= $part;
			continue;
		}

		if ( $skip > 0 || $left < 1 ) {
			$result .= $part;
			continue;
		}

		foreach ( $map as $stem => $target ) {
			if ( $left < 1 || isset( $done[ $stem ] ) ) {
				continue;
			}

			/*
			 * До основи чіпляємо закінчення і, якщо воно поруч, іменник:
			 * тоді в посилання йде «рулонні штори», а не саме «рулонні».
			 */
			$pattern = '~(?<![\p{L}\p{N}])('
				. preg_quote( $stem, '~' )
				. '[\p{L}]{0,12}(?:\s+(?:штор[\p{L}]{0,3}|систем[\p{L}]{0,3}|панел[\p{L}]{0,3}))?)~ui';

			if ( ! preg_match( $pattern, $part, $m ) ) {
				continue;
			}

			$url = scala_autolink_url( $target[0], $target[1] );

			if ( ! $url ) {
				continue;
			}

			$part = preg_replace(
				$pattern,
				'<a href="' . esc_url( $url ) . '">$1</a>',
				$part,
				1
			);

			$done[ $stem ] = true;
			--$left;
		}

		$result .= $part;
	}

	return $result;
}
add_filter( 'the_content', 'scala_autolink_content', 20 );
