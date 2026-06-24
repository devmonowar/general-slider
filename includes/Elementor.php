<?php
/**
 * Elementor integration — registers the General Slider widget when Elementor is active.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the widget into Elementor. The callback only runs when Elementor loads,
 * so the widget class (which extends an Elementor base class) is never touched otherwise.
 */
class Elementor {

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'elementor/widgets/register', array( $this, 'register' ) );
	}

	/**
	 * Register the widget with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register( $widgets_manager ) {
		require_once GENERAL_SLIDER_DIR . 'includes/Elementor_Widget.php';
		$widgets_manager->register( new Elementor_Widget() );
	}
}
