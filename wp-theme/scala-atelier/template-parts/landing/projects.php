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

/*
 * Проєкти з відео — на початок стрічки. Відео тримає увагу краще за
 * нерухомий кадр, а гортати до нього через півдесятка фотографій ніхто
 * не буде. Порядок усередині кожної групи лишається той, що задали в
 * адмінці.
 */
$scala_with_video = array();
$scala_still      = array();

foreach ( $scala_projects as $scala_project ) {
	if ( (int) scala_meta( $scala_project->ID, 'video', 0 ) ) {
		$scala_with_video[] = $scala_project;
	} else {
		$scala_still[] = $scala_project;
	}
}

$scala_projects = array_merge( $scala_with_video, $scala_still );
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
			<?php
			$scala_video_id  = (int) scala_meta( $scala_project->ID, 'video', 0 );
			$scala_video_url = $scala_video_id ? (string) wp_get_attachment_url( $scala_video_id ) : '';
			$scala_wide      = ! $scala_video_url && (int) scala_meta( $scala_project->ID, 'wide', 0 ) === 1;
			$scala_poster    = get_post_thumbnail_id( $scala_project );
			?>
			<div class="proj__item<?php echo $scala_wide ? ' proj__item--wide' : ''; ?><?php echo $scala_video_url ? ' proj__item--video' : ''; ?>">
				<div class="proj__shot">
					<?php if ( $scala_video_url ) : ?>
						<video
							class="proj__video"
							data-scala-video
							src="<?php echo esc_url( $scala_video_url ); ?>"
							<?php echo $scala_poster ? 'poster="' . esc_url( (string) scala_image_url( $scala_poster, 'scala-md' ) ) . '"' : ''; ?>
							muted
							loop
							playsinline
							preload="none"
							aria-label="<?php echo esc_attr( get_the_title( $scala_project ) ); ?>"></video>
						<button type="button" class="proj__sound" data-scala-video-sound aria-pressed="false">
							<?php esc_html_e( 'Звук', 'scala' ); ?>
						</button>
					<?php else : ?>
						<?php
						scala_image(
							$scala_poster,
							array(
								'size'        => 'scala-md',
								'sizes'       => $scala_wide ? '74vw' : '52vw',
								'alt'         => (string) scala_meta( $scala_project->ID, 'alt', get_the_title( $scala_project ) ),
								'placeholder' => get_the_title( $scala_project ),
							)
						);
						?>
					<?php endif; ?>
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
