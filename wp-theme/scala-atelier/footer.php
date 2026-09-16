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

/*
 * Види штор у підвалі — наскрізна перелінковка: з будь-якої сторінки
 * видно всі комерційні сторінки, і вони не залежать від того, чи
 * дійшов відвідувач до каталогу.
 */
$scala_types = scala_posts( 'scala_type' );

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

		<?php if ( $scala_types ) : ?>
			<div>
				<div class="footer__label"><?php esc_html_e( 'Види штор', 'scala' ); ?></div>
				<div class="footer__links">
					<?php foreach ( $scala_types as $scala_type ) : ?>
						<a href="<?php echo esc_url( (string) get_permalink( $scala_type ) ); ?>">
							<?php echo esc_html( get_the_title( $scala_type ) ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

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

				/*
				 * Архів видів штор і журнал у меню не потрапляли взагалі:
				 * на них не вело жодне посилання з жодної сторінки, і
				 * пошуковик знаходив їх лише через карту сайту.
				 */
				$scala_extra = array();

				$scala_archives = array(
					'scala_type'    => __( 'Види штор', 'scala' ),
					'scala_project' => __( 'Роботи', 'scala' ),
					'scala_fabric'  => __( 'Тканини', 'scala' ),
				);

				foreach ( $scala_archives as $scala_pt => $scala_label ) {
					$scala_archive = (string) get_post_type_archive_link( $scala_pt );

					if ( $scala_archive ) {
						$scala_extra[ $scala_archive ] = $scala_label;
					}
				}

				$scala_blog_id = (int) get_option( 'page_for_posts' );

				if ( $scala_blog_id ) {
					$scala_extra[ (string) get_permalink( $scala_blog_id ) ] = get_the_title( $scala_blog_id );
				}

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

				foreach ( $scala_extra as $scala_url => $scala_title ) {
					printf( '<a href="%s">%s</a>', esc_url( $scala_url ), esc_html( $scala_title ) );
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

	<div class="footer__spacer"></div>
</footer>

<?php
/*
 * Липка панель на телефоні — на всіх сторінках, не лише на головній.
 * Доки її не було на підсторінках, там лишалась кнопка в шапці й
 * тиснулась упритул до бургера. Тепер головна дія скрізь однакова
 * і завжди під великим пальцем.
 */
$scala_bar_href = $scala_is_home
	? '#request'
	: ( scala_page_url( 'contacts' ) ?: home_url( '/' ) );
?>
<div class="mobile-bar">
	<a href="<?php echo esc_url( $scala_bar_href ); ?>" class="mobile-bar__cta"
	   data-lead-open="<?php echo esc_attr( __( 'Липка панель (моб.) · ', 'scala' ) . wp_get_document_title() ); ?>">
		<?php esc_html_e( 'Запросити дизайнера', 'scala' ); ?>
	</a>
	<?php if ( $scala_phone ) : ?>
		<a href="tel:<?php echo esc_attr( scala_tel( $scala_phone ) ); ?>" class="mobile-bar__call" aria-label="<?php esc_attr_e( 'Подзвонити', 'scala' ); ?>">
			<?php // Символ ☎ на iPhone малюється кольоровим емодзі — тому лінійна іконка. ?>
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 1.1 4.2 2 2 0 0 1 3.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L7.1 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.8 2z"/></svg>
		</a>
	<?php endif; ?>
</div>

<?php get_template_part( 'template-parts/lead-modal' ); ?>

<?php if ( is_front_page() ) : ?>
	<?php get_template_part( 'template-parts/shot-modal' ); ?>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
