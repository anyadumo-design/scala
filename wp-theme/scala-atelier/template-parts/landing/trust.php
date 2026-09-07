<?php
/**
 * Блок довіри: що входить у послугу + відгуки, фото робіт і скріншоти.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_includes   = scala_rows( 'includes' );
$scala_works      = scala_rows( 'works' );
$scala_shots      = scala_rows( 'screenshots' );
$scala_reviews    = scala_posts( 'scala_review' );

if ( ! $scala_includes && ! $scala_reviews ) {
	return;
}
?>
<section class="section" style="padding-top:96px">
	<div class="two-col">

		<div>
			<?php if ( $scala_includes ) : ?>
				<div class="trust__label"><?php esc_html_e( 'Що входить у послугу', 'scala' ); ?></div>
				<div class="incl">
					<?php foreach ( $scala_includes as $scala_i => $scala_row ) : ?>
						<div class="incl__row">
							<span class="incl__num"><?php echo esc_html( str_pad( (string) ( $scala_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="incl__text"><?php echo esc_html( $scala_row['text'] ?? '' ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div>
			<?php if ( $scala_reviews ) : ?>
				<div class="trust__label"><?php esc_html_e( 'Відгуки клієнтів', 'scala' ); ?></div>
				<div class="reviews">
					<?php foreach ( $scala_reviews as $scala_review ) : ?>
						<div class="review">
							<div class="review__text">
								<?php echo esc_html( wp_strip_all_tags( (string) $scala_review->post_content ) ); ?>
							</div>
							<?php $scala_author = (string) scala_meta( $scala_review->ID, 'author', '' ); ?>
							<?php if ( $scala_author ) : ?>
								<div class="review__who"><?php echo esc_html( $scala_author ); ?></div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $scala_works ) : ?>
				<div class="works">
					<?php foreach ( $scala_works as $scala_work ) : ?>
						<div class="works__item">
							<?php
							scala_image(
								$scala_work['image'] ?? 0,
								array(
									'size'        => 'scala-square',
									'sizes'       => '16vw',
									'alt'         => $scala_work['alt'] ?? __( 'Виконана робота', 'scala' ),
									'placeholder' => __( 'Фото роботи', 'scala' ),
								)
							);
							?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $scala_shots ) : ?>
				<div class="trust__label trust__label--gap"><?php esc_html_e( 'Скріншоти з переписок', 'scala' ); ?></div>
				<div class="shots">
					<?php foreach ( $scala_shots as $scala_shot ) : ?>
						<div class="shots__item">
							<?php
							scala_image(
								$scala_shot['image'] ?? 0,
								array(
									'size'        => 'scala-portrait',
									'sizes'       => '(max-width:760px) 33vw, 12vw',
									'alt'         => '',
									'placeholder' => __( 'Скрін переписки', 'scala' ),
								)
							);
							?>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="shots__note">
					<?php esc_html_e( 'Живі повідомлення клієнтів — без правок і копірайтингу.', 'scala' ); ?>
				</div>
			<?php endif; ?>
		</div>

	</div>
</section>
