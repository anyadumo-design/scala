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
			/*
			 * Для звичайного запису підпис беремо з назви сторінки журналу.
			 * Стандартна назва типу — «Запис» — це слово з адмінки, і
			 * відвідувачу воно нічого не каже.
			 */
			if ( 'post' === get_post_type() ) {
				$scala_blog_id = (int) get_option( 'page_for_posts' );
				$scala_label   = $scala_blog_id ? get_the_title( $scala_blog_id ) : __( 'Журнал', 'scala' );
			} else {
				$scala_obj   = get_post_type_object( get_post_type() );
				$scala_label = $scala_obj ? $scala_obj->labels->singular_name : '';
			}

			echo esc_html( $scala_label );
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
				<?php
				/*
				 * Опис alt: у завантажених файлах він порожній, а картинка
				 * без опису — це і втрачений сигнал для пошуку, і порожнє
				 * місце для того, хто читає екранним зчитувачем.
				 */
				the_post_thumbnail(
					'scala-lg',
					array(
						'sizes' => '100vw',
						'alt'   => get_the_title(),
					)
				);
				?>
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
	// Ті самі картки, що під сторінками кімнат: стаття посилається на
	// види штор, тож показуємо їх і зображеннями теж.
	if ( function_exists( 'scala_types_in_content' ) ) {
		scala_render_room_types(
			scala_types_in_content( apply_filters( 'the_content', get_the_content() ) ),
			function_exists( 'scala_keyphrase_for' ) ? scala_keyphrase_for( (int) get_the_ID() ) : ''
		);
	}
	?>

	<?php
	if ( 'post' === get_post_type() ) {
		get_template_part( 'template-parts/navigator', null, array( 'exclude' => (int) get_the_ID() ) );
	}
	?>

	<?php
	/*
	 * Статті вели лише на контакти: ні одна на одну, ні на види штор.
	 * Для читача це глухий кут, для пошуковика — сторінка без ваги.
	 */
	$scala_more = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => 3,
			'post__not_in'     => array( get_the_ID() ),
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	);
	?>

	<?php if ( $scala_more ) : ?>
		<section class="section--m88">
			<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Читайте також', 'scala' ); ?></div>
			<div class="tp__more">
				<?php foreach ( $scala_more as $scala_one ) : ?>
					<a href="<?php echo esc_url( (string) get_permalink( $scala_one ) ); ?>">
						<?php echo esc_html( get_the_title( $scala_one ) ); ?>
						<?php $scala_sub = (string) get_the_excerpt( $scala_one ); ?>
						<?php if ( $scala_sub ) : ?>
							<span><?php echo esc_html( wp_trim_words( $scala_sub, 14 ) ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
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
