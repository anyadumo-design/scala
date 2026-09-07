<?php
/**
 * Template Name: SCALA — Про бренд
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();

$scala_services = scala_rows( 'about_services' );
$scala_gallery  = scala_rows( 'about_gallery' );
$scala_body     = (string) scala_opt( 'about_body', '' );
?>

<section class="section--sub">
	<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Про бренд', 'scala' ); ?></div>
	<h1 class="h1-page balance" style="max-width:900px"><?php the_title(); ?></h1>

	<?php if ( scala_opt( 'about_lead' ) ) : ?>
		<p class="about__lead"><?php scala_the( 'about_lead' ); ?></p>
	<?php endif; ?>
</section>

<?php if ( scala_opt( 'about_image' ) ) : ?>
	<section class="section--m44">
		<div class="about__hero">
			<?php
			scala_image(
				(int) scala_opt( 'about_image', 0 ),
				array(
					'size'    => 'scala-lg',
					'sizes'   => '100vw',
					'alt'     => __( 'Інтерʼєр зі шторами Scala', 'scala' ),
					'loading' => 'eager',
				)
			);
			?>
		</div>
	</section>
<?php endif; ?>

<?php if ( scala_opt( 'about_h2' ) || $scala_body ) : ?>
	<section class="section--m88">
		<div class="two-col">
			<h2 class="about__h2"><?php scala_the( 'about_h2' ); ?></h2>
			<div class="about__body">
				<?php
				// Абзаци розділяються порожнім рядком у полі адмінки.
				foreach ( preg_split( '~\R{2,}~', $scala_body ) ?: array() as $scala_par ) {
					$scala_par = trim( $scala_par );

					if ( $scala_par ) {
						printf( '<p>%s</p>', esc_html( $scala_par ) );
					}
				}
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $scala_services ) : ?>
	<section class="section--m88">
		<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Послуги', 'scala' ); ?></div>
		<div class="four-col four-col--tight">
			<?php foreach ( $scala_services as $scala_service ) : ?>
				<div class="cell cell--steps">
					<div class="cell__title" style="margin-top:0">
						<?php echo esc_html( $scala_service['title'] ?? '' ); ?>
					</div>
					<div class="cell__text"><?php echo esc_html( $scala_service['text'] ?? '' ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php
get_template_part(
	'template-parts/prose',
	null,
	array(
		'section_class' => 'section--m88',
		'eyebrow'       => scala_opt( 'about_prose_eyebrow' ),
		'title'         => scala_opt( 'about_prose_title' ),
		'content'       => apply_filters( 'the_content', get_the_content() ),
	)
);
?>

<?php if ( $scala_gallery ) : ?>
	<section class="section--m88">
		<div class="about__gallery">
			<?php foreach ( $scala_gallery as $scala_shot ) : ?>
				<div>
					<?php
					scala_image(
						$scala_shot['image'] ?? 0,
						array(
							'size'        => 'scala-md',
							'sizes'       => '(max-width:620px) 100vw, 33vw',
							'alt'         => $scala_shot['alt'] ?? '',
							'placeholder' => __( 'Кадр із цеху або шоуруму', 'scala' ),
						)
					);
					?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php
get_template_part(
	'template-parts/landing/cta',
	null,
	array(
		'title'  => __( 'Почнемо з вашого вікна', 'scala' ),
		'text'   => __( 'Дизайнер приїде зі зразками й розрахує кошторис під ваш проєкт.', 'scala' ),
		'button' => __( 'Запросити дизайнера', 'scala' ),
		'href'   => scala_page_url( 'contacts' ) ?: '#',
	)
);

get_footer();
