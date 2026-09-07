<?php
/**
 * 09 — Заявка.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;
?>
<section id="request" class="section--m96">
	<div class="request__box two-col">
		<div class="request__pane">
			<div class="eyebrow eyebrow--plain">
				<span class="eyebrow__num">09</span>&nbsp;&nbsp;<?php esc_html_e( 'Заявка', 'scala' ); ?>
			</div>

			<h2 class="request__h2"><?php scala_the( 'title' ); ?></h2>
			<p class="request__text"><?php scala_the( 'text' ); ?></p>

			<?php if ( scala_opt( 'note' ) ) : ?>
				<p class="request__note"><?php scala_the( 'note' ); ?></p>
			<?php endif; ?>

			<?php get_template_part( 'template-parts/form', null, array( 'source' => __( 'Форма внизу головної', 'scala' ) ) ); ?>
		</div>

		<div class="request__photo">
			<?php
			scala_image(
				(int) scala_opt( 'photo', 0 ),
				array(
					'size'        => 'scala-md',
					'sizes'       => '(max-width:760px) 100vw, 50vw',
					'class'       => 'parallax',
					'alt'         => __( 'Штори Scala', 'scala' ),
					'placeholder' => __( 'Фото біля форми', 'scala' ),
				)
			);
			?>
		</div>
	</div>
</section>
