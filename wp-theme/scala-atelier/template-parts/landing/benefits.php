<?php
/**
 * 01 — Що змінюють правильні штори.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_items = scala_rows( 'benefits' );

if ( ! $scala_items ) {
	return;
}
?>
<section class="section">
	<div class="eyebrow">
		<span class="eyebrow__num">01</span>
		<span><?php scala_the( 'benefits_eyebrow' ); ?></span>
	</div>

	<div class="four-col">
		<?php foreach ( $scala_items as $scala_item ) : ?>
			<div class="cell">
				<div class="cell__title"><?php echo esc_html( $scala_item['title'] ?? '' ); ?></div>
				<div class="cell__text"><?php echo esc_html( $scala_item['text'] ?? '' ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
