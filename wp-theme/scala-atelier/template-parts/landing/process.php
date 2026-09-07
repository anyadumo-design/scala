<?php
/**
 * 05 — Як працюємо. Нумерація тут справжня: це послідовність кроків.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_steps = scala_rows( 'process' );

if ( ! $scala_steps ) {
	return;
}
?>
<section id="process" class="section">
	<div class="eyebrow">
		<span class="eyebrow__num">05</span>
		<span><?php scala_the( 'process_eyebrow' ); ?></span>
	</div>

	<div class="four-col">
		<?php foreach ( $scala_steps as $scala_i => $scala_step ) : ?>
			<div class="cell cell--steps">
				<div class="cell__num"><?php echo esc_html( str_pad( (string) ( $scala_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></div>
				<div class="cell__title"><?php echo esc_html( $scala_step['title'] ?? '' ); ?></div>
				<div class="cell__text"><?php echo esc_html( $scala_step['text'] ?? '' ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
