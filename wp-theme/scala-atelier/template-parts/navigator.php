<?php
/**
 * Путівник: куди піти далі.
 *
 * Раніше ці посилання стояли сірими рядками тексту в пів ширини —
 * «За кімнатами: Спальня · Кухня · …». Виглядало як забутий підпис, а
 * не як частина сторінки. Тепер це один блок на всю ширину, однаковий
 * на всіх сторінках: колонки з підписом і переліком.
 *
 * Колонка без жодного посилання не показується, а сітка підлаштовується
 * під ту кількість колонок, яка лишилась.
 *
 * Аргументи:
 * - exclude     ID поточного запису: сторінка не посилається сама на себе;
 * - rooms_label підпис колонки кімнат («Інші кімнати» на сторінці кімнати).
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_exclude = (int) ( $args['exclude'] ?? 0 );
$scala_self    = $scala_exclude ? (string) get_permalink( $scala_exclude ) : '';
$scala_columns = array();

// Порівняння видів — окремою колонкою: одне посилання, але головне.
$scala_guide = function_exists( 'scala_guide_link' ) ? scala_guide_link() : null;

if ( $scala_guide && $scala_self !== $scala_guide['url'] ) {
	$scala_columns[] = array(
		'label' => __( 'Як обрати', 'scala' ),
		'kind'  => 'feature',
		'links' => array( $scala_guide ),
	);
}

$scala_rooms = function_exists( 'scala_room_links' ) ? scala_room_links( $scala_exclude ) : array();

if ( $scala_rooms ) {
	$scala_columns[] = array(
		'label' => (string) ( $args['rooms_label'] ?? __( 'Штори за кімнатами', 'scala' ) ),
		'kind'  => 'rooms',
		'links' => $scala_rooms,
	);
}

$scala_cornice = array();

foreach ( function_exists( 'scala_cornice_links' ) ? scala_cornice_links() : array() as $scala_link ) {
	if ( $scala_self !== $scala_link['url'] ) {
		$scala_cornice[] = $scala_link;
	}
}

if ( $scala_cornice ) {
	$scala_columns[] = array(
		'label' => __( 'Про карнизи', 'scala' ),
		'kind'  => 'list',
		'links' => $scala_cornice,
	);
}

if ( ! $scala_columns ) {
	return;
}
?>
<nav class="section--m96 guide-nav" aria-label="<?php esc_attr_e( 'Путівник', 'scala' ); ?>">
	<div class="guide-nav__grid">
		<?php foreach ( $scala_columns as $scala_col ) : ?>
			<div class="guide-nav__col guide-nav__col--<?php echo esc_attr( $scala_col['kind'] ); ?>">
				<div class="eyebrow eyebrow--plain"><?php echo esc_html( $scala_col['label'] ); ?></div>
				<ul class="guide-nav__list">
					<?php foreach ( $scala_col['links'] as $scala_link ) : ?>
						<li>
							<a href="<?php echo esc_url( $scala_link['url'] ); ?>">
								<span><?php echo esc_html( $scala_link['title'] ); ?></span>
								<span class="guide-nav__arrow" aria-hidden="true">&rarr;</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
</nav>
