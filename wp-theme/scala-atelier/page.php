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
	/*
	 * Сторінки кімнат вели на види штор, але не одна на одну й не в
	 * журнал. Людина, яка читає про спальню, наступним питанням має
	 * кухню або карниз — цих переходів тут не було.
	 */
	$scala_rooms   = function_exists( 'scala_room_links' ) ? scala_room_links( (int) get_the_ID() ) : array();
	$scala_seeded  = function_exists( 'scala_is_seeded_guide' ) && scala_is_seeded_guide( (int) get_the_ID() );
	$scala_cornice = $scala_seeded && function_exists( 'scala_cornice_links' ) ? scala_cornice_links() : array();
	?>

	<?php if ( $scala_seeded && $scala_rooms ) : ?>
		<section class="section--m44">
			<p class="lead" style="max-width:760px">
				<?php esc_html_e( 'Інші кімнати:', 'scala' ); ?>
				<?php foreach ( $scala_rooms as $scala_i => $scala_room ) : ?>
					<?php echo $scala_i ? ' · ' : ' '; ?>
					<a href="<?php echo esc_url( $scala_room['url'] ); ?>"><?php echo esc_html( $scala_room['title'] ); ?></a>
				<?php endforeach; ?>
			</p>

			<?php if ( $scala_cornice ) : ?>
				<p class="lead" style="max-width:760px; margin-top:8px">
					<?php esc_html_e( 'Про карнизи:', 'scala' ); ?>
					<?php foreach ( $scala_cornice as $scala_i => $scala_art ) : ?>
						<?php echo $scala_i ? ' · ' : ' '; ?>
						<a href="<?php echo esc_url( $scala_art['url'] ); ?>"><?php echo esc_html( $scala_art['title'] ); ?></a>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

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
