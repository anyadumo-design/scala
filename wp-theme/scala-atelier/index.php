<?php
/**
 * Запасний шаблон. Використовується, коли точнішого немає.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<?php
/*
 * Заголовок сторінки, а не заголовок вкладки: у wp_get_document_title()
 * є назва сайту, і в H1 вона виглядала як «Журнал — Scala».
 */
if ( is_home() ) {
	$scala_blog_id = (int) get_option( 'page_for_posts' );
	$scala_heading = $scala_blog_id ? get_the_title( $scala_blog_id ) : __( 'Журнал', 'scala' );
} elseif ( is_search() ) {
	/* translators: %s — пошуковий запит */
	$scala_heading = sprintf( __( 'Пошук: %s', 'scala' ), get_search_query() );
} else {
	$scala_heading = trim( wp_strip_all_tags( (string) get_the_archive_title() ) );
	$scala_heading = $scala_heading ?: __( 'Матеріали', 'scala' );
}
?>
<section class="section--sub">
	<?php if ( have_posts() ) : ?>
		<h1 class="h1-page balance"><?php echo esc_html( $scala_heading ); ?></h1>
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
