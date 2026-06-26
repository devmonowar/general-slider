<?php
/**
 * Removes plugin data on uninstall: slider posts, their meta and the settings option.
 *
 * @package General_Slider
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete all data created by the plugin.
 */
function general_slider_uninstall() {
	$ids = get_posts(
		array(
			'post_type'        => 'gs_slider',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}

	delete_option( 'general_slider_settings' );
	delete_option( 'general_slider_demo_installed' );
}

general_slider_uninstall();
