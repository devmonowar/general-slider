<?php
/**
 * Central data definitions: meta keys, presets, defaults and getters.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for the slider data model.
 */
class Data {

	const META_SLIDES   = '_gs_slides';
	const META_SETTINGS = '_gs_settings';
	const META_CSS      = '_gs_custom_css';
	const OPTION_KEY    = 'general_slider_settings';

	/**
	 * Available design presets (skins).
	 *
	 * @return array<string,string> key => label.
	 */
	public static function presets() {
		/**
		 * Filter the available design presets.
		 *
		 * @param array<string,string> $presets key => label. Add your own preset
		 *                                       and provide a `.gs-preset-{key}` stylesheet.
		 */
		return apply_filters(
			'general_slider_presets',
			array(
				'hero'        => __( 'Hero fullwidth', 'general-slider' ),
				'split'       => __( 'Split business', 'general-slider' ),
				'minimal'     => __( 'Minimal centered', 'general-slider' ),
				'testimonial' => __( 'Testimonial', 'general-slider' ),
				'fullscreen'  => __( 'Fullscreen', 'general-slider' ),
			)
		);
	}

	/**
	 * Available navigation & frame skins as key => label.
	 *
	 * Presets control the LAYOUT of a slide; skins control the CHROME — the
	 * look of the arrows, dots and slider frame. Any skin works with any
	 * preset. Single source of truth: the settings UIs, the sanitiser and the
	 * wrapper class all read this list, so a new skin only needs an entry
	 * here plus a `.gs-skin-{key}` block in frontend.css.
	 *
	 * @return array<string,string> key => label.
	 */
	public static function skins() {
		/**
		 * Filter the available navigation & frame skins.
		 *
		 * @param array<string,string> $skins key => label. Add your own skin
		 *                                    and provide a `.gs-skin-{key}` stylesheet.
		 */
		return apply_filters(
			'general_slider_skins',
			array(
				'classic' => __( 'Classic', 'general-slider' ),
				'soft'    => __( 'Soft', 'general-slider' ),
				'stories' => __( 'Stories', 'general-slider' ),
				'progress' => __( 'Stories Progress', 'general-slider' ),
				'numbers' => __( 'Numbers', 'general-slider' ),
				'vertical' => __( 'Vertical', 'general-slider' ),
				'corner'  => __( 'Corner', 'general-slider' ),
				'neon'    => __( 'Neon', 'general-slider' ),
				'minimal' => __( 'Minimal', 'general-slider' ),
				'pill'    => __( 'Pill', 'general-slider' ),
				'outline' => __( 'Outline', 'general-slider' ),
				'retro'   => __( 'Retro', 'general-slider' ),
				'glass'   => __( 'Glass', 'general-slider' ),
				'dark'    => __( 'Dark', 'general-slider' ),
			)
		);
	}

	/**
	 * Available text entrance-animation presets.
	 *
	 * Each preset staggers the sub-heading, heading, text and button in one
	 * after another; only the movement differs. Purely CSS-driven.
	 *
	 * @return array<string,string>
	 */
	public static function animations() {
		return array(
			'none'        => __( 'None', 'general-slider' ),
			'fade'        => __( 'Fade in', 'general-slider' ),
			'fade-up'     => __( 'Fade up', 'general-slider' ),
			'fade-down'   => __( 'Fade down', 'general-slider' ),
			'slide-left'  => __( 'Slide from left', 'general-slider' ),
			'slide-right' => __( 'Slide from right', 'general-slider' ),
			'zoom'        => __( 'Zoom in', 'general-slider' ),
		);
	}

	/**
	 * Resolve the effective animation preset from settings, honouring the
	 * legacy `animate` boolean (true → "fade-up") for sliders saved before
	 * presets existed.
	 *
	 * @param array $settings Resolved settings.
	 * @return string A key from self::animations().
	 */
	public static function resolve_animation( $settings ) {
		$animation = is_string( $settings['animation'] ?? null ) ? $settings['animation'] : 'none';
		if ( ( '' === $animation || 'none' === $animation ) && ! empty( $settings['animate'] ) ) {
			$animation = 'fade-up';
		}
		return array_key_exists( $animation, self::animations() ) ? $animation : 'none';
	}

	/**
	 * Available slide transitions.
	 *
	 * @return array<string,string>
	 */
	public static function transitions() {
		return array(
			'slide' => __( 'Slide', 'general-slider' ),
			'fade'  => __( 'Fade', 'general-slider' ),
		);
	}

	/**
	 * Available image focus (object-position) values.
	 *
	 * @return array<string,string>
	 */
	public static function focus_positions() {
		return array(
			'center' => __( 'Center', 'general-slider' ),
			'top'    => __( 'Top', 'general-slider' ),
			'bottom' => __( 'Bottom', 'general-slider' ),
		);
	}

	/**
	 * Available image fit (object-fit) modes.
	 *
	 * @return array<string,string>
	 */
	public static function image_fits() {
		return array(
			'cover'   => __( 'Cover (fill the slide)', 'general-slider' ),
			'contain' => __( 'Contain (show whole image)', 'general-slider' ),
		);
	}

	/**
	 * Available overlay styles.
	 *
	 * @return array<string,string>
	 */
	public static function overlay_styles() {
		return array(
			'solid'    => __( 'Solid', 'general-slider' ),
			'gradient' => __( 'Gradient', 'general-slider' ),
		);
	}

	/**
	 * Hard-coded fallback defaults for slider settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'preset'     => 'hero',
			'skin'       => 'classic',
			'autoplay'   => false,
			'speed'      => 5000,
			'loop'       => true,
			'arrows'     => true,
			'dots'       => true,
			'transition' => 'slide',
			'transition_speed' => 600,
			'overlay'    => 45,
			'overlay_style' => 'solid',
			'height'     => 560,
			'focus'      => 'center',
			'fit'        => 'cover',
			'per_page'   => 1,
			'gap'        => 16,
			'accent'     => '#2196f3',
			'ken_burns'  => false,
			'animate'    => false,
			'animation'  => 'none',
			'thumbnails' => false,
			'responsive' => array(
				'tablet' => array(),
				'mobile' => array(),
			),
			'source'     => self::default_source(),
		);
	}

	/**
	 * Default "slides source" config — a manual slider by default.
	 *
	 * @return array
	 */
	public static function default_source() {
		return array(
			'type'          => 'manual',
			'post_type'     => 'post',
			'taxonomy'      => '',
			'term'          => 0,
			'orderby'       => 'date',
			'order'         => 'DESC',
			'count'         => 6,
			'show_excerpt'  => true,
			'excerpt_words' => 20,
			'button_text'   => '',
		);
	}

	/**
	 * Allowed "orderby" values for a dynamic slider.
	 *
	 * @return array<string,string>
	 */
	public static function source_orderbys() {
		return array(
			'date'       => __( 'Date', 'general-slider' ),
			'title'      => __( 'Title', 'general-slider' ),
			'menu_order' => __( 'Menu order', 'general-slider' ),
			'rand'       => __( 'Random', 'general-slider' ),
		);
	}

	/**
	 * Sanitise the "slides source" config.
	 *
	 * @param mixed $input Raw source input.
	 * @return array
	 */
	public static function sanitize_source( $input ) {
		$input = is_array( $input ) ? $input : array();
		return array(
			'type'          => 'dynamic' === ( $input['type'] ?? '' ) ? 'dynamic' : 'manual',
			'post_type'     => ( '' !== ( $input['post_type'] ?? '' ) ) ? sanitize_key( $input['post_type'] ) : 'post',
			'taxonomy'      => sanitize_key( $input['taxonomy'] ?? '' ),
			'term'          => absint( $input['term'] ?? 0 ),
			'orderby'       => array_key_exists( ( $input['orderby'] ?? '' ), self::source_orderbys() ) ? $input['orderby'] : 'date',
			'order'         => 'ASC' === strtoupper( (string) ( $input['order'] ?? '' ) ) ? 'ASC' : 'DESC',
			'count'         => min( 20, max( 1, absint( $input['count'] ?? 6 ) ) ),
			'show_excerpt'  => ! empty( $input['show_excerpt'] ),
			'excerpt_words' => min( 100, max( 0, absint( $input['excerpt_words'] ?? 20 ) ) ),
			'button_text'   => sanitize_text_field( $input['button_text'] ?? '' ),
		);
	}

	/**
	 * Sanitise one breakpoint's overrides (tablet or mobile).
	 *
	 * Every field is optional — an unset field means "inherit the desktop
	 * value", so only fields the user actually touched are stored.
	 *
	 * @param mixed $input Raw breakpoint input.
	 * @return array
	 */
	private static function sanitize_responsive_bp( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = array();

		if ( '' !== ( $input['per_page'] ?? '' ) ) {
			$out['per_page'] = min( 6, max( 1, absint( $input['per_page'] ) ) );
		}
		if ( '' !== ( $input['gap'] ?? '' ) ) {
			$out['gap'] = min( 100, absint( $input['gap'] ) );
		}
		if ( '' !== ( $input['height'] ?? '' ) ) {
			$out['height'] = min( 1200, max( 120, absint( $input['height'] ) ) );
		}
		if ( in_array( ( $input['arrows'] ?? '' ), array( 'show', 'hide' ), true ) ) {
			$out['arrows'] = 'show' === $input['arrows'];
		}
		if ( in_array( ( $input['dots'] ?? '' ), array( 'show', 'hide' ), true ) ) {
			$out['dots'] = 'show' === $input['dots'];
		}
		if ( ! empty( $input['hide_content'] ) ) {
			$out['hide_content'] = true;
		}

		return $out;
	}

	/**
	 * Sanitise a raw settings array against the allowed values.
	 * Shared by the per-slider meta box and the global settings page.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();

		// Animation preset, with backward compatibility for the old boolean.
		$anim_in   = $input['animation'] ?? '';
		$animation = array_key_exists( $anim_in, self::animations() )
			? $anim_in
			: ( ! empty( $input['animate'] ) ? 'fade-up' : 'none' );

		return array(
			'preset'     => array_key_exists( ( $input['preset'] ?? '' ), self::presets() ) ? $input['preset'] : 'hero',
			'skin'       => array_key_exists( ( $input['skin'] ?? '' ), self::skins() ) ? $input['skin'] : 'classic',
			'transition' => array_key_exists( ( $input['transition'] ?? '' ), self::transitions() ) ? $input['transition'] : 'slide',
			'transition_speed' => min( 3000, max( 100, absint( $input['transition_speed'] ?? 600 ) ) ),
			'autoplay'   => ! empty( $input['autoplay'] ),
			'speed'      => max( 1000, absint( $input['speed'] ?? 5000 ) ),
			'loop'       => ! empty( $input['loop'] ),
			'arrows'     => ! empty( $input['arrows'] ),
			'dots'       => ! empty( $input['dots'] ),
			'overlay'    => min( 100, absint( $input['overlay'] ?? 45 ) ),
			'overlay_style' => array_key_exists( ( $input['overlay_style'] ?? '' ), self::overlay_styles() ) ? $input['overlay_style'] : 'solid',
			'height'     => min( 1200, max( 120, absint( $input['height'] ?? 560 ) ) ),
			'focus'      => array_key_exists( ( $input['focus'] ?? '' ), self::focus_positions() ) ? $input['focus'] : 'center',
			'fit'        => array_key_exists( ( $input['fit'] ?? '' ), self::image_fits() ) ? $input['fit'] : 'cover',
			'per_page'   => min( 6, max( 1, absint( $input['per_page'] ?? 1 ) ) ),
			'gap'        => min( 100, absint( $input['gap'] ?? 16 ) ),
			'accent'     => sanitize_hex_color( $input['accent'] ?? '' ) ? sanitize_hex_color( $input['accent'] ) : '#2196f3',
			'ken_burns'  => ! empty( $input['ken_burns'] ),
			'animation'  => $animation,
			'animate'    => 'none' !== $animation,
			'thumbnails' => ! empty( $input['thumbnails'] ),
			'responsive' => array(
				'tablet' => self::sanitize_responsive_bp( $input['responsive']['tablet'] ?? array() ),
				'mobile' => self::sanitize_responsive_bp( $input['responsive']['mobile'] ?? array() ),
			),
			'source'     => self::sanitize_source( $input['source'] ?? array() ),
		);
	}

	/**
	 * Global default settings (from the Settings page), merged over the hard defaults.
	 *
	 * @return array
	 */
	public static function global_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::default_settings() );
	}

	/**
	 * Effective settings for one slider: global defaults overridden by per-slider values.
	 *
	 * @param int $post_id Slider ID.
	 * @return array
	 */
	public static function get_settings( $post_id ) {
		$per_slider = get_post_meta( $post_id, self::META_SETTINGS, true );
		$per_slider = is_array( $per_slider ) ? $per_slider : array();
		$settings   = wp_parse_args( $per_slider, self::global_settings() );

		/**
		 * Filter the effective settings for a slider.
		 *
		 * @param array $settings Resolved settings.
		 * @param int   $post_id  Slider ID.
		 */
		return apply_filters( 'general_slider_settings', $settings, $post_id );
	}

	/**
	 * Slides for a slider, normalised to a list of associative arrays.
	 *
	 * @param int $post_id Slider ID.
	 * @return array<int,array>
	 */
	public static function get_slides( $post_id ) {
		$settings = self::get_settings( $post_id );
		$source   = is_array( $settings['source'] ?? null ) ? $settings['source'] : array();

		if ( 'dynamic' === ( $source['type'] ?? 'manual' ) ) {
			// Dynamic sliders build their slides from a WP_Query, mapped onto
			// the same slide structure so every preset and skin still applies.
			$slides = Dynamic_Slides::get( $post_id, $source );
		} else {
			$slides = get_post_meta( $post_id, self::META_SLIDES, true );
			$slides = ( empty( $slides ) || ! is_array( $slides ) )
				? array()
				: array_values( array_filter( array_map( array( __CLASS__, 'normalise_slide' ), $slides ) ) );
		}

		/**
		 * Filter the slides of a slider before they are rendered.
		 *
		 * @param array $slides  Normalised slides.
		 * @param int   $post_id Slider ID.
		 */
		return apply_filters( 'general_slider_slides', $slides, $post_id );
	}

	/**
	 * Normalise / sanitise one raw slide array.
	 *
	 * @param mixed $slide Raw slide data.
	 * @return array|null
	 */
	public static function normalise_slide( $slide ) {
		if ( ! is_array( $slide ) ) {
			return null;
		}
		$defaults = array(
			'image_id'    => 0,
			'video'       => '',
			'sub_heading' => '',
			'heading'     => '',
			'text'        => '',
			'btn_text'    => '',
			'btn_url'     => '',
			'link'        => '',
			'new_tab'     => false,
		);
		$slide    = wp_parse_args( $slide, $defaults );

		return array(
			'image_id'    => absint( $slide['image_id'] ),
			'video'       => esc_url_raw( $slide['video'] ),
			'sub_heading' => sanitize_text_field( $slide['sub_heading'] ),
			'heading'     => sanitize_text_field( $slide['heading'] ),
			'text'        => wp_kses_post( $slide['text'] ),
			'btn_text'    => sanitize_text_field( $slide['btn_text'] ),
			'btn_url'     => esc_url_raw( $slide['btn_url'] ),
			'link'        => esc_url_raw( $slide['link'] ),
			'new_tab'     => ! empty( $slide['new_tab'] ),
		);
	}

	/**
	 * Get a list of published sliders for select controls.
	 *
	 * @return array<int,array{id:int,title:string}>
	 */
	public static function get_slider_choices() {
		$posts = get_posts(
			array(
				'post_type'      => Post_Type::SLUG,
				'post_status'    => 'publish',
				'numberposts'    => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'suppress_filters' => false,
			)
		);

		$choices = array();
		foreach ( $posts as $id ) {
			$choices[] = array(
				'id'    => (int) $id,
				'title' => get_the_title( $id ),
			);
		}
		return $choices;
	}
}
