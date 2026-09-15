<?php
/**
 * Відео «штори під ключ».
 *
 * Кадр вертикальний, бо його знімають і дивляться з телефона. На
 * телефоні він і стоїть на всю ширину, на десктопі — у парі з текстом,
 * інакше вертикальне відео або розтягується на пів екрана, або губиться
 * посеред порожнечі.
 *
 * Звук вимкнено навмисно: браузери не дають автозапуску зі звуком, а
 * відео, яке не стартує, гірше за відсутнє. Кнопка звуку поруч.
 *
 * @package scala
 */

defined( 'ABSPATH' ) || exit;

$scala_video_id = (int) scala_opt( 'video_file', 0 );

if ( ! $scala_video_id ) {
	return;
}

$scala_video_url = (string) wp_get_attachment_url( $scala_video_id );

if ( ! $scala_video_url ) {
	return;
}

$scala_poster_id  = (int) scala_opt( 'video_poster', 0 );
$scala_poster_url = $scala_poster_id ? scala_image_url( $scala_poster_id, 'scala-md' ) : '';
$scala_points     = scala_rows( 'video_points' );
$scala_title      = (string) scala_opt( 'video_title', '' );
?>
<section id="turnkey" class="section vid">
	<div class="vid__grid">
		<div class="vid__media">
			<div class="vid__frame">
				<video
					class="vid__player"
					data-scala-video
					src="<?php echo esc_url( $scala_video_url ); ?>"
					<?php echo $scala_poster_url ? 'poster="' . esc_url( $scala_poster_url ) . '"' : ''; ?>
					muted
					loop
					playsinline
					preload="none"
					aria-label="<?php echo esc_attr( $scala_title ? $scala_title : __( 'Відео про пошиття штор', 'scala' ) ); ?>"></video>

				<button type="button" class="vid__play" data-scala-video-play>
					<span class="vid__play-icon" aria-hidden="true">&#9654;</span>
					<span class="visually-hidden"><?php esc_html_e( 'Відтворити відео', 'scala' ); ?></span>
				</button>

				<button type="button" class="vid__sound" data-scala-video-sound
					aria-pressed="false">
					<?php esc_html_e( 'Увімкнути звук', 'scala' ); ?>
				</button>
			</div>
		</div>

		<div class="vid__side">
			<?php if ( scala_opt( 'video_eyebrow' ) ) : ?>
				<div class="eyebrow eyebrow--plain"><?php scala_the( 'video_eyebrow' ); ?></div>
			<?php endif; ?>

			<?php if ( $scala_title ) : ?>
				<h2 class="vid__h2"><?php echo esc_html( $scala_title ); ?></h2>
			<?php endif; ?>

			<?php if ( scala_opt( 'video_text' ) ) : ?>
				<p class="vid__text"><?php scala_the( 'video_text' ); ?></p>
			<?php endif; ?>

			<?php if ( $scala_points ) : ?>
				<ul class="vid__points">
					<?php foreach ( $scala_points as $scala_point ) : ?>
						<?php if ( ! empty( $scala_point['text'] ) ) : ?>
							<li><?php echo esc_html( $scala_point['text'] ); ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( scala_opt( 'video_btn' ) ) : ?>
				<a href="#request" class="btn btn--dark vid__btn"
					data-lead-open="<?php echo esc_attr( __( 'Відео «під ключ»', 'scala' ) ); ?>">
					<?php scala_the( 'video_btn' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
