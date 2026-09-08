<?php
/**
 * Перегляд скріншота відгуку на весь екран.
 *
 * У плитці переписку не прочитати — це мініатюра. Клік відкриває її
 * в повний розмір, зі стрілками й свайпом між скрінами.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog id="shot-modal" class="shotbox" aria-label="<?php esc_attr_e( 'Скріншот відгуку', 'scala' ); ?>">
	<button type="button" class="shotbox__close" data-shot-close aria-label="<?php esc_attr_e( 'Закрити', 'scala' ); ?>">&times;</button>
	<button type="button" class="shotbox__nav shotbox__nav--prev" data-shot-prev aria-label="<?php esc_attr_e( 'Попередній відгук', 'scala' ); ?>">&lsaquo;</button>
	<img class="shotbox__img" alt="" />
	<button type="button" class="shotbox__nav shotbox__nav--next" data-shot-next aria-label="<?php esc_attr_e( 'Наступний відгук', 'scala' ); ?>">&rsaquo;</button>
	<div class="shotbox__count" aria-live="polite"></div>
</dialog>
