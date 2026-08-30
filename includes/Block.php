<?php
/**
 * Registers the General Slider block (server-rendered, no build step).
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Block registration and server-side render.
 */
class Block {

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_data' ) );
	}

	/**
	 * Register the editor assets and the block type.
	 */
	public function register() {
		wp_register_script(
			'general-slider-block-editor',
			GENERAL_SLIDER_URL . 'block/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			GENERAL_SLIDER_VERSION,
			true
		);
		wp_register_style( 'general-slider-block-editor', GENERAL_SLIDER_URL . 'block/editor.css', array(), GENERAL_SLIDER_VERSION );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'general-slider-block-editor', 'general-slider', GENERAL_SLIDER_DIR . 'languages' );
		}

		register_block_type(
			GENERAL_SLIDER_DIR . 'block',
			array( 'render_callback' => array( $this, 'render' ) )
		);
	}

	/**
	 * Hand the list of sliders to the block editor.
	 *
	 * Kept off `init` so the query never runs on a front-end request.
	 */
	public function editor_data() {
		wp_localize_script(
			'general-slider-block-editor',
			'GeneralSliderBlock',
			array( 'sliders' => Data::get_slider_choices() )
		);
	}

	/**
	 * Server render callback.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		$id = isset( $attributes['sliderId'] ) ? absint( $attributes['sliderId'] ) : 0;
		if ( ! $id ) {
			$inner = current_user_can( 'edit_posts' )
				? '<p class="gs-slider-notice">' . esc_html__( 'General Slider: choose a slider in the block settings.', 'general-slider' ) . '</p>'
				: '';
		} else {
			$inner = Renderer::render( $id );
		}

		if ( '' === $inner ) {
			return '';
		}

		// Wrap so block alignment (wide/full) and other supports are applied.
		$wrapper = function_exists( 'get_block_wrapper_attributes' ) ? get_block_wrapper_attributes() : '';
		return sprintf( '<div %s>%s</div>', $wrapper, $inner );
	}
}
