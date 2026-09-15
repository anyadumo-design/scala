<?php
/**
 * SCALA Atelier — точка входу теми.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

define( 'SCALA_VERSION', '1.43.0' );
define( 'SCALA_DIR', get_template_directory() );
define( 'SCALA_URI', get_template_directory_uri() );

/** Ключ опції, у якій лежать усі налаштування теми одним масивом. */
define( 'SCALA_OPT_KEY', 'scala_options' );

require_once SCALA_DIR . '/inc/compat.php';
require_once SCALA_DIR . '/inc/helpers.php';
require_once SCALA_DIR . '/inc/setup.php';
require_once SCALA_DIR . '/inc/enqueue.php';
require_once SCALA_DIR . '/inc/assets-cleanup.php';
require_once SCALA_DIR . '/inc/fields.php';
require_once SCALA_DIR . '/inc/options-schema.php';
require_once SCALA_DIR . '/inc/options-page.php';
require_once SCALA_DIR . '/inc/migrate-page.php';
require_once SCALA_DIR . '/inc/setup-wizard.php';
require_once SCALA_DIR . '/inc/cpt.php';
require_once SCALA_DIR . '/inc/metaboxes.php';
require_once SCALA_DIR . '/inc/traffic.php';
require_once SCALA_DIR . '/inc/leads.php';
require_once SCALA_DIR . '/inc/telegram.php';
require_once SCALA_DIR . '/inc/seo.php';
require_once SCALA_DIR . '/inc/autolink.php';
require_once SCALA_DIR . '/inc/room-types.php';
require_once SCALA_DIR . '/inc/redirects.php';
require_once SCALA_DIR . '/inc/seed-content.php';
require_once SCALA_DIR . '/inc/seed-types.php';
require_once SCALA_DIR . '/inc/seed-posts.php';
require_once SCALA_DIR . '/inc/seed-guides.php';
require_once SCALA_DIR . '/inc/guides.php';
require_once SCALA_DIR . '/inc/upgrade.php';
require_once SCALA_DIR . '/inc/defaults.php';
