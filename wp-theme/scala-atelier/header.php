<?php
/**
 * Шапка сайту.
 *
 * На головній хедер фіксований і ущільнюється після 75% висоти екрана
 * (варіант «bar» з макета). На решті сторінок — sticky, бо там немає
 * hero на всю висоту.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_is_home = is_front_page();
$scala_phone   = (string) scala_opt( 'phone', '' );
$scala_tel     = scala_tel( $scala_phone );
// На самій сторінці контактів кнопка має вести до форми, а не сама на себе.
$scala_contacts_url = scala_page_url( 'contacts' );
$scala_on_contacts  = $scala_contacts_url && is_page() && untrailingslashit( (string) get_permalink() ) === untrailingslashit( $scala_contacts_url );

if ( $scala_is_home ) {
	$scala_cta = '#request';
} elseif ( $scala_on_contacts || ! $scala_contacts_url ) {
	$scala_cta = '#form';
} else {
	$scala_cta = $scala_contacts_url;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="visually-hidden" href="#main"><?php esc_html_e( 'Перейти до вмісту', 'scala' ); ?></a>

<?php if ( $scala_is_home ) : ?>
	<div id="cursor" class="cursor" aria-hidden="true"><span>OPEN</span></div>
<?php endif; ?>

<header class="hdr<?php echo $scala_is_home ? '' : ' hdr--sticky'; ?>">
	<div class="hdr__inner">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
			<?php echo esc_html( get_bloginfo( 'name' ) ?: 'SCALA' ); ?>
		</a>

		<nav class="hdr__nav" aria-label="<?php esc_attr_e( 'Головна навігація', 'scala' ); ?>">
			<?php scala_nav( 'primary' ); ?>
		</nav>

		<?php if ( $scala_phone ) : ?>
			<a class="hdr__phone" href="tel:<?php echo esc_attr( $scala_tel ); ?>">
				<?php echo esc_html( $scala_phone ); ?>
			</a>
		<?php endif; ?>

		<a class="hdr__cta hdr__cta--filled" href="<?php echo esc_url( $scala_cta ); ?>" data-lead-open="<?php echo esc_attr( __( 'Хедер', 'scala' ) . ' · ' . wp_get_document_title() ); ?>">
			<?php esc_html_e( 'Запросити дизайнера', 'scala' ); ?>
		</a>

		<?php // Бургер потрібен на всіх сторінках: нижче 1140px меню ховається. ?>
		<button class="burger" data-menu-toggle aria-label="<?php esc_attr_e( 'Меню', 'scala' ); ?>" aria-controls="mmenu" aria-expanded="false">
			<span></span><span></span>
		</button>
	</div>
</header>

<div id="mmenu" class="mmenu">
		<div class="mmenu__top">
			<span class="logo"><?php echo esc_html( get_bloginfo( 'name' ) ?: 'SCALA' ); ?></span>
			<button class="mmenu__close" data-menu-toggle aria-label="<?php esc_attr_e( 'Закрити', 'scala' ); ?>">&times;</button>
		</div>
		<nav class="mmenu__nav" aria-label="<?php esc_attr_e( 'Мобільна навігація', 'scala' ); ?>">
			<?php scala_nav( 'primary' ); ?>
		</nav>
		<div class="mmenu__foot">
			<?php if ( $scala_phone ) : ?>
				<a class="mmenu__phone" href="tel:<?php echo esc_attr( $scala_tel ); ?>">
					<?php echo esc_html( $scala_phone ); ?>
				</a>
			<?php endif; ?>
			<a class="mmenu__cta" href="<?php echo esc_url( $scala_cta ); ?>" data-lead-open="<?php echo esc_attr( __( 'Мобільне меню', 'scala' ) ); ?>"><?php esc_html_e( 'Запросити дизайнера', 'scala' ); ?></a>
		</div>
	</div>

<main id="main">
