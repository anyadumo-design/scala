<?php
/**
 * Головний екран: штори, що роз'їжджаються на скролі.
 *
 * Слоган — великий напис, але НЕ заголовок. H1 віддано рядку з
 * комерційним запитом і містом: це найвагоміший сигнал сторінки.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_room    = (int) scala_opt( 'room_image', 0 );
$scala_curtain = scala_image_url( (int) scala_opt( 'curtain_image', 0 ), 'scala-lg' );
?>
<section id="top" class="hero" data-cursor="OPEN">
	<div class="hero__stage">

		<div class="hero__room">
			<?php
			scala_image(
				$scala_room,
				array(
					'size'          => 'scala-lg',
					'sizes'         => '100vw',
					'alt'           => __( 'Тюль і портьєри на панорамному вікні', 'scala' ),
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'placeholder'   => __( 'Фото кімнати за шторою', 'scala' ),
				)
			);
			?>
			<div class="hero__room-veil"></div>
		</div>

		<div class="hero__reveal">
			<?php
			// Рядок видів штор: заповнює кадр і дає головній посилання
			// на комерційні сторінки — саме їх їй бракувало найбільше.
			$scala_hero_types = array_slice(
				array_filter(
					scala_posts( 'scala_type' ),
					static fn( $post ) => (int) scala_meta( $post->ID, 'on_home', 1 ) === 1
				),
				0,
				6
			);
			?>
			<?php if ( $scala_hero_types ) : ?>
				<div class="hero__reveal-top">
					<span class="hero__reveal-label"><?php esc_html_e( 'Що ми шиємо', 'scala' ); ?></span>
					<nav class="hero__reveal-links" aria-label="<?php esc_attr_e( 'Види штор', 'scala' ); ?>">
						<?php foreach ( $scala_hero_types as $scala_hero_type ) : ?>
							<a href="<?php echo esc_url( get_permalink( $scala_hero_type ) ?: '#solutions' ); ?>">
								<?php echo esc_html( get_the_title( $scala_hero_type ) ); ?>
							</a>
						<?php endforeach; ?>
					</nav>
				</div>
			<?php endif; ?>

			<div class="hero__reveal-inner">
				<div class="hero__reveal-text"><?php scala_the_rich( 'reveal_text' ); ?></div>
				<div class="hero__reveal-cta">
					<a href="#request" class="btn btn--light" data-lead-open="<?php esc_attr_e( 'Hero — під шторою', 'scala' ); ?>"><?php scala_the( 'btn_primary' ); ?></a>
					<a href="#projects" class="btn btn--ghost"><?php scala_the( 'btn_secondary' ); ?></a>
				</div>
			</div>
		</div>

		<?php // Одне фото штори ділиться навпіл і роз'їжджається в різні боки. ?>
		<div class="curtain curtain--l"<?php echo $scala_curtain ? ' style="background-image:url(' . esc_url( $scala_curtain ) . ')"' : ''; ?>>
			<div class="curtain__shade"></div>
		</div>
		<div class="curtain curtain--r"<?php echo $scala_curtain ? ' style="background-image:url(' . esc_url( $scala_curtain ) . ')"' : ''; ?>>
			<div class="curtain__shade"></div>
		</div>

		<div class="hero__text">
			<p class="hero__slogan"><?php scala_the_rich( 'slogan' ); ?></p>
			<h1 class="hero__h1"><?php scala_the( 'h1' ); ?></h1>
			<p class="hero__sub"><?php scala_the( 'sub' ); ?></p>

			<div class="hero__cta">
				<a href="#request" class="btn btn--dark" data-lead-open="<?php esc_attr_e( 'Hero — головна кнопка', 'scala' ); ?>"><?php scala_the( 'btn_primary' ); ?></a>
				<a href="#projects" class="btn btn--outline"><?php scala_the( 'btn_secondary' ); ?></a>
			</div>

			<?php if ( scala_opt( 'hint' ) ) : ?>
				<div class="hero__hint"><?php scala_the( 'hint' ); ?></div>
			<?php endif; ?>
		</div>

	</div>
</section>
