<?php
/**
 * Сумісність зі старішим PHP.
 *
 * На хостингу клієнта стоїть PHP 7.4. Тема жодних конструкцій PHP 8
 * не використовує — тільки дві функції з 8.0, str_contains і
 * str_starts_with. WordPress підміняє їх власними реалізаціями ще
 * з версії 5.9, тож насправді все працює й на 7.4.
 *
 * Але покладатися на це не варто: тема має бути самодостатньою
 * і не залежати від того, що саме ядро вирішить полiфілити завтра.
 * Тому свої підміни, під перевіркою function_exists.
 *
 * Файл підключається першим, до всього іншого.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Чи міститься підрядок у рядку.
	 *
	 * @param string $haystack Де шукаємо.
	 * @param string $needle   Що шукаємо.
	 * @return bool
	 */
	function str_contains( string $haystack, string $needle ): bool {
		return '' === $needle || false !== strpos( $haystack, $needle );
	}
}

if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * Чи починається рядок із підрядка.
	 *
	 * @param string $haystack Де шукаємо.
	 * @param string $needle   Що шукаємо.
	 * @return bool
	 */
	function str_starts_with( string $haystack, string $needle ): bool {
		return 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}
}
