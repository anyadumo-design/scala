<?php
/**
 * Template Name: SCALA — Каталог
 *
 * Каталог зводить в одну сітку види штор, тканини й комплектацію.
 * Окремого типу записів під каталог навмисно немає: інакше ті самі
 * позиції довелося б заводити двічі.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();

/**
 * Збирає позиції каталогу з трьох джерел в один список.
 *
 * @return array
 */
$scala_build_catalog = static function (): array {
	$items = array();

	foreach ( scala_posts( 'scala_type' ) as $post ) {
		if ( (int) scala_meta( $post->ID, 'in_catalog', 1 ) !== 1 ) {
			continue;
		}

		$items[] = array(
			'title' => get_the_title( $post ),
			'tag'   => (string) scala_meta( $post->ID, 'tag', __( 'Види штор', 'scala' ) ),
			'text'  => (string) scala_meta( $post->ID, 'short', (string) $post->post_excerpt ),
			'cat'   => __( 'Види штор', 'scala' ),
			'thumb' => get_post_thumbnail_id( $post ),
			'url'   => get_permalink( $post ),
		);
	}

	foreach ( scala_posts( 'scala_fabric' ) as $post ) {
		$items[] = array(
			'title' => get_the_title( $post ),
			'tag'   => (string) scala_meta( $post->ID, 'tag', __( 'Колекція', 'scala' ) ),
			'text'  => (string) $post->post_excerpt,
			'cat'   => __( 'Тканини', 'scala' ),
			'thumb' => get_post_thumbnail_id( $post ),
			'url'   => get_permalink( $post ),
		);
	}

	foreach ( scala_posts( 'scala_extra' ) as $post ) {
		$items[] = array(
			'title' => get_the_title( $post ),
			'tag'   => (string) scala_meta( $post->ID, 'tag', __( 'Комплектація', 'scala' ) ),
			'text'  => (string) scala_meta( $post->ID, 'short', (string) $post->post_excerpt ),
			'cat'   => __( 'Комплектація', 'scala' ),
			'thumb' => get_post_thumbnail_id( $post ),
			'url'   => scala_page_url( 'contacts' ),
		);
	}

	return $items;
};

$scala_items = $scala_build_catalog();
$scala_cats  = array_values( array_unique( array_column( $scala_items, 'cat' ) ) );
?>

<section class="section--sub">
	<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Каталог', 'scala' ); ?></div>
	<h1 class="h1-page balance"><?php the_title(); ?></h1>

	<?php if ( scala_opt( 'catalog_lead' ) ) : ?>
		<p class="lead"><?php scala_the( 'catalog_lead' ); ?></p>
	<?php endif; ?>

	<?php if ( count( $scala_cats ) > 1 ) : ?>
		<div class="cat__filters" role="group" aria-label="<?php esc_attr_e( 'Фільтр каталогу', 'scala' ); ?>">
			<button type="button" class="chip is-active" data-filter="*" aria-pressed="true">
				<?php esc_html_e( 'Усе', 'scala' ); ?>
			</button>
			<?php foreach ( $scala_cats as $scala_cat ) : ?>
				<button type="button" class="chip" data-filter="<?php echo esc_attr( $scala_cat ); ?>" aria-pressed="false">
					<?php echo esc_html( $scala_cat ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>

<section class="section" style="padding-top:28px">
	<div class="cat-grid">
		<?php foreach ( $scala_items as $scala_item ) : ?>
			<a href="<?php echo esc_url( $scala_item['url'] ?: '#' ); ?>" class="cat-card"
				data-cat="<?php echo esc_attr( $scala_item['cat'] ); ?>">
				<div class="cat-card__frame">
					<div class="cat-card__zoom">
						<?php
						scala_image(
							$scala_item['thumb'],
							array(
								'size'        => 'scala-portrait',
								'sizes'       => '(max-width:760px) 50vw, 25vw',
								'alt'         => $scala_item['title'],
								'placeholder' => $scala_item['title'],
							)
						);
						?>
					</div>
				</div>
				<div class="cat-card__row">
					<span class="cat-card__name"><?php echo esc_html( $scala_item['title'] ); ?></span>
					<span class="cat-card__tag"><?php echo esc_html( $scala_item['tag'] ); ?></span>
				</div>
				<?php if ( $scala_item['text'] ) : ?>
					<div class="cat-card__text"><?php echo esc_html( $scala_item['text'] ); ?></div>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<?php
get_template_part(
	'template-parts/prose',
	null,
	array(
		'eyebrow' => scala_opt( 'catalog_prose_eyebrow' ),
		'title'   => scala_opt( 'catalog_prose_title' ),
		'content' => apply_filters( 'the_content', get_the_content() ),
	)
);

get_template_part(
	'template-parts/landing/cta',
	null,
	array(
		'title'  => __( 'Не знаєте, з чого почати?', 'scala' ),
		'text'   => __( 'Дизайнер привезе зразки з відповідних колекцій і покаже їх на ваших вікнах.', 'scala' ),
		'button' => __( 'Запросити дизайнера', 'scala' ),
		'href'   => scala_page_url( 'contacts' ) ?: '#',
	)
);

get_footer();
