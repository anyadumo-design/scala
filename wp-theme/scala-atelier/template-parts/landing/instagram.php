<?php
/**
 * 08 — Instagram.
 *
 * Плитки редагуються в розділі «Instagram»: фото + посилання на допис.
 * Автопідтягування стрічки тут навмисно немає — Instagram Basic Display
 * API вимкнено у грудні 2024, а Graph API вимагає бізнес-акаунт і токен
 * з оновленням кожні 60 днів. Ручні плитки не ламаються без нагляду.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_tiles   = scala_posts( 'scala_ig' );
$scala_profile = (string) scala_opt( 'instagram', '' );
$scala_handle  = (string) scala_opt( 'instagram_handle', '' );

if ( ! $scala_profile ) {
	return;
}
?>
<section id="instagram" class="section--m96">
	<div class="ig__head">
		<div>
			<div class="eyebrow">
				<span class="eyebrow__num">08</span>
				<span><?php scala_the( 'ig_eyebrow' ); ?></span>
			</div>

			<h2 class="h2-section ig__h2">
				<?php esc_html_e( 'Щодня нові вікна в', 'scala' ); ?>
				<a href="<?php echo esc_url( $scala_profile ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $scala_handle ); ?>
				</a>
			</h2>

			<p class="ig__text"><?php scala_the( 'ig_lead' ); ?></p>
		</div>

		<a href="<?php echo esc_url( $scala_profile ); ?>" class="btn btn--dark btn--sm"
			target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Підписатися', 'scala' ); ?>
		</a>
	</div>

	<?php if ( $scala_tiles ) : ?>
		<div class="ig-grid" data-cursor="INSTA">
			<?php foreach ( $scala_tiles as $scala_tile ) : ?>
				<?php
				$scala_url = (string) scala_meta( $scala_tile->ID, 'url', '' );
				$scala_alt = (string) scala_meta( $scala_tile->ID, 'alt', get_the_title( $scala_tile ) );
				?>
				<a href="<?php echo esc_url( $scala_url ?: $scala_profile ); ?>"
					target="_blank" rel="noopener noreferrer"
					aria-label="<?php echo esc_attr( $scala_url ? __( 'Відкрити допис в Instagram', 'scala' ) : __( 'Відкрити профіль в Instagram', 'scala' ) ); ?>">
					<?php
					scala_image(
						get_post_thumbnail_id( $scala_tile ),
						array(
							'size'        => 'scala-square',
							'sizes'       => '(max-width:1100px) 33vw, 190px',
							'alt'         => $scala_alt,
							'placeholder' => __( 'Допис', 'scala' ),
						)
					);
					?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
