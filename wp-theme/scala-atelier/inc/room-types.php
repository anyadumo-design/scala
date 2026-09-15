<?php
/**
 * Картки видів штор під текстом сторінки кімнати.
 *
 * Сторінки кімнат були суцільним текстом без жодного зображення. Це
 * помітно і людині — стіна тексту про штори, де жодних штор не видно, —
 * і аналізу SEO, який окремо перевіряє наявність зображень і опису alt.
 *
 * Види беремо не зі списку в коді, а з посилань у самому тексті: про
 * що сторінка пише, те й показує. Відредагують текст — набір карток
 * зміниться сам.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

/**
 * Види штор, на які посилається текст сторінки.
 *
 * @param string $content Готовий HTML сторінки.
 * @return array Записи scala_type.
 */
function scala_types_in_content( string $content ): array {
	$archive = (string) get_post_type_archive_link( 'scala_type' );

	if ( ! $archive ) {
		return array();
	}

	$base = wp_parse_url( $archive, PHP_URL_PATH );

	if ( ! $base ) {
		return array();
	}

	if ( ! preg_match_all( '~href="[^"]*' . preg_quote( $base, '~' ) . '([^"/]+)/?"~', $content, $m ) ) {
		return array();
	}

	$slugs = array_values( array_unique( $m[1] ) );

	if ( ! $slugs ) {
		return array();
	}

	$found = get_posts(
		array(
			'post_type'        => 'scala_type',
			'post_status'      => 'publish',
			'post_name__in'    => $slugs,
			'posts_per_page'   => 4,
			'orderby'          => 'post_name__in',
			'suppress_filters' => false,
		)
	);

	return $found ?: array();
}

/**
 * Малює ряд карток.
 *
 * @param array  $types     Записи видів штор.
 * @param string $keyphrase Ключова фраза сторінки — йде в опис першого фото.
 * @return void
 */
function scala_render_room_types( array $types, string $keyphrase = '' ): void {
	if ( ! $types ) {
		return;
	}
	?>
	<section class="section--m44">
		<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Про які штори йдеться', 'scala' ); ?></div>

		<div class="cat-grid cat-grid--four">
			<?php foreach ( $types as $scala_i => $scala_type ) : ?>
				<a href="<?php echo esc_url( (string) get_permalink( $scala_type ) ); ?>" class="cat-card">
					<div class="cat-card__frame">
						<div class="cat-card__zoom">
							<?php
							$scala_name = get_the_title( $scala_type );

							scala_image(
								get_post_thumbnail_id( $scala_type ),
								array(
									'size'        => 'scala-portrait',
									'sizes'       => '(max-width:760px) 50vw, 25vw',
									// Перше фото описуємо фразою сторінки: воно
									// ілюструє саме її тему.
									'alt'         => ( 0 === $scala_i && $keyphrase )
										? $keyphrase . ' — ' . mb_strtolower( $scala_name )
										: $scala_name,
									'placeholder' => $scala_name,
								)
							);
							?>
						</div>
					</div>
					<div class="cat-card__row">
						<span class="cat-card__name"><?php echo esc_html( get_the_title( $scala_type ) ); ?></span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}
