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
		$css = self::asset( 'assets/css/frontend.css' );
		$js  = self::asset( 'assets/js/frontend.js' );

		wp_register_style( 'splide', GENERAL_SLIDER_URL . 'assets/vendor/splide/splide.min.css', array(), '4.1.4' );
		wp_register_style( 'general-slider', GENERAL_SLIDER_URL . $css, array( 'splide' ), self::version( $css ) );

		wp_register_script( 'splide', GENERAL_SLIDER_URL . 'assets/vendor/splide/splide.min.js', array(), '4.1.4', true );
		wp_register_script( 'general-slider', GENERAL_SLIDER_URL . $js, array( 'splide' ), self::version( $js ), true );
	}

	/**
	 * Resolve an asset to its minified sibling when available and not debugging.
	 *
	 * `assets/css/frontend.css` becomes `assets/css/frontend.min.css` on normal
	 * loads, and stays unminified when SCRIPT_DEBUG is on (or no .min exists).
	 *
	 * @param string $relative Path to the source asset, relative to the plugin root.
	 * @return string
	 */
	private static function asset( $relative ) {
		if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			$min = preg_replace( '/\.(css|js)$/', '.min.$1', $relative );
			if ( is_string( $min ) && $min !== $relative && is_readable( GENERAL_SLIDER_DIR . $min ) ) {
				return $min;
			}
		}
		return $relative;
	}

	/**
	 * Cache-busting version for a plugin asset: the file's modification time,
	 * so any CSS/JS change busts caches even within the same plugin version.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string
	 */
	private static function version( $relative ) {
		$path = GENERAL_SLIDER_DIR . $relative;
		if ( is_readable( $path ) ) {
			$mtime = filemtime( $path );
			if ( $mtime ) {
				return (string) $mtime;
			}
		}
		return GENERAL_SLIDER_VERSION;
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
		$screen = get_current_screen();
		if ( ! $screen || Post_Type::SLUG !== $screen->post_type ) {
			return;
		}

		// Shortcode click-to-copy + admin styles: on the sliders list and the editor.
		if ( in_array( $hook, array( 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
			$admin_css = self::asset( 'assets/css/admin.css' );
			$copy_js   = self::asset( 'assets/js/admin-copy.js' );
			wp_enqueue_style( 'general-slider-admin', GENERAL_SLIDER_URL . $admin_css, array(), self::version( $admin_css ) );
			wp_enqueue_script( 'general-slider-admin-copy', GENERAL_SLIDER_URL . $copy_js, array(), self::version( $copy_js ), true );
		}

		// The slide editor (repeater + media picker) only.
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$admin_js = self::asset( 'assets/js/admin.js' );
		wp_enqueue_media();
		wp_enqueue_script( 'general-slider-admin', GENERAL_SLIDER_URL . $admin_js, array( 'jquery', 'jquery-ui-sortable' ), self::version( $admin_js ), true );
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
				'sourceData'  => Dynamic_Slides::ui_data(),
				'anyTerm'     => __( 'All', 'general-slider' ),
			)
		);
	}
}
