<?php
/**
 * Registers and conditionally loads front-end and admin assets.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Asset loader. Front-end assets only load when a slider is actually rendered.
 */
class Assets {

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin' ) );
	}

	/**
	 * Register (but do not enqueue) the front-end assets.
	 */
	public static function register_frontend() {
		$ver = GENERAL_SLIDER_VERSION;

		wp_register_style( 'splide', GENERAL_SLIDER_URL . 'assets/vendor/splide/splide.min.css', array(), '4.1.4' );
		wp_register_style( 'general-slider', GENERAL_SLIDER_URL . 'assets/css/frontend.css', array( 'splide' ), $ver );

		wp_register_script( 'splide', GENERAL_SLIDER_URL . 'assets/vendor/splide/splide.min.js', array(), '4.1.4', true );
		wp_register_script( 'general-slider', GENERAL_SLIDER_URL . 'assets/js/frontend.js', array( 'splide' ), $ver, true );
	}

	/**
	 * Enqueue the front-end assets. Called by the renderer only when needed.
	 */
	public static function mark_needed() {
		// In case the renderer runs before wp_enqueue_scripts (rare), make sure handles exist.
		if ( ! wp_style_is( 'general-slider', 'registered' ) ) {
			self::register_frontend();
		}
		wp_enqueue_style( 'splide' );
		wp_enqueue_style( 'general-slider' );
		wp_enqueue_script( 'splide' );
		wp_enqueue_script( 'general-slider' );
	}

	/**
	 * Admin assets, only on the slider edit screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function admin( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || Post_Type::SLUG !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'general-slider-admin', GENERAL_SLIDER_URL . 'assets/css/admin.css', array(), GENERAL_SLIDER_VERSION );
		wp_enqueue_script( 'general-slider-admin', GENERAL_SLIDER_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), GENERAL_SLIDER_VERSION, true );
		wp_localize_script(
			'general-slider-admin',
			'GeneralSliderAdmin',
			array(
				'chooseImage' => __( 'Choose image', 'general-slider' ),
				'useImage'    => __( 'Use this image', 'general-slider' ),
				'chooseVideo' => __( 'Choose video', 'general-slider' ),
				'useVideo'    => __( 'Use this video', 'general-slider' ),
				'removeText'  => __( 'Remove slide', 'general-slider' ),
				'confirm'     => __( 'Remove this slide?', 'general-slider' ),
			)
		);
	}
}
