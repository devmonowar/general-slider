<?php
/**
 * The [general_slider] shortcode.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the shortcode.
 */
class Shortcode {

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_shortcode( 'general_slider', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'general_slider' );
		return Renderer::render( absint( $atts['id'] ) );
	}
}
