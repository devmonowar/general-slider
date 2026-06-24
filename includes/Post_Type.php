<?php
/**
 * Registers the slider custom post type.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * The "gs_slider" post type — one post is one reusable slider.
 */
class Post_Type {

	const SLUG = 'gs_slider';

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'manage_' . self::SLUG . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::SLUG . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
	}

	/**
	 * Add "Slides" and "Shortcode" columns to the sliders list table.
	 *
	 * @param array $cols Existing columns.
	 * @return array
	 */
	public function columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['gs_slides']    = __( 'Slides', 'general-slider' );
				$new['gs_shortcode'] = __( 'Shortcode', 'general-slider' );
			}
		}
		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Slider ID.
	 */
	public function column_content( $column, $post_id ) {
		if ( 'gs_slides' === $column ) {
			echo (int) count( Data::get_slides( $post_id ) );
		} elseif ( 'gs_shortcode' === $column ) {
			printf(
				'<input type="text" readonly onclick="this.select()" value="%s" style="width:200px;max-width:100%%" />',
				esc_attr( sprintf( '[general_slider id="%d"]', $post_id ) )
			);
		}
	}

	/**
	 * Register the post type. Static so it can run on activation too.
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'Sliders', 'general-slider' ),
			'singular_name'      => __( 'Slider', 'general-slider' ),
			'add_new'            => __( 'Add New', 'general-slider' ),
			'add_new_item'       => __( 'Add New Slider', 'general-slider' ),
			'edit_item'          => __( 'Edit Slider', 'general-slider' ),
			'new_item'           => __( 'New Slider', 'general-slider' ),
			'view_item'          => __( 'View Slider', 'general-slider' ),
			'search_items'       => __( 'Search Sliders', 'general-slider' ),
			'not_found'          => __( 'No sliders found', 'general-slider' ),
			'not_found_in_trash' => __( 'No sliders found in Trash', 'general-slider' ),
			'all_items'          => __( 'All Sliders', 'general-slider' ),
			'menu_name'          => __( 'General Slider', 'general-slider' ),
		);

		register_post_type(
			self::SLUG,
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-slides',
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'capability_type'     => 'post',
			)
		);
	}
}
