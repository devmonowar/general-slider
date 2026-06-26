<?php
/**
 * Remote Demo Library: fetches a manifest of ready-made sliders from a
 * GitHub Pages endpoint and imports them (with their images) on one click.
 *
 * Nothing here is hard-coded: the demo list, metadata, preview images and
 * slider data all come from the manifest at GS_DEMO_LIBRARY_URL.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Admin screen + import handler for the remote demo library.
 */
class Demo_Library {

	const PAGE      = 'general-slider-demos';
	const ACTION    = 'gs_demo_import';
	const TRANSIENT = 'gs_demo_manifest';
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'import' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * Add the "Demo Library" submenu under the Sliders menu.
	 */
	public function menu() {
		$hook = add_submenu_page(
			'edit.php?post_type=' . Post_Type::SLUG,
			__( 'Demo Library', 'general-slider' ),
			__( 'Demo Library', 'general-slider' ),
			'manage_options',
			self::PAGE,
			array( $this, 'page' )
		);
		if ( $hook ) {
			add_action( 'admin_print_styles-' . $hook, array( $this, 'styles' ) );
		}
	}

	/**
	 * Load the admin stylesheet on the Demo Library screen.
	 */
	public function styles() {
		wp_enqueue_style( 'general-slider-admin', GENERAL_SLIDER_URL . 'assets/css/admin.css', array(), GENERAL_SLIDER_VERSION );
	}

	/**
	 * The resolved manifest URL (filterable for dev / staging).
	 *
	 * @return string
	 */
	public static function manifest_url() {
		return (string) apply_filters( 'general_slider_demo_library_url', GS_DEMO_LIBRARY_URL );
	}

	/**
	 * Fetch and validate the manifest, cached in a transient.
	 *
	 * @param bool $force Bypass the cache.
	 * @return array|\WP_Error Validated manifest array, or WP_Error on failure.
	 */
	public static function get_manifest( $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( self::TRANSIENT );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = wp_remote_get(
			self::manifest_url(),
			array(
				'timeout' => 10,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( 'gs_http', __( 'The demo library could not be reached.', 'general-slider' ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$data = self::validate_manifest( $data );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		set_transient( self::TRANSIENT, $data, self::CACHE_TTL );
		return $data;
	}

	/**
	 * Validate the raw manifest structure and normalise its demos.
	 *
	 * @param mixed $data Decoded JSON.
	 * @return array|\WP_Error
	 */
	private static function validate_manifest( $data ) {
		if ( ! is_array( $data ) || empty( $data['demos'] ) || ! is_array( $data['demos'] ) ) {
			return new \WP_Error( 'gs_manifest', __( 'The demo library response was not valid.', 'general-slider' ) );
		}
		if ( isset( $data['plugin'] ) && 'general-slider' !== $data['plugin'] ) {
			return new \WP_Error( 'gs_manifest', __( 'The demo library is for a different plugin.', 'general-slider' ) );
		}

		$demos = array();
		foreach ( $data['demos'] as $demo ) {
			if ( ! is_array( $demo ) || empty( $demo['id'] ) || empty( $demo['file'] ) ) {
				continue;
			}
			$file = esc_url_raw( $demo['file'] );
			if ( ! $file || ! in_array( wp_parse_url( $file, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) {
				continue;
			}
			$demos[] = array(
				'id'          => sanitize_key( $demo['id'] ),
				'name'        => isset( $demo['name'] ) ? sanitize_text_field( $demo['name'] ) : $demo['id'],
				'description' => isset( $demo['description'] ) ? sanitize_text_field( $demo['description'] ) : '',
				'version'     => isset( $demo['version'] ) ? sanitize_text_field( $demo['version'] ) : '',
				'requires'    => isset( $demo['requires'] ) ? sanitize_text_field( $demo['requires'] ) : '',
				'category'    => isset( $demo['category'] ) ? sanitize_text_field( $demo['category'] ) : '',
				'tags'        => isset( $demo['tags'] ) && is_array( $demo['tags'] ) ? array_map( 'sanitize_text_field', $demo['tags'] ) : array(),
				'featured'    => ! empty( $demo['featured'] ),
				'is_new'      => ! empty( $demo['new'] ),
				'preview'     => isset( $demo['preview'] ) ? esc_url_raw( $demo['preview'] ) : '',
				'file'        => $file,
			);
		}

		if ( ! $demos ) {
			return new \WP_Error( 'gs_manifest', __( 'The demo library is empty right now.', 'general-slider' ) );
		}

		return array(
			'schema_version' => isset( $data['schema_version'] ) ? absint( $data['schema_version'] ) : 1,
			'demos'          => $demos,
		);
	}

	/**
	 * Render the Demo Library page.
	 */
	public function page() {
		$manifest = self::get_manifest();
		?>
		<div class="wrap gs-demo-library">
			<h1><?php esc_html_e( 'Demo Library', 'general-slider' ); ?></h1>
			<p><?php esc_html_e( 'Import a ready-made slider with one click. Images are downloaded into your Media Library automatically.', 'general-slider' ); ?></p>

			<?php if ( is_wp_error( $manifest ) ) : ?>
				<div class="notice notice-warning">
					<p><strong><?php esc_html_e( 'Unable to load the online demo library.', 'general-slider' ); ?></strong> <?php echo esc_html( $manifest->get_error_message() ); ?></p>
				</div>
				<h2><?php esc_html_e( 'Basic demo', 'general-slider' ); ?></h2>
				<p><?php esc_html_e( 'You can still create a simple starter slider offline:', 'general-slider' ); ?></p>
				<?php Demo_Importer::button(); ?>
			<?php else : ?>
				<div class="gs-demo-grid">
					<?php
					foreach ( $manifest['demos'] as $demo ) {
						$this->card( $demo );
					}
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a single demo card.
	 *
	 * @param array $demo Normalised demo entry.
	 */
	private function card( $demo ) {
		$can_import = '' === $demo['requires'] || version_compare( GENERAL_SLIDER_VERSION, $demo['requires'], '>=' );
		?>
		<div class="gs-demo-card">
			<div class="gs-demo-card__preview">
				<?php if ( $demo['preview'] ) : ?>
					<img src="<?php echo esc_url( $demo['preview'] ); ?>" alt="<?php echo esc_attr( $demo['name'] ); ?>" loading="lazy" />
				<?php endif; ?>
				<?php if ( $demo['featured'] ) : ?>
					<span class="gs-demo-badge gs-demo-badge--featured"><?php esc_html_e( 'Featured', 'general-slider' ); ?></span>
				<?php elseif ( $demo['is_new'] ) : ?>
					<span class="gs-demo-badge gs-demo-badge--new"><?php esc_html_e( 'New', 'general-slider' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="gs-demo-card__body">
				<h3 class="gs-demo-card__title"><?php echo esc_html( $demo['name'] ); ?></h3>
				<?php if ( $demo['description'] ) : ?>
					<p class="gs-demo-card__desc"><?php echo esc_html( $demo['description'] ); ?></p>
				<?php endif; ?>
				<?php if ( $demo['category'] ) : ?>
					<p class="gs-demo-card__cat"><?php echo esc_html( $demo['category'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="gs-demo-card__actions">
				<?php if ( $can_import ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
						<input type="hidden" name="demo_id" value="<?php echo esc_attr( $demo['id'] ); ?>" />
						<?php wp_nonce_field( self::ACTION . '_' . $demo['id'] ); ?>
						<?php submit_button( __( 'Import Demo', 'general-slider' ), 'primary', 'submit', false ); ?>
					</form>
				<?php else : ?>
					<button type="button" class="button" disabled><?php esc_html_e( 'Import Demo', 'general-slider' ); ?></button>
					<p class="gs-demo-card__requires">
						<?php
						/* translators: %s: required plugin version. */
						printf( esc_html__( 'Requires General Slider %s+', 'general-slider' ), esc_html( $demo['requires'] ) );
						?>
						<br /><strong><?php esc_html_e( 'Update Needed', 'general-slider' ); ?></strong>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle a one-click import request.
	 */
	public function import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'general-slider' ) );
		}

		$demo_id = isset( $_POST['demo_id'] ) ? sanitize_key( wp_unslash( $_POST['demo_id'] ) ) : '';
		check_admin_referer( self::ACTION . '_' . $demo_id );

		$result = $demo_id ? $this->import_demo( $demo_id ) : 0;
		if ( $result ) {
			wp_safe_redirect( add_query_arg( 'gs_demo_lib', 'ok', get_edit_post_link( $result, 'redirect' ) ) );
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => self::PAGE,
					'gs_demo_lib' => 'fail',
				),
				admin_url( 'edit.php?post_type=' . Post_Type::SLUG )
			)
		);
		exit;
	}

	/**
	 * Look up a demo in the manifest, fetch its file and create the slider.
	 *
	 * @param string $demo_id Demo id.
	 * @return int New slider ID, or 0 on failure.
	 */
	private function import_demo( $demo_id ) {
		$manifest = self::get_manifest();
		if ( is_wp_error( $manifest ) ) {
			return 0;
		}

		// Resolve the demo entry from the manifest (never trust a posted URL).
		$entry = null;
		foreach ( $manifest['demos'] as $demo ) {
			if ( $demo['id'] === $demo_id ) {
				$entry = $demo;
				break;
			}
		}
		if ( ! $entry ) {
			return 0;
		}

		// Re-check the version requirement server-side.
		if ( '' !== $entry['requires'] && ! version_compare( GENERAL_SLIDER_VERSION, $entry['requires'], '>=' ) ) {
			return 0;
		}

		$response = wp_remote_get( $entry['file'], array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return 0;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['slides'] ) || ! is_array( $data['slides'] ) ) {
			return 0;
		}

		$title   = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : $entry['name'];
		$post_id = wp_insert_post(
			array(
				'post_type'   => Post_Type::SLUG,
				'post_status' => 'draft',
				'post_title'  => $title,
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		// Build slides, sideloading each image into the Media Library.
		$slides = array();
		foreach ( $data['slides'] as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			if ( ! empty( $raw['image_url'] ) ) {
				$raw['image_id'] = self::sideload_image( $raw['image_url'], $post_id );
			}
			unset( $raw['image_url'] );
			$slide = Data::normalise_slide( $raw );
			if ( $slide ) {
				$slides[] = $slide;
			}
		}

		update_post_meta( $post_id, Data::META_SLIDES, $slides );
		update_post_meta( $post_id, Data::META_SETTINGS, Data::sanitize_settings( isset( $data['settings'] ) ? $data['settings'] : array() ) );
		if ( ! empty( $data['custom_css'] ) ) {
			update_post_meta( $post_id, Data::META_CSS, Tools::clean_css( $data['custom_css'] ) );
		}

		// Provenance: which demo this slider came from (foundation for future updates).
		update_post_meta( $post_id, '_gs_demo_id', $demo_id );
		update_post_meta( $post_id, '_gs_demo_source', esc_url_raw( $entry['file'] ) );

		return (int) $post_id;
	}

	/**
	 * Download a remote image into the Media Library.
	 *
	 * @param string $url     Image URL (must be https).
	 * @param int    $post_id Slider to attach it to.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private static function sideload_image( $url, $post_id ) {
		$url = esc_url_raw( $url );
		if ( ! $url || ! in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url, 15 );
		if ( is_wp_error( $tmp ) ) {
			return 0;
		}

		$file = array(
			'name'     => basename( wp_parse_url( $url, PHP_URL_PATH ) ),
			'tmp_name' => $tmp,
		);

		// media_handle_sideload validates the file type itself; non-images are rejected.
		$id = media_handle_sideload( $file, $post_id );
		if ( is_wp_error( $id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return 0;
		}

		return (int) $id;
	}

	/**
	 * Show the import result notice.
	 */
	public function notice() {
		if ( ! isset( $_GET['gs_demo_lib'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$status = sanitize_key( wp_unslash( $_GET['gs_demo_lib'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ok' === $status ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Demo imported. Review it below, then publish and embed it with the block or shortcode.', 'general-slider' ) . '</p></div>';
		} elseif ( 'fail' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Sorry, that demo could not be imported. Please try again.', 'general-slider' ) . '</p></div>';
		}
	}
}
