<?php
/**
 * Demo Export: turns a slider into a portable ZIP for the remote demo library.
 *
 * The ZIP contains the demo JSON (with image URLs already rewritten to the
 * demo-library base), the image files, an auto-generated preview and a manifest
 * entry to paste into demo-library.json — so no manual JSON editing is needed.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Demo Export" row action to the sliders list.
 */
class Demo_Export {

	const ACTION = 'gs_demo_export';

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_filter( 'post_row_actions', array( $this, 'row_action' ), 20, 2 );
		add_action( 'admin_action_' . self::ACTION, array( $this, 'export' ) );
		add_action( 'admin_notices', array( $this, 'export_notice' ) );
	}

	/**
	 * Add the "Demo Export" link to the row actions.
	 *
	 * @param array    $actions Row actions.
	 * @param \WP_Post $post    Post object.
	 * @return array
	 */
	public function row_action( $actions, $post ) {
		if ( Post_Type::SLUG === $post->post_type && current_user_can( 'manage_options' ) ) {
			$url                       = wp_nonce_url(
				admin_url( 'edit.php?post_type=' . Post_Type::SLUG . '&gs_export=' . $post->ID ),
				self::ACTION . '_' . $post->ID
			);
			$actions['gs_demo_export'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Export Slider', 'general-slider' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * After "Export Slider", show a success notice and auto-start the download.
	 */
	public function export_notice() {
		if ( ! isset( $_GET['gs_export'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$id    = absint( $_GET['gs_export'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( $_GET['_wpnonce'] ) : '';
		if ( ! $id || ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $nonce, self::ACTION . '_' . $id ) ) {
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>%s</strong><br>%s</p></div>',
			esc_html__( 'Demo package created successfully.', 'general-slider' ),
			esc_html__( 'Download will begin shortly.', 'general-slider' )
		);

		$download = wp_nonce_url( admin_url( 'admin.php?action=' . self::ACTION . '&post=' . $id ), self::ACTION . '_' . $id );
		wp_print_inline_script_tag( 'setTimeout(function(){window.location=' . wp_json_encode( $download ) . ';},800);' );
	}

	/**
	 * Build and stream the demo ZIP.
	 */
	public function export() {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( self::ACTION . '_' . $id );

		$post = $id ? get_post( $id ) : null;
		if ( ! $post || Post_Type::SLUG !== $post->post_type || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'general-slider' ) );
		}
		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( esc_html__( 'Export Slider needs the PHP Zip extension, which is not available on this server.', 'general-slider' ) );
		}

		$slug = sanitize_title( $post->post_title );
		$slug = $slug ? $slug : 'slider-' . $id;

		// Derive the demo-library base URLs from the manifest URL.
		$base        = trailingslashit( dirname( Demo_Library::manifest_url() ) );
		$images_base = $base . 'assets/images/';

		$slides     = Data::get_slides( $id );
		$slides_out = array();
		$image_map  = array(); // zip-path => local file.
		$first_img  = '';
		$i          = 0;
		foreach ( $slides as $slide ) {
			++$i;
			$out = array();
			if ( $slide['image_id'] ) {
				$file = get_attached_file( $slide['image_id'] );
				if ( $file && file_exists( $file ) ) {
					$ext                = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
					$name               = $slug . '-' . $i . '.' . $ext;
					$image_map[ $name ] = $file;
					$out['image_url']   = $images_base . $name;
					$first_img          = $first_img ? $first_img : $file;
				}
			}
			foreach ( array( 'video', 'sub_heading', 'heading', 'text', 'btn_text', 'btn_url', 'link' ) as $field ) {
				if ( '' !== $slide[ $field ] ) {
					$out[ $field ] = $slide[ $field ];
				}
			}
			if ( ! empty( $slide['new_tab'] ) ) {
				$out['new_tab'] = true;
			}
			$slides_out[] = $out;
		}

		$demo = array(
			'schema_version' => 1,
			'plugin'         => 'general-slider',
			'id'             => $slug,
			'name'           => $post->post_title,
			'version'        => '1.0',
			'title'          => $post->post_title,
			'slides'         => $slides_out,
			'settings'       => Data::get_settings( $id ),
			'custom_css'     => (string) get_post_meta( $id, Data::META_CSS, true ),
		);

		$entry = array(
			'id'             => $slug,
			'name'           => $post->post_title,
			'description'    => '',
			'version'        => '1.0',
			'updated'        => gmdate( 'Y-m-d' ),
			'requires'       => GENERAL_SLIDER_VERSION,
			'category'       => '',
			'tags'           => array(),
			'featured'       => false,
			'new'            => true,
			'preview'        => $base . 'previews/' . $slug . '.jpg',
			'preview_width'  => 1200,
			'preview_height' => 675,
			'file'           => $base . 'demos/' . $slug . '.json',
		);

		$this->stream_zip( $slug, $demo, $entry, $image_map, $first_img );
	}

	/**
	 * Assemble the ZIP and send it to the browser.
	 *
	 * @param string $slug      Demo slug.
	 * @param array  $demo      Demo JSON data.
	 * @param array  $entry     Manifest entry.
	 * @param array  $image_map zip-name => local file path.
	 * @param string $first_img Source image for the preview (may be empty).
	 */
	private function stream_zip( $slug, $demo, $entry, $image_map, $first_img ) {
		$json_args = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

		$tmp_zip = wp_tempnam( $slug . '-demo.zip' );
		$zip     = new \ZipArchive();
		if ( true !== $zip->open( $tmp_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) {
			wp_delete_file( $tmp_zip );
			wp_die( esc_html__( 'Could not create the demo ZIP.', 'general-slider' ) );
		}

		$zip->addFromString( $slug . '.json', (string) wp_json_encode( $demo, $json_args ) );
		$zip->addFromString( 'manifest-entry.json', (string) wp_json_encode( $entry, $json_args ) );
		$zip->addFromString( 'README.txt', $this->readme( $slug ) );

		foreach ( $image_map as $name => $file ) {
			$zip->addFile( $file, 'assets/images/' . $name );
		}

		// Auto-generate a preview from the first slide image.
		$preview_tmp = $first_img ? $this->make_preview( $first_img ) : '';
		if ( $preview_tmp ) {
			$zip->addFile( $preview_tmp, 'previews/' . $slug . '.jpg' );
		}

		$zip->close();

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename=' . $slug . '-demo.zip' );
		header( 'Content-Length: ' . filesize( $tmp_zip ) );
		readfile( $tmp_zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a generated download.

		if ( $preview_tmp ) {
			wp_delete_file( $preview_tmp );
		}
		wp_delete_file( $tmp_zip );
		exit;
	}

	/**
	 * Make a 1200x675 JPEG preview from an image, returning its temp path.
	 *
	 * @param string $file Source image path.
	 * @return string Temp file path, or '' on failure.
	 */
	private function make_preview( $file ) {
		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return '';
		}
		$editor->resize( 1200, 675, true );
		$tmp   = wp_tempnam( 'gs-preview.jpg' );
		$saved = $editor->save( $tmp, 'image/jpeg' );
		if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
			wp_delete_file( $tmp );
			return '';
		}
		// The editor may save to a path with its own extension; use what it reports.
		if ( $saved['path'] !== $tmp && file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}
		return $saved['path'];
	}

	/**
	 * The instructions bundled inside the ZIP.
	 *
	 * @param string $slug Demo slug.
	 * @return string
	 */
	private function readme( $slug ) {
		return "General Slider — Export Slider: {$slug}\n"
			. "=======================================\n\n"
			. "To publish this demo in your wp-plugin-demo-library repository:\n\n"
			. "1. Copy assets/images/*  ->  general-slider/assets/images/\n"
			. "2. Copy previews/{$slug}.jpg  ->  general-slider/previews/\n"
			. "3. Copy {$slug}.json  ->  general-slider/demos/\n"
			. "4. Paste the object in manifest-entry.json into the \"demos\" array of\n"
			. "   general-slider/demo-library.json (fill in description, category, tags).\n"
			. "5. Record each image's source + license in general-slider/CREDITS.md\n"
			. "   (use CC0 / public-domain images only).\n"
			. "6. Commit & push. The demo appears in the plugin automatically.\n\n"
			. "Image URLs in {$slug}.json already point to the demo-library base, so no\n"
			. "manual JSON editing is required.\n";
	}
}
