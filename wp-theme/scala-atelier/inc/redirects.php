<?php
/**
 * Редіректи зі старих адрес.
 *
 * Сайт переїжджає на нову структуру, а частина адрес уже проіндексована.
 * Без 301 ці сторінки віддадуть 404 — і позиції, які вони мають зараз,
 * зникнуть. Це найдорожча помилка при переїзді, і виправляти її потім
 * довго, тому карта адрес їде разом з темою.
 *
 * Спрацьовує лише на 404: якщо адреса відкривається сама, ми в неї
 * не втручаємось.
 *
 * Російську версію (Polylang, /ru/) на новому сайті не робимо — усі
 * її адреси ведуть на українські відповідники.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Карта старих адрес: шлях без слешів => новий шлях.
 *
 * Порожній рядок означає головну.
 *
 * @return array
 */
function scala_redirect_map(): array {
	return array(

		/* ---- Сторінки, для яких у новій структурі немає окремої ---- */
		'galereya'                        => 'katalog',
		'indyvidualnyj-pidbir-tkanyny'    => 'katalog',
		'vyyizd-dyzajnera'                => 'pro-brend',
		'poshyttya-ta-montazh-pid-klyuch' => 'pro-brend',
		'spivpraczya-dlya-dyzajneriv'     => 'pro-brend',
		'sample-page'                     => '',

		/* ---- Комерційні сторінки, які лежали в блозі ---------------- */
		'avstrijski-shtory' => 'vydy-shtor/avstrijski-shtory',
		'yaponski-shtory'   => 'vydy-shtor/yaponski-paneli',
		'rulonni-shtory'    => 'vydy-shtor/rulonni-shtory',

		/* ---- Російська версія: усе на українські відповідники ------- */
		'ru'                                 => '',
		'ru/galereya-2'                      => 'katalog',
		'ru/yndyvydualnyj-podbor-tkany'      => 'katalog',
		'ru/pro-brend-2'                     => 'pro-brend',
		'ru/sotrudnychestvo-dlya-dyzajnerov'  => 'pro-brend',
		'ru/poshyv-y-montazh-pod-klyuch'     => 'pro-brend',
		'ru/vyezd-dyzajnera'                 => 'pro-brend',
		'ru/kontakty-2'                      => 'kontakty',
		'ru/avstryjskye-shtory'              => 'vydy-shtor/avstrijski-shtory',
		'ru/rulonnye-shtory'                 => 'vydy-shtor/rulonni-shtory',

		'ru/yaponskye-shtory-mynymalyzm-styl-y-funkczyonalnost'
			=> 'vydy-shtor/yaponski-paneli',

		'ru/elektrokarnyzy-sovremennoe-reshenye-dlya-upravlenyya-shtoramy'
			=> 'elektrokarnyzy-suchasne-rishennya-dlya-keruvannya-shtoramy',

		'ru/kak-vybrat-ydealnye-tkany-dlya-shtor' => 'novost-3',
		'ru/kak-pravylno-vybrat-karnyz'           => 'yak-pravilno-vibrati-karniz',
		'ru/novost-4'                             => 'yaki-vikonni-sistemi-ye-na-rinku',
	);
}

/**
 * Веде стару адресу на нову.
 *
 * @return void
 */
function scala_do_redirect(): void {
	if ( ! is_404() ) {
		return;
	}

	$path = trim( (string) ( $GLOBALS['wp']->request ?? '' ), '/' );

	if ( '' === $path ) {
		return;
	}

	$map = scala_redirect_map();

	// Прямий збіг, далі — усе інше з /ru/ на головну.
	if ( isset( $map[ $path ] ) ) {
		$target = $map[ $path ];
	} elseif ( 'ru' === strtok( $path, '/' ) ) {
		$target = '';
	} else {
		return;
	}

	wp_safe_redirect( home_url( user_trailingslashit( $target ) ), 301 );
	exit;
}
add_action( 'template_redirect', 'scala_do_redirect' );
