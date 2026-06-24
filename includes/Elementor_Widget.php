<?php
/**
 * Elementor widget for General Slider.
 *
 * This file is only loaded inside the Elementor widget-registration hook,
 * so \Elementor\Widget_Base is guaranteed to exist here.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * "General Slider" Elementor widget.
 */
class Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'general_slider';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'General Slider', 'general-slider' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'slider', 'carousel', 'slideshow' );
	}

	/**
	 * Register the widget controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'gs_section',
			array( 'label' => __( 'Slider', 'general-slider' ) )
		);

		$options = array( 0 => __( '— Select a slider —', 'general-slider' ) );
		foreach ( Data::get_slider_choices() as $choice ) {
			$options[ $choice['id'] ] = $choice['title'];
		}

		$this->add_control(
			'slider_id',
			array(
				'label'   => __( 'Choose slider', 'general-slider' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $options,
				'default' => 0,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$id       = isset( $settings['slider_id'] ) ? absint( $settings['slider_id'] ) : 0;

		if ( $id ) {
			echo Renderer::render( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="gs-slider-notice">' . esc_html__( 'General Slider: choose a slider in the widget settings.', 'general-slider' ) . '</p>';
		}
	}
}
