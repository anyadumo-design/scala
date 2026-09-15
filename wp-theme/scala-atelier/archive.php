<?php
/**
 * Архів типу записів: усі види штор, тканини або проєкти.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="section--sub">
	<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Каталог', 'scala' ); ?></div>
	<h1 class="h1-page balance"><?php post_type_archive_title(); ?></h1>

	<?php
	// Провідник для тих, хто ще не знає, який тип шукає.
	$scala_guide = is_post_type_archive( 'scala_type' ) ? scala_guide_link() : null;
	?>

	<?php if ( $scala_guide ) : ?>
		<p class="lead" style="margin-top:18px">
			<?php esc_html_e( 'Не знаєте, який тип обрати?', 'scala' ); ?>
			<a href="<?php echo esc_url( $scala_guide['url'] ); ?>"><?php echo esc_html( $scala_guide['title'] ); ?></a>
		</p>
	<?php endif; ?>

	<?php $scala_rooms = is_post_type_archive( 'scala_type' ) ? scala_room_links() : array(); ?>

	<?php if ( $scala_rooms ) : ?>
		<p class="lead" style="margin-top:10px">
			<?php esc_html_e( 'За кімнатами:', 'scala' ); ?>
			<?php foreach ( $scala_rooms as $scala_i => $scala_room ) : ?>
				<?php echo $scala_i ? ' · ' : ' '; ?>
				<a href="<?php echo esc_url( $scala_room['url'] ); ?>"><?php echo esc_html( $scala_room['title'] ); ?></a>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>

	<?php
	$scala_cornice = is_post_type_archive( 'scala_type' ) && function_exists( 'scala_cornice_links' )
		? scala_cornice_links()
		: array();
	?>

	<?php if ( $scala_cornice ) : ?>
		<p class="lead" style="margin-top:10px">
			<?php esc_html_e( 'Про карнизи:', 'scala' ); ?>
			<?php foreach ( $scala_cornice as $scala_i => $scala_art ) : ?>
				<?php echo $scala_i ? ' · ' : ' '; ?>
				<a href="<?php echo esc_url( $scala_art['url'] ); ?>"><?php echo esc_html( $scala_art['title'] ); ?></a>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>
</section>

<section class="section" style="padding-top:28px">
	<div class="cat-grid">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<a href="<?php the_permalink(); ?>" class="cat-card">
				<div class="cat-card__frame">
					<div class="cat-card__zoom">
						<?php
						scala_image(
							get_post_thumbnail_id(),
							array(
								'size'        => 'scala-portrait',
								'sizes'       => '(max-width:760px) 50vw, 25vw',
								'alt'         => get_the_title(),
								'placeholder' => get_the_title(),
							)
						);
						?>
					</div>
				</div>
				<div class="cat-card__row">
					<span class="cat-card__name"><?php the_title(); ?></span>
				</div>
				<?php
				// На картці показуємо короткий опис, а не excerpt: там лежить
				// текст для сніпета пошуку, довший за розмір картки.
				$scala_card_text = (string) scala_meta( get_the_ID(), 'short', '' );

				if ( ! $scala_card_text && has_excerpt() ) {
					$scala_card_text = (string) get_the_excerpt();
				}
				?>
				<?php if ( $scala_card_text ) : ?>
					<div class="cat-card__text"><?php echo esc_html( $scala_card_text ); ?></div>
				<?php endif; ?>
			</a>
			<?php
		endwhile;
		?>
	</div>
</section>
<?php
get_footer();
