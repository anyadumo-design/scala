<?php
/**
 * Головна сторінка — лендинг.
 *
 * Порядок секцій із макета: hero → 01 переваги → 02 сценарії → CTA →
 * 03 види штор → 04 проєкти → CTA → 05 процес → 06 тканини → 07 FAQ →
 * блок довіри → 08 Instagram → 09 заявка.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/landing/hero' );
get_template_part( 'template-parts/landing/benefits' );
get_template_part( 'template-parts/landing/scenarios' );

get_template_part(
	'template-parts/landing/cta',
	null,
	array(
		'title'  => scala_opt( 'cta1_title' ),
		'text'   => scala_opt( 'cta1_text' ),
		'button' => scala_opt( 'cta1_btn' ),
	)
);

get_template_part( 'template-parts/landing/types' );
get_template_part( 'template-parts/landing/projects' );

get_template_part( 'template-parts/landing/process' );
get_template_part( 'template-parts/landing/fabrics' );
get_template_part( 'template-parts/landing/faq' );
// Змістовний текст беремо з вмісту сторінки, призначеної головною.
$scala_front_id = (int) get_option( 'page_on_front' );
$scala_front    = $scala_front_id ? get_post( $scala_front_id ) : null;

if ( $scala_front && trim( (string) $scala_front->post_content ) ) {
	get_template_part(
		'template-parts/prose',
		null,
		array(
			'id'      => 'about-service',
			'eyebrow' => scala_opt( 'home_prose_eyebrow' ),
			'title'   => scala_opt( 'home_prose_title' ),
			'content' => apply_filters( 'the_content', $scala_front->post_content ),
		)
	);
}

get_template_part( 'template-parts/landing/trust' );
get_template_part( 'template-parts/landing/instagram' );
get_template_part( 'template-parts/landing/request' );

get_footer();
