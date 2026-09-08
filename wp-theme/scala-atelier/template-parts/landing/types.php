<?php
/**
 * 03 — Види штор.
 *
 * Картки ведуть на власні сторінки видів (/vydy-shtor/…). Це головна
 * перелінковка сайту: саме нею головна передає вагу комерційним
 * сторінкам. Якщо сторінки виду немає — картка веде на форму.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_types = array_filter(
	scala_posts( 'scala_type' ),
	static fn( $post ) => (int) scala_meta( $post->ID, 'on_home', 1 ) === 1
);

if ( ! $scala_types ) {
	return;
}
?>
<section id="solutions" class="section">
	<div class="eyebrow">
		<span class="eyebrow__num">03</span>
		<span><?php scala_the( 'types_eyebrow' ); ?></span>
	</div>

	<h2 class="h2-section"><?php scala_the( 'types_title' ); ?></h2>

	<div class="types-grid">
		<?php foreach ( $scala_types as $scala_type ) : ?>
			<?php
			$scala_url   = get_permalink( $scala_type );
			$scala_short = (string) scala_meta( $scala_type->ID, 'short', (string) $scala_type->post_excerpt );
			$scala_tag   = (string) scala_meta( $scala_type->ID, 'tag', __( 'Докладно →', 'scala' ) );
			?>
			<a href="<?php echo esc_url( $scala_url ?: '#request' ); ?>" class="type-card" data-cursor="ДЕТАЛІ">
				<div class="type-card__frame">
					<div class="type-card__zoom">
						<?php
						scala_image(
							get_post_thumbnail_id( $scala_type ),
							array(
								'size'        => 'scala-portrait',
								'sizes'       => '(max-width:760px) 50vw, 20vw',
								'alt'         => get_the_title( $scala_type ),
								'placeholder' => get_the_title( $scala_type ),
							)
						);
						?>
					</div>
				</div>
				<div class="type-card__row">
					<span class="type-card__name"><?php echo esc_html( get_the_title( $scala_type ) ); ?></span>
				</div>
				<?php if ( $scala_short ) : ?>
					<div class="type-card__text"><?php echo esc_html( $scala_short ); ?></div>
				<?php endif; ?>
				<?php if ( $scala_tag ) : ?>
					<span class="type-card__tag"><?php echo esc_html( $scala_tag ); ?></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</section>
