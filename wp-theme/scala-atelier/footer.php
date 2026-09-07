<?php
/**
 * Підвал сайту.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_is_home = is_front_page();
$scala_phone   = (string) scala_opt( 'phone', '' );
$scala_email   = (string) scala_opt( 'email', '' );

$scala_socials = array_filter(
	array(
		'Instagram' => (string) scala_opt( 'instagram', '' ),
		'Telegram'  => (string) scala_opt( 'telegram', '' ),
		'WhatsApp'  => (string) scala_opt( 'whatsapp', '' ),
	)
);
?>
</main>

<footer<?php echo $scala_is_home ? ' id="contacts"' : ''; ?> class="footer<?php echo $scala_is_home ? '' : ' footer--sub'; ?>">
	<div class="footer__grid">

		<div>
			<div class="logo"><?php echo esc_html( get_bloginfo( 'name' ) ?: 'SCALA' ); ?></div>
			<div class="footer__brand-text"><?php scala_the( 'footer_about' ); ?></div>
		</div>

		<div>
			<div class="footer__label"><?php esc_html_e( 'Контакти', 'scala' ); ?></div>
			<div class="footer__contacts">
				<?php if ( $scala_phone ) : ?>
					<a href="tel:<?php echo esc_attr( scala_tel( $scala_phone ) ); ?>"><?php echo esc_html( $scala_phone ); ?></a><br />
				<?php endif; ?>
				<?php if ( $scala_email ) : ?>
					<a href="mailto:<?php echo esc_attr( $scala_email ); ?>"><?php echo esc_html( $scala_email ); ?></a>
				<?php endif; ?>
			</div>
		</div>

		<div>
			<div class="footer__label"><?php esc_html_e( 'Соцмережі', 'scala' ); ?></div>
			<div class="footer__links">
				<?php foreach ( $scala_socials as $scala_label => $scala_url ) : ?>
					<a href="<?php echo esc_url( $scala_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $scala_label ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<div>
			<div class="footer__label">
				<?php echo $scala_is_home ? esc_html__( 'Розділи', 'scala' ) : esc_html__( 'Сторінки', 'scala' ); ?>
			</div>
			<div class="footer__links">
				<?php
				if ( $scala_is_home ) {
					// Якорі секцій цієї сторінки.
					$scala_anchors = array(
						'#solutions' => __( 'Рішення', 'scala' ),
						'#projects'  => __( 'Проєкти', 'scala' ),
						'#process'   => __( 'Як працюємо', 'scala' ),
						'#fabrics'   => __( 'Тканини', 'scala' ),
						'#faq'       => __( 'Питання', 'scala' ),
					);

					foreach ( $scala_anchors as $scala_href => $scala_title ) {
						printf( '<a href="%s">%s</a>', esc_attr( $scala_href ), esc_html( $scala_title ) );
					}
				} else {
					printf(
						'<a href="%s">%s</a>',
						esc_url( home_url( '/' ) ),
						esc_html__( 'Головна', 'scala' )
					);
				}

				// Окремі сторінки. Тонка лінія відділяє їх від якорів вище.
				$scala_pages = array(
					'catalog'  => __( 'Каталог', 'scala' ),
					'about'    => __( 'Про бренд', 'scala' ),
					'contacts' => __( 'Контакти', 'scala' ),
				);

				$scala_first = true;

				foreach ( $scala_pages as $scala_key => $scala_title ) {
					$scala_url = scala_page_url( $scala_key );

					if ( ! $scala_url ) {
						continue;
					}

					printf(
						'<a href="%s"%s>%s</a>',
						esc_url( $scala_url ),
						( $scala_first && $scala_is_home ) ? ' class="is-page-link"' : '',
						esc_html( $scala_title )
					);

					$scala_first = false;
				}
				?>
			</div>
		</div>

	</div>

	<div class="footer__bottom">
		<span>
			<?php
			printf(
				/* translators: 1 — рік, 2 — назва сайту */
				esc_html__( '© %1$s %2$s', 'scala' ),
				esc_html( wp_date( 'Y' ) ),
				esc_html( get_bloginfo( 'name' ) ?: 'SCALA' )
			);
			?>
		</span>
		<?php if ( scala_opt( 'footer_tagline' ) ) : ?>
			<span><?php scala_the( 'footer_tagline' ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( $scala_is_home ) : ?>
		<div class="footer__spacer"></div>
	<?php endif; ?>
</footer>

<?php if ( $scala_is_home ) : ?>
	<div class="mobile-bar">
		<a href="#request" class="mobile-bar__cta" data-lead-open="<?php esc_attr_e( 'Липка панель (моб.)', 'scala' ); ?>"><?php esc_html_e( 'Запросити дизайнера', 'scala' ); ?></a>
		<?php if ( $scala_phone ) : ?>
			<a href="tel:<?php echo esc_attr( scala_tel( $scala_phone ) ); ?>" class="mobile-bar__call" aria-label="<?php esc_attr_e( 'Подзвонити', 'scala' ); ?>">☎</a>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php get_template_part( 'template-parts/lead-modal' ); ?>

<?php wp_footer(); ?>
</body>
</html>
