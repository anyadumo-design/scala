<?php
/**
 * 07 — Часті питання.
 *
 * Ці ж питання йдуть у розмітку FAQPage (inc/seo.php) слово в слово:
 * за розбіжність тексту на сторінці й у схемі Google знімає сніпет.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_faq = scala_posts( 'scala_faq' );

if ( ! $scala_faq ) {
	return;
}
?>
<section id="faq" class="section section--narrow">
	<div class="eyebrow eyebrow--plain">
		<span class="eyebrow__num">07</span>&nbsp;&nbsp;<?php scala_the( 'faq_eyebrow' ); ?>
	</div>

	<h2 class="h2-section faq__h2"><?php scala_the( 'faq_title' ); ?></h2>

	<?php foreach ( $scala_faq as $scala_item ) : ?>
		<details class="faq-item">
			<summary>
				<?php echo esc_html( get_the_title( $scala_item ) ); ?>
				<span class="f-plus">+</span>
			</summary>
			<div class="faq-item__a">
				<?php echo esc_html( wp_strip_all_tags( (string) $scala_item->post_content ) ); ?>
			</div>
		</details>
	<?php endforeach; ?>
</section>
