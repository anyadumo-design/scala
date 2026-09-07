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

	<section class="section--m44">
		<div class="about__body" style="max-width:760px">
			<?php the_content(); ?>
		</div>
	</section>
	<?php
endwhile;

get_footer();
