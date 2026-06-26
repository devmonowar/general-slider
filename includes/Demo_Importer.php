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

		// Create the same bundled starter demo offered by the Demo Library.
		$post_id = Demo_Library::create_bundled_slider( 'publish' );
		if ( $post_id ) {
			wp_safe_redirect( add_query_arg( 'gs_demo', 'ok', get_edit_post_link( $post_id, 'redirect' ) ) );
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => Settings::PAGE,
					'gs_demo' => 'fail',
				),
				admin_url( 'edit.php?post_type=' . Post_Type::SLUG )
			)
		);
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
