<?php
/**
 * Один розділ сторінки виду штор: заголовок ліворуч, текст праворуч.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_heading = $args['heading'] ?? '';
$scala_body    = $args['body'] ?? '';

if ( ! trim( wp_strip_all_tags( (string) $scala_body ) ) ) {
	return;
}
?>
<div class="prose-block">
	<div class="prose-block__aside">
		<?php if ( $scala_heading ) : ?>
			<h2 class="prose-block__title"><?php echo esc_html( $scala_heading ); ?></h2>
		<?php endif; ?>
	</div>
	<div class="prose">
		<?php
		// Вміст уже пройшов фільтри the_content.
		echo $scala_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</div>
