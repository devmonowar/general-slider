<?php
/**
 * Remote Demo Library: fetches a manifest of ready-made sliders from a
 * GitHub Pages endpoint and imports them (with their images) on one click.
 * Ships with one bundled starter demo used as the offline fallback.
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

	/** Reserved id for the demo bundled inside the plugin (offline fallback). */
	const BUNDLED_ID = 'nature-showcase';

	/** admin-post action for importing a demo package (.zip) exported with "Export Slider". */
	const ZIP_ACTION = 'gs_demo_import_zip';

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'import' ) );
		add_action( 'admin_post_' . self::ZIP_ACTION, array( $this, 'import_zip' ) );
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
		// "Refresh" clears the 6-hour manifest cache so newly published demos appear immediately.
		if ( isset( $_GET['gs_refresh'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'gs_demo_refresh' ) ) {
			delete_transient( self::TRANSIENT );
		}
		$manifest = self::get_manifest();
		?>
		<div class="wrap gs-demo-library">
			<h1>
				<?php esc_html_e( 'Demo Library', 'general-slider' ); ?>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'gs_refresh', '1', admin_url( 'edit.php?post_type=' . Post_Type::SLUG . '&page=' . self::PAGE ) ), 'gs_demo_refresh' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Refresh', 'general-slider' ); ?></a>
			</h1>
			<p><?php esc_html_e( 'Import a ready-made slider with one click. Images are downloaded into your Media Library automatically.', 'general-slider' ); ?></p>

			<?php if ( is_wp_error( $manifest ) ) : ?>
				<div class="notice notice-warning">
					<p><strong><?php esc_html_e( 'Unable to load the online demo library.', 'general-slider' ); ?></strong> <?php echo esc_html( $manifest->get_error_message() ); ?></p>
				</div>
				<p><?php esc_html_e( 'You can still import the bundled starter demo below.', 'general-slider' ); ?></p>
				<div class="gs-demo-grid">
					<?php $this->bundled_card(); ?>
				</div>
			<?php else : ?>
				<div class="gs-demo-grid">
					<?php
					foreach ( $manifest['demos'] as $demo ) {
						$this->card( $demo );
					}
					?>
				</div>
			<?php endif; ?>

			<hr />
			<h2><?php esc_html_e( 'Import a demo package', 'general-slider' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Have a .zip exported with "Export Slider"? Import it here — its images are included.', 'general-slider' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="gs-file-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ZIP_ACTION ); ?>" />
				<?php wp_nonce_field( self::ZIP_ACTION ); ?>
				<input type="file" name="gs_zip" accept=".zip,application/zip" required />
				<?php submit_button( __( 'Import package', 'general-slider' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php Tools::file_required_script(); ?>
		</div>
		<?php
	}

	/**
	 * Render a single (remote) demo card.
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
	 * Render the bundled starter demo as a card (offline fallback).
	 */
	private function bundled_card() {
		$demo = self::bundled_demo();
		if ( ! $demo ) {
			return;
		}
		$name        = isset( $demo['name'] ) ? $demo['name'] : __( 'Starter demo', 'general-slider' );
		$description = isset( $demo['description'] ) ? $demo['description'] : '';
		$preview     = GENERAL_SLIDER_URL . 'demos/images/nature-1.jpg';
		?>
		<div class="gs-demo-card">
			<div class="gs-demo-card__preview">
				<img src="<?php echo esc_url( $preview ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
				<span class="gs-demo-badge gs-demo-badge--featured"><?php esc_html_e( 'Starter', 'general-slider' ); ?></span>
			</div>
			<div class="gs-demo-card__body">
				<h3 class="gs-demo-card__title"><?php echo esc_html( $name ); ?></h3>
				<?php if ( $description ) : ?>
					<p class="gs-demo-card__desc"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</div>
			<div class="gs-demo-card__actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
					<input type="hidden" name="gs_bundled" value="1" />
					<?php wp_nonce_field( self::ACTION . '_bundled' ); ?>
					<?php submit_button( __( 'Import Demo', 'general-slider' ), 'primary', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Read the demo bundled inside the plugin (demos/{id}.json).
	 *
	 * @return array|null Demo data, or null if missing / invalid.
	 */
	private static function bundled_demo() {
		$file = GENERAL_SLIDER_DIR . 'demos/' . self::BUNDLED_ID . '.json';
		if ( ! is_readable( $file ) ) {
			return null;
		}
		$data = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled file.
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Handle a one-click import request (remote demo or bundled fallback).
	 */
	public function import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'general-slider' ) );
		}

		if ( isset( $_POST['gs_bundled'] ) ) {
			check_admin_referer( self::ACTION . '_bundled' );
			$result = self::create_bundled_slider( 'draft' );
		} else {
			$demo_id = isset( $_POST['demo_id'] ) ? sanitize_key( wp_unslash( $_POST['demo_id'] ) ) : '';
			check_admin_referer( self::ACTION . '_' . $demo_id );
			$result = $demo_id ? $this->import_demo( $demo_id ) : 0;
		}

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
	 * Create a slider from demo data, resolving each slide image via a callback.
	 *
	 * Shared by the remote import, the bundled demo and the .zip import — only the
	 * image source differs, supplied as $resolve_image.
	 *
	 * @param mixed    $demo          Demo data: 'title', 'slides', 'settings', 'custom_css'.
	 * @param string   $status        Post status ('draft' or 'publish').
	 * @param string   $source        Stored as _gs_demo_source.
	 * @param string   $demo_id       Stored as _gs_demo_id (skipped if empty).
	 * @param callable $resolve_image function( array $slide, int $post_id ): int — attachment id or 0.
	 * @return int New slider ID, or 0 on failure.
	 */
	private static function build_slider( $demo, $status, $source, $demo_id, $resolve_image ) {
		if ( ! is_array( $demo ) || empty( $demo['slides'] ) || ! is_array( $demo['slides'] ) ) {
			return 0;
		}

		$title   = ! empty( $demo['title'] ) ? sanitize_text_field( $demo['title'] ) : __( 'Imported demo', 'general-slider' );
		$post_id = wp_insert_post(
			array(
				'post_type'   => Post_Type::SLUG,
				'post_status' => ( 'publish' === $status ) ? 'publish' : 'draft',
				'post_title'  => $title,
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		$slides = array();
		foreach ( $demo['slides'] as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$image_id = (int) call_user_func( $resolve_image, $raw, $post_id );
			if ( $image_id ) {
				$raw['image_id'] = $image_id;
				// SEO: give the imported image descriptive alt text (demo-provided "alt", else the heading).
				$alt = '';
				if ( ! empty( $raw['alt'] ) ) {
					$alt = sanitize_text_field( $raw['alt'] );
				} elseif ( ! empty( $raw['heading'] ) ) {
					$alt = sanitize_text_field( wp_strip_all_tags( $raw['heading'] ) );
				}
				if ( '' !== $alt ) {
					update_post_meta( $image_id, '_wp_attachment_image_alt', $alt );
				}
			}
			unset( $raw['image_url'], $raw['image'], $raw['alt'] );
			$slide = Data::normalise_slide( $raw );
			if ( $slide ) {
				$slides[] = $slide;
			}
		}

		update_post_meta( $post_id, Data::META_SLIDES, $slides );
		update_post_meta( $post_id, Data::META_SETTINGS, Data::sanitize_settings( isset( $demo['settings'] ) ? $demo['settings'] : array() ) );
		if ( ! empty( $demo['custom_css'] ) ) {
			update_post_meta( $post_id, Data::META_CSS, Tools::clean_css( $demo['custom_css'] ) );
		}
		if ( '' !== (string) $demo_id ) {
			update_post_meta( $post_id, '_gs_demo_id', $demo_id );
		}
		update_post_meta( $post_id, '_gs_demo_source', $source );

		return (int) $post_id;
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

		if ( empty( $data['title'] ) ) {
			$data['title'] = $entry['name'];
		}

		return self::build_slider(
			$data,
			'draft',
			esc_url_raw( $entry['file'] ),
			$demo_id,
			function ( $raw, $pid ) {
				return ! empty( $raw['image_url'] ) ? self::sideload_image( $raw['image_url'], $pid ) : 0;
			}
		);
	}

	/**
	 * Seed the bundled starter demo on first activation (once, only on an empty site).
	 */
	public static function maybe_install_demo() {
		if ( get_option( 'general_slider_demo_installed' ) ) {
			return;
		}
		// Mark first so this never retries on later activations.
		update_option( 'general_slider_demo_installed', 1 );

		$existing = get_posts(
			array(
				'post_type'   => Post_Type::SLUG,
				'post_status' => 'any',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		if ( $existing ) {
			return;
		}

		self::create_bundled_slider( 'publish' );
	}

	/**
	 * Create a slider from the bundled demo, sideloading its images from the plugin.
	 *
	 * @param string $status Post status ('draft' or 'publish').
	 * @return int New slider ID, or 0 on failure.
	 */
	public static function create_bundled_slider( $status = 'draft' ) {
		$img_dir = GENERAL_SLIDER_DIR . 'demos/images/';
		return self::build_slider(
			self::bundled_demo(),
			$status,
			'bundled',
			self::BUNDLED_ID,
			function ( $raw, $pid ) use ( $img_dir ) {
				return ! empty( $raw['image'] ) ? self::sideload_local_image( $img_dir . basename( $raw['image'] ), $pid ) : 0;
			}
		);
	}

	/**
	 * Download a remote image into the Media Library.
	 *
	 * @param string $url     Image URL (http/https).
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
	 * Copy a bundled image into the Media Library.
	 *
	 * @param string $path    Absolute path to a bundled image.
	 * @param int    $post_id Slider to attach it to.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private static function sideload_local_image( $path, $post_id ) {
		if ( ! is_readable( $path ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Sideload moves the temp file, so copy the bundled asset to a temp path first.
		$tmp = wp_tempnam( basename( $path ) );
		if ( ! $tmp || ! copy( $path, $tmp ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- copying a bundled plugin asset to a temp file for sideload.
			if ( $tmp && file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return 0;
		}

		$file = array(
			'name'     => basename( $path ),
			'tmp_name' => $tmp,
		);
		$id   = media_handle_sideload( $file, $post_id );
		if ( is_wp_error( $id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return 0;
		}

		return (int) $id;
	}

	/**
	 * Handle an uploaded demo package (.zip) and import it.
	 */
	public function import_zip() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'general-slider' ) );
		}
		check_admin_referer( self::ZIP_ACTION );

		$result = 0;
		if ( isset( $_FILES['gs_zip']['tmp_name'] ) && is_uploaded_file( $_FILES['gs_zip']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$result = self::import_zip_file( $_FILES['gs_zip']['tmp_name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		}

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
	 * Create a slider from an exported demo .zip, sideloading the images it contains.
	 *
	 * @param string $tmp_zip Path to the uploaded zip.
	 * @return int New slider ID, or 0 on failure.
	 */
	private static function import_zip_file( $tmp_zip ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return 0;
		}
		$zip = new \ZipArchive();
		if ( true !== $zip->open( $tmp_zip ) ) {
			return 0;
		}

		// Locate the demo JSON inside the package (the one that has slides).
		$data  = null;
		$count = $zip->count();
		for ( $i = 0; $i < $count; $i++ ) {
			$name = $zip->getNameIndex( $i );
			if ( '.json' !== substr( $name, -5 ) || 'manifest-entry.json' === basename( $name ) ) {
				continue;
			}
			$decoded = json_decode( $zip->getFromIndex( $i ), true );
			if ( is_array( $decoded ) && ! empty( $decoded['slides'] ) && is_array( $decoded['slides'] ) ) {
				$data = $decoded;
				break;
			}
		}
		if ( ! $data ) {
			$zip->close();
			return 0;
		}

		$demo_id = ! empty( $data['id'] ) ? sanitize_key( $data['id'] ) : '';
		$result  = self::build_slider(
			$data,
			'draft',
			'zip',
			$demo_id,
			function ( $raw, $pid ) use ( $zip ) {
				if ( empty( $raw['image_url'] ) ) {
					return 0;
				}
				$base  = basename( (string) wp_parse_url( $raw['image_url'], PHP_URL_PATH ) );
				$bytes = $base ? $zip->getFromName( 'assets/images/' . $base ) : false;
				return ( false !== $bytes ) ? self::sideload_bytes( $bytes, $base, $pid ) : 0;
			}
		);
		$zip->close();

		return $result;
	}

	/**
	 * Write raw image bytes to a temp file and sideload it into the Media Library.
	 *
	 * @param string $bytes   Raw image data.
	 * @param string $name    File name.
	 * @param int    $post_id Slider to attach it to.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private static function sideload_bytes( $bytes, $name, $post_id ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = wp_tempnam( $name );
		if ( ! $tmp || false === file_put_contents( $tmp, $bytes ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- writing extracted zip bytes to a temp file for sideload.
			if ( $tmp && file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return 0;
		}

		$file = array(
			'name'     => $name,
			'tmp_name' => $tmp,
		);
		$id   = media_handle_sideload( $file, $post_id );
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
