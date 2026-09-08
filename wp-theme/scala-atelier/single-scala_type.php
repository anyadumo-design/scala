<?php
/**
 * Сторінка одного виду штор.
 *
 * Текст беремо зі звичайного редактора WordPress і ріжемо по H2 на
 * двоколонкові блоки — так клієнт редагує сторінку звично, а дизайн
 * лишається таким, як задумано. Вступ, тканини й питання лежать
 * в окремих полях, бо вони працюють не лише як текст: тканини ведуть
 * у каталог, а питання йдуть у розмітку FAQ для пошуку.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$scala_id       = get_the_ID();
	$scala_name     = get_the_title();
	$scala_lead     = (string) scala_meta( $scala_id, 'lead', '' );
	$scala_fabrics  = scala_meta_rows( $scala_id, 'fabrics' );
	$scala_faq      = scala_meta_rows( $scala_id, 'faq' );
	$scala_sections = scala_split_sections( apply_filters( 'the_content', get_the_content() ) );
	$scala_contacts = scala_page_url( 'contacts' ) ?: home_url( '/' );
	$scala_catalog  = scala_page_url( 'catalog' ) ?: home_url( '/' );
	$scala_archive  = get_post_type_archive_link( 'scala_type' ) ?: $scala_catalog;

	// Перші три блоки, далі CTA-смуга, далі решта.
	$scala_head_blocks = array_slice( $scala_sections, 0, 3 );
	$scala_tail_blocks = array_slice( $scala_sections, 3 );
	?>

	<section class="section--sub">
		<nav class="crumbs" aria-label="<?php esc_attr_e( 'Хлібні крихти', 'scala' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Головна', 'scala' ); ?></a>
			<span class="crumbs__sep">/</span>
			<a href="<?php echo esc_url( $scala_archive ); ?>"><?php esc_html_e( 'Види штор', 'scala' ); ?></a>
			<span class="crumbs__sep">/</span>
			<span aria-current="page"><?php echo esc_html( $scala_name ); ?></span>
		</nav>

		<h1 class="h1-page balance" style="max-width:1000px"><?php the_title(); ?></h1>

		<?php if ( $scala_lead ) : ?>
			<p class="tp__lead pretty"><?php echo wp_kses( $scala_lead, scala_inline_tags() ); ?></p>
		<?php endif; ?>

		<div style="margin-top:30px;display:flex;gap:12px;flex-wrap:wrap">
			<a href="<?php echo esc_url( $scala_contacts ); ?>" class="btn btn--dark"
			   data-lead-open="<?php echo esc_attr( $scala_name . __( ' · верх сторінки', 'scala' ) ); ?>">
				<?php esc_html_e( 'Запросити дизайнера зі зразками', 'scala' ); ?>
			</a>
			<?php if ( $scala_faq ) : ?>
				<a href="#faq" class="btn btn--outline"><?php esc_html_e( 'Часті питання', 'scala' ); ?></a>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( has_post_thumbnail() ) : ?>
		<section class="section--m44">
			<div class="tp__hero">
				<?php
				the_post_thumbnail(
					'scala-lg',
					array(
						'sizes' => '100vw',
						'class' => 'parallax',
					)
				);
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $scala_head_blocks ) : ?>
		<section class="section--m96">
			<div class="tp__blocks">
				<?php foreach ( $scala_head_blocks as $scala_block ) : ?>
					<?php get_template_part( 'template-parts/type-block', null, $scala_block ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	get_template_part(
		'template-parts/landing/cta',
		null,
		array(
			'title' => __( 'Порахуємо ваше вікно після заміру', 'scala' ),
			'text'  => __( 'Дизайнер приїде зі зразками в межах Києва, зніме розміри й покаже тканину у вашому освітленні. Виїзд безкоштовний і ні до чого не зобовʼязує.', 'scala' ),
			'href'  => $scala_contacts,
		)
	);
	?>

	<?php if ( $scala_tail_blocks ) : ?>
		<section class="section--m96">
			<div class="tp__blocks">
				<?php foreach ( $scala_tail_blocks as $scala_block ) : ?>
					<?php get_template_part( 'template-parts/type-block', null, $scala_block ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $scala_fabrics ) : ?>
		<section class="section--m96">
			<div class="prose-block">
				<div class="prose-block__aside">
					<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Тканини', 'scala' ); ?></div>
					<h2 class="prose-block__title"><?php esc_html_e( 'Що дизайнер привезе на замір', 'scala' ); ?></h2>
				</div>
				<div class="prose">
					<p><?php esc_html_e( 'Нижче — позиції з нашого каталогу, які найчастіше беруть на цю конструкцію. Остаточний вибір роблять на вікні: тканина у вашому освітленні виглядає інакше, ніж на фотографії.', 'scala' ); ?></p>
					<div class="tp__fabrics">
						<?php foreach ( $scala_fabrics as $scala_fabric ) : ?>
							<?php if ( empty( $scala_fabric['name'] ) ) { continue; } ?>
							<a class="tp__fab" href="<?php echo esc_url( $scala_catalog ); ?>"><?php echo esc_html( $scala_fabric['name'] ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $scala_faq ) : ?>
		<section id="faq" class="section section--narrow">
			<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Часті питання', 'scala' ); ?></div>
			<h2 class="h2-section faq__h2">
				<?php
				printf(
					/* translators: %s — назва виду штор */
					esc_html__( '%s: що питають найчастіше', 'scala' ),
					esc_html( $scala_name )
				);
				?>
			</h2>

			<?php foreach ( $scala_faq as $scala_item ) : ?>
				<?php if ( empty( $scala_item['q'] ) || empty( $scala_item['a'] ) ) { continue; } ?>
				<details class="faq-item">
					<summary><?php echo esc_html( $scala_item['q'] ); ?><span class="f-plus">+</span></summary>
					<div class="faq-item__a"><?php echo wp_kses( $scala_item['a'], scala_inline_tags() ); ?></div>
				</details>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>

	<?php
	$scala_others = get_posts(
		array(
			'post_type'        => 'scala_type',
			'posts_per_page'   => 4,
			'post__not_in'     => array( $scala_id ),
			'orderby'          => 'menu_order title',
			'order'            => 'ASC',
			'suppress_filters' => false,
		)
	);
	?>

	<?php if ( $scala_others ) : ?>
		<section class="section--m96">
			<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Інші види штор', 'scala' ); ?></div>
			<h2 class="h2-section"><?php esc_html_e( 'Порівняйте з іншими конструкціями', 'scala' ); ?></h2>
			<div class="tp__more">
				<?php foreach ( $scala_others as $scala_other ) : ?>
					<a href="<?php echo esc_url( (string) get_permalink( $scala_other ) ); ?>">
						<?php echo esc_html( get_the_title( $scala_other ) ); ?>
						<?php $scala_short = (string) scala_meta( $scala_other->ID, 'short', '' ); ?>
						<?php if ( $scala_short ) : ?>
							<span><?php echo esc_html( $scala_short ); ?></span>
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
			'title' => sprintf(
				/* translators: %s — назва виду штор у нижньому регістрі */
				__( 'Готові порахувати %s під ваше вікно', 'scala' ),
				function_exists( 'mb_strtolower' ) ? mb_strtolower( $scala_name, 'UTF-8' ) : strtolower( $scala_name )
			),
			'text'  => __( 'Залиште номер — менеджер звʼяжеться в робочий час, щоб узгодити зручну дату виїзду.', 'scala' ),
			'href'  => $scala_contacts,
		)
	);
	?>

	<?php
endwhile;

get_footer();
