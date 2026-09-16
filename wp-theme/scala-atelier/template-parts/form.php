<?php
/**
 * Форма заявки. Використовується інлайново на сторінках і всередині
 * модального вікна.
 *
 * $args['prefix'] — префікс для id полів. Форма присутня на сторінці
 * двічі (інлайнова + у вікні), тож без різних id вийшли б дублікати,
 * і підпис <label for> вказував би не на те поле.
 *
 * $args['source'] — звідки прийшла заявка. Для інлайнової форми
 * фіксоване, для модальної підставляє скрипт при відкритті.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_prefix = isset( $args['prefix'] ) ? sanitize_key( (string) $args['prefix'] ) : 'lead';
$scala_source = isset( $args['source'] ) ? (string) $args['source'] : '';
$scala_needs  = scala_rows( 'needs' );
$scala_places = scala_rows( 'places' );

// Поле нове, у збережених налаштуваннях його ще немає.
if ( ! $scala_places ) {
	$scala_places = array(
		array( 'text' => __( 'Квартира', 'scala' ) ),
		array( 'text' => __( 'Будинок', 'scala' ) ),
		array( 'text' => __( 'Комерційне приміщення', 'scala' ) ),
	);
}

$scala_id = static fn( string $name ): string => $scala_prefix . '-' . $name;
?>
<form class="form" data-lead-form novalidate>
	<label class="visually-hidden" for="<?php echo esc_attr( $scala_id( 'name' ) ); ?>"><?php esc_html_e( 'Імʼя', 'scala' ); ?></label>
	<input id="<?php echo esc_attr( $scala_id( 'name' ) ); ?>" name="name" autocomplete="given-name"
		placeholder="<?php esc_attr_e( 'Імʼя (необовʼязково)', 'scala' ); ?>" />

	<label class="visually-hidden" for="<?php echo esc_attr( $scala_id( 'phone' ) ); ?>"><?php esc_html_e( 'Телефон', 'scala' ); ?></label>
	<input id="<?php echo esc_attr( $scala_id( 'phone' ) ); ?>" name="phone" type="tel" required autocomplete="tel" inputmode="tel"
		placeholder="+38 (0__) ___-__-__" />

	<?php
	/*
	 * Не випадаючий список, а позначки: люди часто замовляють кілька
	 * речей одразу — тюль і портьєри, римські на кухню й рулонні в
	 * спальню, — а список дозволяв обрати лише одне.
	 */
	?>
	<?php if ( $scala_needs ) : ?>
		<fieldset class="form__group">
			<legend class="form__legend"><?php esc_html_e( 'Що потрібно оформити', 'scala' ); ?> <span><?php esc_html_e( 'можна кілька', 'scala' ); ?></span></legend>
			<div class="form__choices">
				<?php foreach ( $scala_needs as $scala_need ) : ?>
					<?php if ( ! empty( $scala_need['text'] ) ) : ?>
						<label class="form__choice">
							<input type="checkbox" name="need[]" value="<?php echo esc_attr( $scala_need['text'] ); ?>" />
							<span><?php echo esc_html( $scala_need['text'] ); ?></span>
						</label>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</fieldset>
	<?php endif; ?>

	<fieldset class="form__group">
		<legend class="form__legend"><?php esc_html_e( 'Тип приміщення', 'scala' ); ?></legend>
		<div class="form__choices">
			<?php foreach ( $scala_places as $scala_place ) : ?>
				<?php if ( ! empty( $scala_place['text'] ) ) : ?>
					<label class="form__choice">
						<input type="radio" name="place" value="<?php echo esc_attr( $scala_place['text'] ); ?>" />
						<span><?php echo esc_html( $scala_place['text'] ); ?></span>
					</label>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</fieldset>

	<label class="visually-hidden" for="<?php echo esc_attr( $scala_id( 'note' ) ); ?>"><?php esc_html_e( 'Коментар', 'scala' ); ?></label>
	<textarea id="<?php echo esc_attr( $scala_id( 'note' ) ); ?>" name="note" rows="2"
		placeholder="<?php esc_attr_e( 'Коментар (необовʼязково)', 'scala' ); ?>"></textarea>

	<?php // Пастка для ботів: люди цього поля не бачать. ?>
	<div class="form__hp" aria-hidden="true">
		<label><?php esc_html_e( 'Не заповнюйте це поле', 'scala' ); ?>
			<input type="text" name="website" tabindex="-1" autocomplete="off" />
		</label>
	</div>

	<input type="hidden" name="source" value="<?php echo esc_url( is_singular() ? (string) get_permalink() : home_url( user_trailingslashit( $GLOBALS['wp']->request ?? '' ) ) ); ?>" />
	<input type="hidden" name="source_label" value="<?php echo esc_attr( $scala_source ); ?>"<?php echo $scala_source ? '' : ' data-source-field'; ?> />

	<button type="submit"><?php scala_the( 'button', __( 'Запросити дизайнера', 'scala' ) ); ?></button>
	<div class="form__error" role="alert"></div>
	<div class="form__consent"><?php scala_the( 'consent' ); ?></div>
</form>

<div class="form-sent" data-lead-sent hidden>
	<div class="form-sent__title" id="<?php echo esc_attr( $scala_id( 'sent-title' ) ); ?>"><?php scala_the( 'sent_title', __( 'Заявку прийнято', 'scala' ) ); ?></div>
	<div class="form-sent__text" data-thanks></div>
</div>
