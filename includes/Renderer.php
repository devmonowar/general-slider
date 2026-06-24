<?php
/**
 * Renders a slider to front-end HTML (shared by the block and the shortcode).
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the Splide markup for a slider.
 */
class Renderer {

	/**
	 * Render a slider.
	 *
	 * @param int   $post_id   Slider post ID.
	 * @param array $overrides Optional settings overrides.
	 * @return string HTML markup (empty string if nothing to show).
	 */
	public static function render( $post_id, $overrides = array() ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || Post_Type::SLUG !== get_post_type( $post_id ) ) {
			return self::notice( __( 'General Slider: please choose a slider.', 'general-slider' ) );
		}

		$slides = Data::get_slides( $post_id );
		if ( empty( $slides ) ) {
			return self::notice( __( 'General Slider: this slider has no slides yet.', 'general-slider' ) );
		}

		$settings = wp_parse_args( $overrides, Data::get_settings( $post_id ) );
		$preset   = array_key_exists( $settings['preset'], Data::presets() ) ? $settings['preset'] : 'hero';

		// Tell the assets to actually load on this request.
		Assets::mark_needed();

		$per_page = 'fade' === $settings['transition'] ? 1 : min( 6, max( 1, absint( $settings['per_page'] ) ) );
		$config   = array(
			'type'       => 'fade' === $settings['transition'] ? 'fade' : ( $settings['loop'] ? 'loop' : 'slide' ),
			'autoplay'   => (bool) $settings['autoplay'],
			'interval'   => max( 1000, absint( $settings['speed'] ) ),
			'arrows'     => (bool) $settings['arrows'],
			'pagination' => (bool) $settings['dots'],
			'perPage'    => $per_page,
			'gap'        => absint( $settings['gap'] ) . 'px',
			'direction'  => is_rtl() ? 'rtl' : 'ltr',
		);

		/**
		 * Filter the Splide configuration passed to the front-end script.
		 *
		 * @param array $config  Splide options.
		 * @param int   $post_id Slider ID.
		 */
		$config = apply_filters( 'general_slider_config', $config, $post_id );

		$classes = sprintf( 'splide gs-slider gs-preset-%s', esc_attr( $preset ) );
		if ( $per_page > 1 ) {
			$classes .= ' gs-multi';
		}
		if ( ! empty( $settings['ken_burns'] ) ) {
			$classes .= ' gs-kenburns';
		}
		if ( ! empty( $settings['animate'] ) ) {
			$classes .= ' gs-animate';
		}
		if ( 'gradient' === ( $settings['overlay_style'] ?? 'solid' ) ) {
			$classes .= ' gs-overlay-gradient';
		}
		$focus  = array_key_exists( $settings['focus'], Data::focus_positions() ) ? $settings['focus'] : 'center';
		$fit    = array_key_exists( $settings['fit'], Data::image_fits() ) ? $settings['fit'] : 'cover';
		$accent = sanitize_hex_color( $settings['accent'] ) ? sanitize_hex_color( $settings['accent'] ) : '#2196f3';
		$style  = sprintf(
			'--gs-overlay:%s;--gs-min-h:%dpx;--gs-focus:%s;--gs-fit:%s;--gs-accent:%s;',
			round( min( 100, absint( $settings['overlay'] ) ) / 100, 2 ),
			max( 120, absint( $settings['height'] ) ),
			$focus,
			$fit,
			$accent
		);

		$custom_css = (string) get_post_meta( $post_id, Data::META_CSS, true );

		ob_start();
		?>
		<?php if ( '' !== trim( $custom_css ) ) : ?>
		<style id="gs-slider-css-<?php echo (int) $post_id; ?>"><?php echo Tools::clean_css( $custom_css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
		<?php endif; ?>
		<div id="gs-slider-<?php echo (int) $post_id; ?>" class="<?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $style ); ?>" role="region" aria-roledescription="carousel" aria-label="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" data-gs="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<div class="splide__track">
				<ul class="splide__list">
					<?php foreach ( $slides as $gs_index => $slide ) : ?>
						<?php $gs_has_link = '' !== $slide['link']; ?>
						<li class="splide__slide gs-slide">
							<?php echo self::slide_media( $slide, 0 === $gs_index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<div class="gs-slide__overlay" aria-hidden="true"></div>
							<?php if ( $gs_has_link ) : ?>
							<a class="gs-slide__content gs-slide__content--link" href="<?php echo esc_url( $slide['link'] ); ?>"<?php echo $slide['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
							<?php else : ?>
							<div class="gs-slide__content">
							<?php endif; ?>
								<?php if ( '' !== $slide['sub_heading'] ) : ?>
									<p class="gs-slide__sub"><?php echo esc_html( $slide['sub_heading'] ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $slide['heading'] ) : ?>
									<h2 class="gs-slide__title"><?php echo esc_html( $slide['heading'] ); ?></h2>
								<?php endif; ?>
								<?php if ( '' !== $slide['text'] ) : ?>
									<div class="gs-slide__text"><?php echo wp_kses_post( $slide['text'] ); ?></div>
								<?php endif; ?>
								<?php if ( '' !== $slide['btn_text'] ) : ?>
									<?php if ( $gs_has_link ) : ?>
										<span class="gs-slide__btn"><?php echo esc_html( $slide['btn_text'] ); ?></span>
									<?php else : ?>
										<a class="gs-slide__btn" href="<?php echo esc_url( $slide['btn_url'] ? $slide['btn_url'] : '#' ); ?>"><?php echo esc_html( $slide['btn_text'] ); ?></a>
									<?php endif; ?>
								<?php endif; ?>
							<?php echo $gs_has_link ? '</a>' : '</div>'; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php if ( $config['autoplay'] ) : ?>
				<button class="splide__toggle gs-toggle" type="button" aria-label="<?php esc_attr_e( 'Pause or play the slider', 'general-slider' ); ?>">
					<span class="splide__toggle__play" aria-hidden="true">&#9658;</span>
					<span class="splide__toggle__pause" aria-hidden="true">&#10074;&#10074;</span>
				</button>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $settings['thumbnails'] ) && count( $slides ) > 1 ) : ?>
			<div class="splide gs-thumbnails" style="--gs-accent:<?php echo esc_attr( $accent ); ?>" aria-label="<?php esc_attr_e( 'Slider thumbnails', 'general-slider' ); ?>">
				<div class="splide__track">
					<ul class="splide__list">
						<?php foreach ( $slides as $gs_i => $gs_thumb ) : ?>
							<li class="splide__slide gs-thumb">
								<?php
								if ( $gs_thumb['image_id'] ) {
									echo wp_get_attachment_image(
										$gs_thumb['image_id'],
										'thumbnail',
										false,
										array(
											'class' => 'gs-thumb__img',
											'loading' => 'lazy',
										)
									); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								} else {
									echo '<span class="gs-thumb__num">' . (int) ( $gs_i + 1 ) . '</span>';
								}
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>
		<?php
		$html = ob_get_clean();

		/**
		 * Filter the final rendered slider HTML.
		 *
		 * @param string $html     Slider markup.
		 * @param int    $post_id  Slider ID.
		 * @param array  $settings Resolved settings.
		 */
		return apply_filters( 'general_slider_html', $html, $post_id, $settings );
	}

	/**
	 * Build the slide media (image) markup.
	 *
	 * @param array $slide Normalised slide.
	 * @return string
	 */
	private static function slide_media( $slide, $is_first = false ) {
		// Background video takes precedence; the image (if any) is used as the poster.
		if ( ! empty( $slide['video'] ) ) {
			$embed = self::video_embed_src( $slide['video'] );
			if ( $embed ) {
				return '<div class="gs-slide__media gs-slide__media--embed"><iframe class="gs-slide__embed" src="' . esc_url( $embed ) . '" title="' . esc_attr( $slide['heading'] ) . '" loading="lazy" allow="autoplay; encrypted-media" aria-hidden="true" tabindex="-1"></iframe></div>';
			}
			// Self-hosted file.
			$poster = ! empty( $slide['image_id'] ) ? wp_get_attachment_image_url( $slide['image_id'], 'full' ) : '';
			$type   = ( false !== strpos( strtolower( $slide['video'] ), '.webm' ) ) ? 'video/webm' : 'video/mp4';
			$html   = '<div class="gs-slide__media"><video class="gs-slide__video" autoplay muted loop playsinline preload="metadata" aria-hidden="true"';
			$html  .= $poster ? ' poster="' . esc_url( $poster ) . '"' : '';
			$html  .= '><source src="' . esc_url( $slide['video'] ) . '" type="' . esc_attr( $type ) . '"></video></div>';
			return $html;
		}

		if ( empty( $slide['image_id'] ) ) {
			return '<div class="gs-slide__media gs-slide__media--empty" aria-hidden="true"></div>';
		}
		// First slide is the likely LCP element: load it eagerly with high priority.
		$attr = array(
			'class'    => 'gs-slide__img',
			'decoding' => 'async',
			'loading'  => $is_first ? 'eager' : 'lazy',
		);
		if ( $is_first ) {
			$attr['fetchpriority'] = 'high';
		}
		// Let WordPress pull the alt text from the attachment itself (better a11y).
		$img = wp_get_attachment_image( $slide['image_id'], 'full', false, $attr );
		return '<div class="gs-slide__media">' . $img . '</div>';
	}

	/**
	 * Build a background-embed URL for a YouTube or Vimeo link.
	 *
	 * @param string $url Raw video URL.
	 * @return string Embed URL, or empty string for non-YouTube/Vimeo links.
	 */
	private static function video_embed_src( $url ) {
		if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
			$id = $m[1];
			return add_query_arg(
				array(
					'autoplay'       => 1,
					'mute'           => 1,
					'controls'       => 0,
					'loop'           => 1,
					'playlist'       => $id,
					'playsinline'    => 1,
					'modestbranding' => 1,
					'rel'            => 0,
					'iv_load_policy' => 3,
				),
				'https://www.youtube.com/embed/' . $id
			);
		}
		if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
			return add_query_arg(
				array(
					'autoplay'   => 1,
					'muted'      => 1,
					'loop'       => 1,
					'background' => 1,
				),
				'https://player.vimeo.com/video/' . $m[1]
			);
		}
		return '';
	}

	/**
	 * A small front-end notice, only shown to users who can edit.
	 *
	 * @param string $message Message text.
	 * @return string
	 */
	private static function notice( $message ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}
		return '<p class="gs-slider-notice">' . esc_html( $message ) . '</p>';
	}
}
