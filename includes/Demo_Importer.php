<?php
/**
 * One-click demo slider importer.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a ready-made demo slider so new users see a working example.
 */
class Demo_Importer {

	const ACTION = 'gs_import_demo';

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'import' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * Render the import button (used on the settings page).
	 */
	public static function button() {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
			<?php wp_nonce_field( self::ACTION ); ?>
			<?php submit_button( __( 'Create demo slider', 'general-slider' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Handle the import request.
	 */
	public function import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'general-slider' ) );
		}
		check_admin_referer( self::ACTION );

		$post_id = wp_insert_post(
			array(
				'post_type'   => Post_Type::SLUG,
				'post_status' => 'publish',
				'post_title'  => __( 'Demo Slider', 'general-slider' ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=' . Post_Type::SLUG . '&page=' . Settings::PAGE . '&gs_demo=fail' ) );
			exit;
		}

		$slides = array(
			array(
				'sub_heading' => __( 'Welcome to', 'general-slider' ),
				'heading'     => __( 'Build sliders in minutes', 'general-slider' ),
				'text'        => __( 'A lightweight, no-code carousel for any WordPress page.', 'general-slider' ),
				'btn_text'    => __( 'Get started', 'general-slider' ),
				'btn_url'     => '#',
			),
			array(
				'sub_heading' => __( 'Easy & fast', 'general-slider' ),
				'heading'     => __( 'No coding required', 'general-slider' ),
				'text'        => __( 'Just add slides and drop the block on any page.', 'general-slider' ),
				'btn_text'    => __( 'Learn more', 'general-slider' ),
				'btn_url'     => '#',
			),
			array(
				'sub_heading' => __( 'Simple. Fast. Yours.', 'general-slider' ),
				'heading'     => __( 'Make it your own', 'general-slider' ),
				'text'        => __( 'Pick a design preset and match your theme.', 'general-slider' ),
				'btn_text'    => '',
				'btn_url'     => '',
			),
		);

		$slides = array_map( array( Data::class, 'normalise_slide' ), $slides );
		update_post_meta( $post_id, Data::META_SLIDES, $slides );
		update_post_meta( $post_id, Data::META_SETTINGS, Data::default_settings() );

		wp_safe_redirect( get_edit_post_link( $post_id, 'redirect' ) . '&gs_demo=ok' );
		exit;
	}

	/**
	 * Show a success notice after import.
	 */
	public function notice() {
		if ( ! isset( $_GET['gs_demo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$status = sanitize_key( wp_unslash( $_GET['gs_demo'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ok' === $status ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Demo slider created. Edit it below, then embed it with the block or shortcode.', 'general-slider' ) . '</p></div>';
		} elseif ( 'fail' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Could not create the demo slider.', 'general-slider' ) . '</p></div>';
		}
	}
}
