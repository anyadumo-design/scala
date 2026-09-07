<?php
/**
 * CTA-смуга. Параметри приходять через get_template_part( …, $args ).
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_title = $args['title'] ?? '';
$scala_text  = $args['text'] ?? '';
$scala_btn   = $args['button'] ?? __( 'Запросити дизайнера', 'scala' );
$scala_href  = $args['href'] ?? '#request';

if ( ! $scala_title ) {
	return;
}
?>
<section class="section--m88">
	<div class="ctabar">
		<div>
			<div class="ctabar__title"><?php echo esc_html( $scala_title ); ?></div>
			<?php if ( $scala_text ) : ?>
				<div class="ctabar__text"><?php echo esc_html( $scala_text ); ?></div>
			<?php endif; ?>
		</div>
		<a href="<?php echo esc_url( $scala_href ); ?>" class="btn btn--dark"<?php echo '#request' === $scala_href ? ' data-lead-open="' . esc_attr( __( 'Смуга: ', 'scala' ) . $scala_title ) . '"' : ''; ?>>
			<?php echo esc_html( $scala_btn ); ?>
		</a>
	</div>
</section>
