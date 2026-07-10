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
	delete_option( 'general_slider_review' );
	delete_option( 'general_slider_dynamic_cache_v' );

	// Remove any leftover dynamic-slider slide caches (transients).
	global $wpdb;
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_gs\_dyn\_%' OR option_name LIKE '\_transient\_timeout\_gs\_dyn\_%'"
	);
}

general_slider_uninstall();
