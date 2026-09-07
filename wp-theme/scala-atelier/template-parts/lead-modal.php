<?php
/**
 * Модальне вікно заявки.
 *
 * Кнопки з атрибутом data-lead-open відкривають форму на місці, а не
 * переносять користувача до блоку внизу сторінки. Значення атрибута
 * потрапляє в заявку як джерело — видно, який саме блок її привів.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog id="lead-modal" class="modal" aria-labelledby="lead-modal-title">
	<div class="modal__box">
		<button type="button" class="modal__close" data-modal-close aria-label="<?php esc_attr_e( 'Закрити', 'scala' ); ?>">&times;</button>

		<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Заявка', 'scala' ); ?></div>
		<h2 class="modal__title" id="lead-modal-title"><?php scala_the( 'title', __( 'Отримати індивідуальний прорахунок', 'scala' ) ); ?></h2>
		<p class="modal__text"><?php scala_the( 'note' ); ?></p>
		<div class="modal__source" data-source-label></div>

		<?php get_template_part( 'template-parts/form', null, array( 'prefix' => 'm-lead' ) ); ?>
	</div>
</dialog>
