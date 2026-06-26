<?php
/**
 * Import / export sliders as JSON.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Handles exporting all sliders to a JSON file and importing them back.
 */
class Tools {

	const EXPORT = 'gs_export';
	const IMPORT = 'gs_import';

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'admin_post_' . self::EXPORT, array( $this, 'export' ) );
		add_action( 'admin_post_' . self::IMPORT, array( $this, 'import' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * Render the import/export UI (used on the settings page).
	 */
	public static function ui() {
		?>
		<p>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::EXPORT ), self::EXPORT ) ); ?>"><?php esc_html_e( 'Export all sliders', 'general-slider' ); ?></a>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="gs-file-form">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::IMPORT ); ?>" />
			<?php wp_nonce_field( self::IMPORT ); ?>
			<input type="file" name="gs_file" accept="application/json,.json" required />
			<?php submit_button( __( 'Import sliders', 'general-slider' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Disable a file form's submit button until a file is chosen.
	 * Applies to any form with the `gs-file-form` class on the current page.
	 */
	public static function file_required_script() {
		wp_print_inline_script_tag( 'document.querySelectorAll(".gs-file-form").forEach(function(f){var i=f.querySelector("input[type=file]"),b=f.querySelector("[type=submit]");if(i&&b){b.disabled=!i.value;i.addEventListener("change",function(){b.disabled=!i.value;});}});' );
	}

	/**
	 * Export all sliders as a downloadable JSON file.
	 */
	public function export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'general-slider' ) );
		}
		check_admin_referer( self::EXPORT );

		$ids  = get_posts(
			array(
				'post_type'        => Post_Type::SLUG,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);
		$data = array(
			'plugin'  => 'general-slider',
			'version' => GENERAL_SLIDER_VERSION,
			'sliders' => array(),
		);
		foreach ( $ids as $id ) {
			$data['sliders'][] = array(
				'title'      => get_the_title( $id ),
				'slides'     => Data::get_slides( $id ),
				'settings'   => Data::get_settings( $id ),
				'custom_css' => (string) get_post_meta( $id, Data::META_CSS, true ),
			);
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=general-slider-export.json' );
		echo wp_json_encode( $data );
		exit;
	}

	/**
	 * Import sliders from an uploaded JSON file.
	 */
	public function import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'general-slider' ) );
		}
		check_admin_referer( self::IMPORT );

		$count = 0;
		if ( isset( $_FILES['gs_file']['tmp_name'] ) && is_uploaded_file( $_FILES['gs_file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$raw  = file_get_contents( $_FILES['gs_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$json = json_decode( $raw, true );

			if ( is_array( $json ) && ! empty( $json['sliders'] ) && is_array( $json['sliders'] ) ) {
				foreach ( $json['sliders'] as $slider ) {
					if ( ! is_array( $slider ) ) {
						continue;
					}
					$new_id = wp_insert_post(
						array(
							'post_type'   => Post_Type::SLUG,
							'post_status' => 'draft',
							'post_title'  => isset( $slider['title'] ) ? sanitize_text_field( $slider['title'] ) : __( 'Imported slider', 'general-slider' ),
						)
					);
					if ( ! $new_id || is_wp_error( $new_id ) ) {
						continue;
					}

					$slides = array();
					if ( ! empty( $slider['slides'] ) && is_array( $slider['slides'] ) ) {
						foreach ( $slider['slides'] as $slide ) {
							$normalised = Data::normalise_slide( $slide );
							if ( $normalised ) {
								$slides[] = $normalised;
							}
						}
					}
					update_post_meta( $new_id, Data::META_SLIDES, $slides );
					update_post_meta( $new_id, Data::META_SETTINGS, Data::sanitize_settings( isset( $slider['settings'] ) ? $slider['settings'] : array() ) );

					if ( ! empty( $slider['custom_css'] ) ) {
						update_post_meta( $new_id, Data::META_CSS, self::clean_css( $slider['custom_css'] ) );
					}
					++$count;
				}
			}
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . Post_Type::SLUG . '&page=' . Settings::PAGE . '&gs_import=' . $count ) );
		exit;
	}

	/**
	 * Strip anything that could break out of a <style> tag.
	 *
	 * @param string $css Raw CSS.
	 * @return string
	 */
	public static function clean_css( $css ) {
		// Removing "<" is enough to stop a "</style>" break-out while keeping
		// CSS child combinators (">") intact.
		return trim( str_replace( '<', '', (string) $css ) );
	}

	/**
	 * Show an import result notice.
	 */
	public function notice() {
		if ( ! isset( $_GET['gs_import'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$count = absint( $_GET['gs_import'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of sliders imported. */
					_n( 'Imported %d slider.', 'Imported %d sliders.', $count, 'general-slider' ),
					$count
				)
			)
		);
	}
}
