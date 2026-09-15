<?php
/**
 * Звичайна сторінка без спеціального шаблону.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="section--sub">
		<h1 class="h1-page balance"><?php the_title(); ?></h1>
	</section>

	<?php
	$scala_html  = apply_filters( 'the_content', get_the_content() );
	$scala_types = scala_types_in_content( $scala_html );
	?>

	<section class="section--m44">
		<div class="about__body" style="max-width:760px">
			<?php echo $scala_html; // phpcs:ignore WordPress.Security.EscapeOutput -- вміст уже пройшов the_content. ?>
		</div>
	</section>

	<?php
	scala_render_room_types(
		$scala_types,
		function_exists( 'scala_keyphrase_for' ) ? scala_keyphrase_for( (int) get_the_ID() ) : ''
	);
	?>

	<?php
	// Звичайні сторінки досі закінчувались текстом і нічим більше.
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
