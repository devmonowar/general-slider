<?php
/**
 * Removes plugin data on uninstall: slider posts, their categories, meta and options.
 *
 * @package General_Slider
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete every piece of plugin data on the current site.
 */
function general_slider_uninstall_site() {
	// The plugin itself is not loaded during uninstall, so make the post type
	// and the taxonomy known before asking WordPress to delete their content.
	register_post_type( 'gs_slider', array( 'public' => false ) );
	register_taxonomy( 'gs_slider_cat', 'gs_slider', array( 'public' => false ) );

	// 'any' silently skips trashed and auto-draft sliders, so list the statuses.
	$ids = get_posts(
		array(
			'post_type'        => 'gs_slider',
			'post_status'      => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft' ),
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}

	// Slider categories, together with their term meta.
	$terms = get_terms(
		array(
			'taxonomy'   => 'gs_slider_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, 'gs_slider_cat' );
		}
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

if ( is_multisite() ) {
	// Uninstall runs once for the whole network, so clean every site in it.
	$general_slider_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $general_slider_sites as $general_slider_site ) {
		switch_to_blog( $general_slider_site );
		general_slider_uninstall_site();
		restore_current_blog();
	}
} else {
	general_slider_uninstall_site();
}
