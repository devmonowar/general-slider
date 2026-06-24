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
			'autoplay'   => false,
			'speed'      => 5000,
			'loop'       => true,
			'arrows'     => true,
			'dots'       => true,
			'transition' => 'slide',
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
			'thumbnails' => false,
		);
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
		return array(
			'preset'     => array_key_exists( ( $input['preset'] ?? '' ), self::presets() ) ? $input['preset'] : 'hero',
			'transition' => array_key_exists( ( $input['transition'] ?? '' ), self::transitions() ) ? $input['transition'] : 'slide',
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
			'animate'    => ! empty( $input['animate'] ),
			'thumbnails' => ! empty( $input['thumbnails'] ),
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
		$slides = get_post_meta( $post_id, self::META_SLIDES, true );
		$slides = ( empty( $slides ) || ! is_array( $slides ) )
			? array()
			: array_values( array_filter( array_map( array( __CLASS__, 'normalise_slide' ), $slides ) ) );

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
