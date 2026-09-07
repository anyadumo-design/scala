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

$scala_id = static fn( string $name ): string => $scala_prefix . '-' . $name;
?>
<form class="form" data-lead-form novalidate>
	<label class="visually-hidden" for="<?php echo esc_attr( $scala_id( 'name' ) ); ?>"><?php esc_html_e( 'Імʼя', 'scala' ); ?></label>
	<input id="<?php echo esc_attr( $scala_id( 'name' ) ); ?>" name="name" autocomplete="given-name"
		placeholder="<?php esc_attr_e( 'Імʼя (необовʼязково)', 'scala' ); ?>" />

	<label class="visually-hidden" for="<?php echo esc_attr( $scala_id( 'phone' ) ); ?>"><?php esc_html_e( 'Телефон', 'scala' ); ?></label>
	<input id="<?php echo esc_attr( $scala_id( 'phone' ) ); ?>" name="phone" type="tel" required autocomplete="tel"
		placeholder="<?php esc_attr_e( 'Телефон *', 'scala' ); ?>" />

	<?php if ( $scala_needs ) : ?>
		<label class="visually-hidden" for="<?php echo esc_attr( $scala_id( 'need' ) ); ?>"><?php esc_html_e( 'Що потрібно оформити', 'scala' ); ?></label>
		<select id="<?php echo esc_attr( $scala_id( 'need' ) ); ?>" name="need">
			<option value=""><?php esc_html_e( 'Що потрібно оформити', 'scala' ); ?></option>
			<?php foreach ( $scala_needs as $scala_need ) : ?>
				<?php if ( ! empty( $scala_need['text'] ) ) : ?>
					<option><?php echo esc_html( $scala_need['text'] ); ?></option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select>
	<?php endif; ?>

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
	<div class="form-sent__title"><?php scala_the( 'sent_title', __( 'Заявку прийнято', 'scala' ) ); ?></div>
	<div class="form-sent__text" data-thanks></div>
</div>
