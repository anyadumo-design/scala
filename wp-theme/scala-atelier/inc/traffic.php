<?php
/**
 * Звідки прийшла заявка: UTM-мітки, перехід, перша сторінка.
 *
 * Скрипт на сайті запамʼятовує мітки з адреси при вході й передає їх
 * разом із заявкою. Тут вони чистяться, зберігаються в заявці й
 * зводяться в один людський рядок — «instagram / cpc — osin» або
 * «Пошук Google». Цей рядок показується в списку заявок, у листі та
 * в Telegram: власниці сайту треба знати канал, а не розбирати мітки.
 *
 * Свідомо не зберігаємо: IP-адресу, повний рядок браузера, місто.
 * Для «звідки прийшов клієнт» це зайві персональні дані.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Мітки кампанії з адреси сторінки.
 *
 * @return array
 */
function scala_traffic_keys(): array {
	return array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid' );
}

/**
 * Збирає дані про джерело з надісланої форми.
 *
 * @return array Лише непорожні значення.
 */
function scala_collect_traffic(): array {
	$out = array();

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- нонс перевірено в scala_handle_lead().
	foreach ( scala_traffic_keys() as $key ) {
		if ( empty( $_POST[ $key ] ) ) {
			continue;
		}

		$value = mb_substr( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ), 0, 200 );

		if ( '' !== $value ) {
			$out[ $key ] = $value;
		}
	}

	foreach ( array( 'referrer', 'landing' ) as $key ) {
		if ( empty( $_POST[ $key ] ) ) {
			continue;
		}

		$value = mb_substr( esc_url_raw( wp_unslash( $_POST[ $key ] ) ), 0, 300 );

		if ( '' !== $value ) {
			$out[ $key ] = $value;
		}
	}

	if ( ! empty( $_POST['views'] ) ) {
		$views = min( 999, absint( wp_unslash( $_POST['views'] ) ) );

		if ( $views > 1 ) {
			$out['views'] = $views;
		}
	}
	// phpcs:enable

	$out['device'] = wp_is_mobile() ? 'mobile' : 'desktop';

	return $out;
}

/**
 * Канал одним рядком — те, що читає людина.
 *
 * @param array $traffic Дані заявки.
 * @return string
 */
function scala_traffic_channel( array $traffic ): string {
	// Заявки, прийняті до появи цього обліку: даних немає взагалі.
	if ( ! $traffic ) {
		return '—';
	}

	if ( ! empty( $traffic['utm_source'] ) ) {
		$line = (string) $traffic['utm_source'];

		if ( ! empty( $traffic['utm_medium'] ) ) {
			$line .= ' / ' . $traffic['utm_medium'];
		}

		if ( ! empty( $traffic['utm_campaign'] ) ) {
			$line .= ' — ' . $traffic['utm_campaign'];
		}

		return $line;
	}

	if ( ! empty( $traffic['gclid'] ) ) {
		return __( 'Google Ads — оголошення', 'scala' );
	}

	if ( ! empty( $traffic['fbclid'] ) ) {
		return __( 'Facebook або Instagram — оголошення', 'scala' );
	}

	if ( ! empty( $traffic['referrer'] ) ) {
		return scala_traffic_site_name( (string) $traffic['referrer'] );
	}

	return __( 'Прямий захід або месенджер', 'scala' );
}

/**
 * Назва сайту, з якого прийшли.
 *
 * @param string $url Адреса переходу.
 * @return string
 */
function scala_traffic_site_name( string $url ): string {
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	$host = preg_replace( '~^www\.~', '', strtolower( $host ) );

	if ( '' === $host ) {
		return __( 'Перехід з іншого сайту', 'scala' );
	}

	$known = array(
		'google'    => __( 'Пошук Google', 'scala' ),
		'bing'      => __( 'Пошук Bing', 'scala' ),
		'duckduckgo' => __( 'Пошук DuckDuckGo', 'scala' ),
		'instagram' => __( 'Instagram', 'scala' ),
		'facebook'  => __( 'Facebook', 'scala' ),
		'fb.'       => __( 'Facebook', 'scala' ),
		't.me'      => __( 'Telegram', 'scala' ),
		'telegram'  => __( 'Telegram', 'scala' ),
		'youtube'   => __( 'YouTube', 'scala' ),
		'pinterest' => __( 'Pinterest', 'scala' ),
		'tiktok'    => __( 'TikTok', 'scala' ),
		'olx'       => __( 'OLX', 'scala' ),
	);

	foreach ( $known as $needle => $name ) {
		if ( str_contains( $host, $needle ) ) {
			return $name;
		}
	}

	/* translators: %s — домен сайту, з якого перейшли */
	return sprintf( __( 'Перехід з %s', 'scala' ), $host );
}

/**
 * Рядки для листа, Telegram і картки заявки.
 *
 * Перший рядок — канал, він є завжди. Решта зʼявляється лише тоді,
 * коли є що показати: порожні мітки нікому не потрібні.
 *
 * @param array $traffic   Дані заявки.
 * @param bool  $technical Разом з ідентифікаторами кліку — вони потрібні
 *                         лише для вивантаження конверсій у рекламний
 *                         кабінет, у листі з заявкою це сміття.
 * @return array Підпис => значення.
 */
function scala_traffic_rows( array $traffic, bool $technical = false ): array {
	$rows = array( __( 'Канал', 'scala' ) => scala_traffic_channel( $traffic ) );

	if ( ! $traffic ) {
		return $rows;
	}

	$labels = array(
		'utm_campaign' => __( 'Кампанія', 'scala' ),
		'utm_content'  => __( 'Оголошення', 'scala' ),
		'utm_term'     => __( 'Ключове слово', 'scala' ),
	);

	foreach ( $labels as $key => $label ) {
		// Кампанія вже є в рядку каналу — не повторюємо.
		if ( 'utm_campaign' === $key && ! empty( $traffic['utm_source'] ) ) {
			continue;
		}

		if ( ! empty( $traffic[ $key ] ) ) {
			$rows[ $label ] = (string) $traffic[ $key ];
		}
	}

	if ( ! empty( $traffic['landing'] ) ) {
		$rows[ __( 'Зайшли з', 'scala' ) ] = (string) $traffic['landing'];
	}

	if ( ! empty( $traffic['views'] ) ) {
		$rows[ __( 'Переглянуто сторінок', 'scala' ) ] = (string) (int) $traffic['views'];
	}

	if ( ! empty( $traffic['device'] ) ) {
		$rows[ __( 'Пристрій', 'scala' ) ] = 'mobile' === $traffic['device']
			? __( 'телефон', 'scala' )
			: __( 'компʼютер', 'scala' );
	}

	if ( $technical ) {
		foreach ( array( 'gclid' => 'Google (gclid)', 'fbclid' => 'Meta (fbclid)' ) as $key => $label ) {
			if ( ! empty( $traffic[ $key ] ) ) {
				$rows[ $label ] = (string) $traffic[ $key ];
			}
		}
	}

	return $rows;
}
