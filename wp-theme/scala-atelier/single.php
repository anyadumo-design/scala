<?php
/**
 * Окрема сторінка виду штор, тканини або проєкту.
 *
 * Це основа під майбутню структуру /vydy-shtor/ і /tkanyny/: сюди
 * додається розгорнутий текст, галерея й FAQ конкретного виду.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="section--sub">
		<div class="eyebrow eyebrow--plain">
			<?php
			$scala_obj = get_post_type_object( get_post_type() );
			echo esc_html( $scala_obj ? $scala_obj->labels->singular_name : '' );
			?>
		</div>
		<h1 class="h1-page balance"><?php the_title(); ?></h1>

		<?php if ( has_excerpt() ) : ?>
			<p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
	</section>

	<?php if ( has_post_thumbnail() ) : ?>
		<section class="section--m44">
			<div class="about__hero">
				<?php the_post_thumbnail( 'scala-lg', array( 'sizes' => '100vw' ) ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( get_the_content() ) : ?>
		<section class="section--m88">
			<div class="about__body" style="max-width:760px">
				<?php the_content(); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	get_template_part(
		'template-parts/landing/cta',
		null,
		array(
			'title'  => __( 'Порахуємо під ваші вікна', 'scala' ),
			'text'   => __( 'Дизайнер приїде зі зразками, зробить заміри й розрахує кошторис.', 'scala' ),
			'button' => __( 'Запросити дизайнера', 'scala' ),
			'href'   => scala_page_url( 'contacts' ) ?: home_url( '/' ),
		)
	);
	?>
	<?php
endwhile;

get_footer();
