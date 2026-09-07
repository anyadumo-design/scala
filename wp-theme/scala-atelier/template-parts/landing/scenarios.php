<?php
/**
 * 02 — Сценарії світла. Кадри перемикаються прозорістю.
 *
 * Підпис і опис читаються скриптом з data-атрибутів шару, тож текст
 * лишається редагованим і не дублюється в JS.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_scenarios = scala_rows( 'scenarios' );

if ( ! $scala_scenarios ) {
	return;
}

$scala_first = $scala_scenarios[0];
?>
<section id="scenarios" class="section">
	<div class="eyebrow">
		<span class="eyebrow__num">02</span>
		<span><?php scala_the( 'scenarios_eyebrow' ); ?></span>
	</div>

	<h2 class="scen__h2"><?php scala_the( 'scenarios_title' ); ?></h2>

	<div id="scen-stage" class="scen__stage" data-cursor="LIGHT">
		<?php foreach ( $scala_scenarios as $scala_i => $scala_scene ) : ?>
			<div class="scen__layer<?php echo 0 === $scala_i ? ' is-active' : ''; ?>"
				data-label="<?php echo esc_attr( $scala_scene['title'] ?? '' ); ?>"
				data-text="<?php echo esc_attr( $scala_scene['text'] ?? '' ); ?>"
				<?php echo ! empty( $scala_scene['dim'] ) ? ' data-dim="1"' : ''; ?>>
				<?php
				scala_image(
					$scala_scene['image'] ?? 0,
					array(
						'size'        => 'scala-lg',
						'sizes'       => '100vw',
						'class'       => 'parallax',
						'alt'         => $scala_scene['title'] ?? '',
						'placeholder' => __( 'Кадр сценарію', 'scala' ),
					)
				);
				?>
			</div>
		<?php endforeach; ?>

		<div class="scen__dim"></div>
		<div class="scen__veil"></div>

		<div class="scen__bar">
			<div class="scen__state">
				<div class="scen__state-label"><?php echo esc_html( $scala_first['title'] ?? '' ); ?></div>
				<div class="scen__state-text"><?php echo esc_html( $scala_first['text'] ?? '' ); ?></div>
			</div>

			<div id="scen-controls" class="scen__controls" role="tablist"
				aria-label="<?php esc_attr_e( 'Сценарії світла', 'scala' ); ?>">
				<?php foreach ( $scala_scenarios as $scala_i => $scala_scene ) : ?>
					<button type="button" class="scen__btn<?php echo 0 === $scala_i ? ' is-active' : ''; ?>"
						role="tab" aria-selected="<?php echo 0 === $scala_i ? 'true' : 'false'; ?>">
						<?php echo esc_html( $scala_scene['title'] ?? '' ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
