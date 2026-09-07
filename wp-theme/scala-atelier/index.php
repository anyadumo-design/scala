<?php
/**
 * Запасний шаблон. Використовується, коли точнішого немає.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="section--sub">
	<?php if ( have_posts() ) : ?>
		<h1 class="h1-page balance"><?php echo esc_html( wp_get_document_title() ); ?></h1>
		<div class="about__body" style="margin-top:28px">
			<?php while ( have_posts() ) : the_post(); ?>
				<article>
					<h2 class="about__h2" style="font-size:clamp(20px,2.4vw,30px)">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h2>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
		</div>
		<div style="margin-top:32px"><?php posts_nav_link(); ?></div>
	<?php else : ?>
		<h1 class="h1-page"><?php esc_html_e( 'Нічого не знайдено', 'scala' ); ?></h1>
	<?php endif; ?>
</section>
<?php
get_footer();
