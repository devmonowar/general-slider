<?php
/**
 * Registers the slider category taxonomy.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * "gs_slider_cat" taxonomy for organising sliders.
 */
class Taxonomy {

	const SLUG = 'gs_slider_cat';

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the taxonomy.
	 */
	public static function register() {
		register_taxonomy(
			self::SLUG,
			Post_Type::SLUG,
			array(
				'labels'            => array(
					'name'          => __( 'Categories', 'general-slider' ),
					'singular_name' => __( 'Category', 'general-slider' ),
					'menu_name'     => __( 'Categories', 'general-slider' ),
					'all_items'     => __( 'All Categories', 'general-slider' ),
					'edit_item'     => __( 'Edit Category', 'general-slider' ),
					'add_new_item'  => __( 'Add New Category', 'general-slider' ),
					'search_items'  => __( 'Search Categories', 'general-slider' ),
				),
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => false,
				'query_var'         => false,
			)
		);
	}
}
