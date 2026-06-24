<?php
/**
 * Adds a "Duplicate" row action to the sliders list.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Duplicates a slider (post, slides, settings, custom CSS and categories).
 */
class Duplicator {

	const ACTION = 'gs_duplicate';

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_filter( 'post_row_actions', array( $this, 'row_action' ), 10, 2 );
		add_action( 'admin_action_' . self::ACTION, array( $this, 'duplicate' ) );
	}

	/**
	 * Add the "Duplicate" link to the row actions.
	 *
	 * @param array    $actions Row actions.
	 * @param \WP_Post $post    Post object.
	 * @return array
	 */
	public function row_action( $actions, $post ) {
		if ( Post_Type::SLUG === $post->post_type && current_user_can( 'edit_posts' ) ) {
			$url                     = wp_nonce_url(
				admin_url( 'admin.php?action=' . self::ACTION . '&post=' . $post->ID ),
				self::ACTION . '_' . $post->ID
			);
			$actions['gs_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'general-slider' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Handle the duplicate request.
	 */
	public function duplicate() {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( self::ACTION . '_' . $id );

		$post = $id ? get_post( $id ) : null;
		if ( ! $post || Post_Type::SLUG !== $post->post_type || ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'general-slider' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => Post_Type::SLUG,
				'post_status' => 'draft',
				/* translators: %s: original slider title. */
				'post_title'  => sprintf( __( '%s (copy)', 'general-slider' ), $post->post_title ),
			)
		);

		if ( $new_id && ! is_wp_error( $new_id ) ) {
			update_post_meta( $new_id, Data::META_SLIDES, Data::get_slides( $id ) );
			update_post_meta( $new_id, Data::META_SETTINGS, get_post_meta( $id, Data::META_SETTINGS, true ) );

			$css = get_post_meta( $id, Data::META_CSS, true );
			if ( $css ) {
				update_post_meta( $new_id, Data::META_CSS, $css );
			}

			$terms = wp_get_object_terms( $id, Taxonomy::SLUG, array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) && $terms ) {
				wp_set_object_terms( $new_id, $terms, Taxonomy::SLUG );
			}
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . Post_Type::SLUG ) );
		exit;
	}
}
