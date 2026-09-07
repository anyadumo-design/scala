<?php
/**
 * 04 — Реалізовані проєкти. Горизонтальна стрічка.
 *
 * Снап тут навмисно вимкнено: на картках 620–900px він давав кроки по
 * 600+px і остання картка ставала недосяжною. Скрол вільний, для миші
 * додано перетягування (див. main.js).
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_projects = scala_posts( 'scala_project' );

if ( ! $scala_projects ) {
	return;
}
?>
<section id="projects" class="section--flush">
	<div class="proj__head">
		<div class="eyebrow">
			<span class="eyebrow__num">04</span>
			<span><?php scala_the( 'projects_eyebrow' ); ?></span>
		</div>
		<div class="proj__hint"><?php esc_html_e( 'Гортайте вбік →', 'scala' ); ?></div>
	</div>

	<div class="proj__strip" data-cursor="VIEW" tabindex="0" role="region"
		aria-label="<?php esc_attr_e( 'Реалізовані проєкти, гортається вбік', 'scala' ); ?>">
		<?php foreach ( $scala_projects as $scala_project ) : ?>
			<?php $scala_wide = (int) scala_meta( $scala_project->ID, 'wide', 0 ) === 1; ?>
			<div class="proj__item<?php echo $scala_wide ? ' proj__item--wide' : ''; ?>">
				<div class="proj__shot">
					<?php
					scala_image(
						get_post_thumbnail_id( $scala_project ),
						array(
							'size'        => 'scala-md',
							'sizes'       => $scala_wide ? '74vw' : '52vw',
							'alt'         => (string) scala_meta( $scala_project->ID, 'alt', get_the_title( $scala_project ) ),
							'placeholder' => get_the_title( $scala_project ),
						)
					);
					?>
				</div>
				<div class="proj__cap"><?php echo esc_html( get_the_title( $scala_project ) ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php
	// Банер належить цій же секції: окремою секцією він відривався від
	// стрічки, і між ними лишалось близько 170px порожнечі.
	$scala_cta_title = (string) scala_opt( 'cta2_title', '' );
	?>
	<?php if ( $scala_cta_title ) : ?>
		<div class="proj__cta">
			<div class="ctabar">
				<div>
					<div class="ctabar__title"><?php echo esc_html( $scala_cta_title ); ?></div>
					<?php if ( scala_opt( 'cta2_text' ) ) : ?>
						<div class="ctabar__text"><?php scala_the( 'cta2_text' ); ?></div>
					<?php endif; ?>
				</div>
				<a href="#request" class="btn btn--dark" data-lead-open="<?php echo esc_attr( __( 'Смуга: ', 'scala' ) . $scala_cta_title ); ?>">
					<?php scala_the( 'cta2_btn', __( 'Отримати прорахунок', 'scala' ) ); ?>
				</a>
			</div>
		</div>
	<?php endif; ?>
</section>
