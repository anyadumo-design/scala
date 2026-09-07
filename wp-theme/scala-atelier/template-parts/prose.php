<?php
/**
 * Змістовний текстовий блок.
 *
 * Сам текст — це вміст сторінки з редактора WordPress. Так клієнт
 * редагує його звичним способом, з заголовками, списками й
 * посиланнями, а не через вузьке поле налаштувань.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_eyebrow = $args['eyebrow'] ?? '';
$scala_title   = $args['title'] ?? '';
$scala_html    = $args['content'] ?? '';
$scala_id      = $args['id'] ?? '';
$scala_class   = $args['section_class'] ?? 'section';

if ( ! trim( wp_strip_all_tags( (string) $scala_html ) ) ) {
	return;
}
?>
<section<?php echo $scala_id ? ' id="' . esc_attr( $scala_id ) . '"' : ''; ?> class="<?php echo esc_attr( $scala_class ); ?>">
	<div class="prose-block">
		<div class="prose-block__aside">
			<?php if ( $scala_eyebrow ) : ?>
				<div class="eyebrow eyebrow--plain"><?php echo esc_html( $scala_eyebrow ); ?></div>
			<?php endif; ?>
			<?php if ( $scala_title ) : ?>
				<h2 class="prose-block__title"><?php echo esc_html( $scala_title ); ?></h2>
			<?php endif; ?>
		</div>

		<div class="prose">
			<?php
			// Вміст уже пройшов фільтри the_content, тож виводимо як є.
			echo $scala_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</section>
