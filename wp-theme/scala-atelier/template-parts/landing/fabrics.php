<?php
/**
 * 06 — Тканини. Картки, що розкриваються.
 *
 * Відео підключається як embed з YouTube/Vimeo за посиланням з
 * адмінки. У прототипі тут був вибір локального файла — у продакшн
 * це не переноситься.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_fabrics = array_filter(
	scala_posts( 'scala_fabric' ),
	static fn( $post ) => (int) scala_meta( $post->ID, 'on_home', 1 ) === 1
);

if ( ! $scala_fabrics ) {
	return;
}

$scala_catalog_url = scala_page_url( 'catalog' );
?>
<section id="fabrics" class="section">
	<div class="eyebrow">
		<span class="eyebrow__num">06</span>
		<span><?php scala_the( 'fabrics_eyebrow' ); ?></span>
	</div>

	<h2 class="h2-section fab__h2 balance"><?php scala_the( 'fabrics_title' ); ?></h2>
	<p class="fab__lead"><?php scala_the( 'fabrics_lead' ); ?></p>

	<div class="fab-grid" data-cursor="FEEL">
		<?php foreach ( $scala_fabrics as $scala_fabric ) : ?>
			<?php
			$scala_gallery = (array) scala_meta( $scala_fabric->ID, 'gallery', array() );
			$scala_video   = (string) scala_meta( $scala_fabric->ID, 'video', '' );
			$scala_title   = get_the_title( $scala_fabric );
			?>
			<details class="fab-card">
				<summary>
					<div class="fab-card__frame">
						<div class="fab-card__zoom">
							<?php
							scala_image(
								get_post_thumbnail_id( $scala_fabric ),
								array(
									'size'        => 'scala-md',
									'sizes'       => '(max-width:760px) 100vw, 33vw',
									'alt'         => $scala_title,
									'placeholder' => $scala_title,
								)
							);
							?>
						</div>
					</div>
					<div class="fab-card__row">
						<span class="fab-card__name"><?php echo esc_html( $scala_title ); ?></span>
						<span class="f-plus">+</span>
					</div>
				</summary>

				<?php if ( $scala_fabric->post_content ) : ?>
					<div class="fab-card__text">
						<?php echo esc_html( wp_strip_all_tags( $scala_fabric->post_content ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $scala_gallery || $scala_video ) : ?>
					<div class="fab-card__body">
						<?php if ( $scala_gallery ) : ?>
							<div class="fab-card__gallery">
								<?php foreach ( $scala_gallery as $scala_shot ) : ?>
									<div class="fab-card__thumb">
										<?php
										scala_image(
											$scala_shot['image'] ?? 0,
											array(
												'size'        => 'scala-square',
												'sizes'       => '(max-width:760px) 33vw, 12vw',
												'alt'         => $scala_shot['alt'] ?? $scala_title,
												'placeholder' => $scala_title,
											)
										);
										?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( $scala_video ) : ?>
							<div class="fab-card__video">
								<?php
								// wp_oembed_get сам віддає iframe потрібного сервісу.
								$scala_embed = wp_oembed_get( $scala_video, array( 'width' => 640 ) );

								if ( $scala_embed ) {
									echo wp_kses(
										$scala_embed,
										array(
											'iframe' => array(
												'src'             => array(),
												'width'           => array(),
												'height'          => array(),
												'frameborder'     => array(),
												'allow'           => array(),
												'allowfullscreen' => array(),
												'title'           => array(),
												'loading'         => array(),
											),
										)
									);
								}
								?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</details>
		<?php endforeach; ?>
	</div>

	<?php if ( $scala_catalog_url ) : ?>
		<a href="<?php echo esc_url( $scala_catalog_url ); ?>" class="link-accent">
			<?php esc_html_e( 'Дивитись колекцію', 'scala' ); ?>
		</a>
	<?php endif; ?>
</section>
