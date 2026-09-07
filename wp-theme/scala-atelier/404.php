<?php
/**
 * Сторінку не знайдено.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="section--sub" style="min-height:46vh">
	<div class="eyebrow eyebrow--plain">404</div>
	<h1 class="h1-page balance"><?php esc_html_e( 'Такої сторінки немає', 'scala' ); ?></h1>
	<p class="lead">
		<?php esc_html_e( 'Можливо, посилання застаріло. Подивіться каталог або напишіть нам — підкажемо.', 'scala' ); ?>
	</p>

	<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:28px">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--dark">
			<?php esc_html_e( 'На головну', 'scala' ); ?>
		</a>
		<?php if ( scala_page_url( 'catalog' ) ) : ?>
			<a href="<?php echo esc_url( scala_page_url( 'catalog' ) ); ?>" class="btn btn--outline">
				<?php esc_html_e( 'Каталог', 'scala' ); ?>
			</a>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
