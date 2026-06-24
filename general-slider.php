<?php
/**
 * Plugin Name:       General Slider
 * Plugin URI:        https://wordpress.org/plugins/general-slider/
 * Description:        A lightweight, easy-to-use carousel slider. Build reusable sliders and drop them anywhere with a block.
 * Version:           2.3.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Monowar
 * Author URI:        https://wordpress.org/plugins/general-slider/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       general-slider
 * Domain Path:       /languages
 *
 * @package General_Slider
 */

defined( 'ABSPATH' ) || exit;

define( 'GENERAL_SLIDER_VERSION', '2.3.0' );
define( 'GENERAL_SLIDER_FILE', __FILE__ );
define( 'GENERAL_SLIDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'GENERAL_SLIDER_URL', plugin_dir_url( __FILE__ ) );
define( 'GENERAL_SLIDER_BASENAME', plugin_basename( __FILE__ ) );

/**
 * PSR-4-style autoloader for the GeneralSlider namespace.
 *
 * GeneralSlider\Post_Type  ->  includes/Post_Type.php
 */
spl_autoload_register(
	function ( $class_name ) {
		$prefix = 'GeneralSlider\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = GENERAL_SLIDER_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

/**
 * Boot the plugin once all plugins are loaded.
 */
function general_slider() {
	return \GeneralSlider\Plugin::instance();
}
add_action( 'plugins_loaded', 'general_slider' );

// Activation: register the post type then flush rewrite rules.
register_activation_hook(
	__FILE__,
	function () {
		\GeneralSlider\Post_Type::register();
		flush_rewrite_rules();
	}
);

// Deactivation: clean up rewrite rules.
register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);
