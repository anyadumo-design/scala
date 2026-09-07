<?php
/**
 * Template Name: SCALA — Контакти
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();

$scala_phone = (string) scala_opt( 'phone', '' );
$scala_email = (string) scala_opt( 'email', '' );
$scala_map   = (string) scala_opt( 'map_embed', '' );

$scala_msgs = array_filter(
	array(
		'Telegram'         => (string) scala_opt( 'telegram', '' ),
		'WhatsApp'         => (string) scala_opt( 'whatsapp', '' ),
		'Instagram Direct' => (string) scala_opt( 'instagram', '' ),
	)
);
?>

<section class="section--sub">
	<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Контакти', 'scala' ); ?></div>
	<h1 class="h1-page"><?php the_title(); ?></h1>

	<?php if ( scala_opt( 'contacts_lead' ) ) : ?>
		<p class="lead"><?php scala_the( 'contacts_lead' ); ?></p>
	<?php endif; ?>

	<div class="four-col contacts-grid">

		<?php if ( $scala_phone ) : ?>
			<div class="cell cell--steps">
				<div class="contacts__label"><?php esc_html_e( 'Телефон', 'scala' ); ?></div>
				<a class="contacts__value" href="tel:<?php echo esc_attr( scala_tel( $scala_phone ) ); ?>">
					<?php echo esc_html( $scala_phone ); ?>
				</a>
				<div class="contacts__text"><?php esc_html_e( 'Дзвінки та Viber на цей номер.', 'scala' ); ?></div>
			</div>
		<?php endif; ?>

		<?php if ( $scala_email ) : ?>
			<div class="cell cell--steps">
				<div class="contacts__label"><?php esc_html_e( 'Пошта', 'scala' ); ?></div>
				<a class="contacts__value contacts__value--sm" href="mailto:<?php echo esc_attr( $scala_email ); ?>">
					<?php echo esc_html( $scala_email ); ?>
				</a>
				<div class="contacts__text"><?php esc_html_e( 'Для проєктів, комерційних обʼєктів і дизайнерів.', 'scala' ); ?></div>
			</div>
		<?php endif; ?>

		<?php if ( $scala_msgs ) : ?>
			<div class="cell cell--steps">
				<div class="contacts__label"><?php esc_html_e( 'Месенджери', 'scala' ); ?></div>
				<div class="contacts__msgs">
					<?php foreach ( $scala_msgs as $scala_label => $scala_url ) : ?>
						<a href="<?php echo esc_url( $scala_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $scala_label ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="cell cell--steps">
			<div class="contacts__label"><?php esc_html_e( 'Шоурум і графік', 'scala' ); ?></div>
			<?php if ( scala_opt( 'address' ) ) : ?>
				<div class="contacts__value contacts__value--sm"><?php scala_the( 'address' ); ?></div>
			<?php endif; ?>
			<div class="contacts__hours"><?php scala_the( 'hours' ); ?></div>
		</div>

	</div>

	<?php if ( $scala_map ) : ?>
		<div class="contacts__map">
			<iframe src="<?php echo esc_url( $scala_map ); ?>" loading="lazy"
				title="<?php esc_attr_e( 'Мапа проїзду до шоуруму', 'scala' ); ?>"
				referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
		</div>
	<?php endif; ?>
</section>

<?php
get_template_part(
	'template-parts/prose',
	null,
	array(
		'section_class' => 'section--m88',
		'eyebrow'       => scala_opt( 'contacts_prose_eyebrow' ),
		'title'         => scala_opt( 'contacts_prose_title' ),
		'content'       => apply_filters( 'the_content', get_the_content() ),
	)
);
?>

<section id="form" class="section--m88">
	<div class="request__box two-col">
		<div class="request__pane">
			<div class="eyebrow eyebrow--plain"><?php esc_html_e( 'Заявка', 'scala' ); ?></div>
			<h2 class="request__h2" style="font-size:clamp(28px,3.4vw,48px);line-height:1.05;margin-top:20px">
				<?php scala_the( 'title' ); ?>
			</h2>
			<p class="request__text" style="max-width:460px"><?php scala_the( 'text' ); ?></p>

			<?php get_template_part( 'template-parts/form', null, array( 'source' => __( 'Форма на сторінці контактів', 'scala' ) ) ); ?>
		</div>

		<div class="request__photo">
			<?php
			scala_image(
				(int) scala_opt( 'photo', 0 ),
				array(
					'size'        => 'scala-md',
					'sizes'       => '(max-width:760px) 100vw, 50vw',
					'alt'         => __( 'Штори Scala', 'scala' ),
					'placeholder' => __( 'Фото біля форми', 'scala' ),
				)
			);
			?>
		</div>
	</div>
</section>

<?php
get_footer();
