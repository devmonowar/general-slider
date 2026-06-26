<?php
/**
 * Slide editor (native repeater) and per-slider settings meta boxes.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the slider edit screen: slides, settings and embed help.
 */
class Slides_Meta {

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_boxes' ) );
		add_action( 'save_post_' . Post_Type::SLUG, array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register the meta boxes.
	 */
	public function add_boxes() {
		add_meta_box( 'gs_slides', __( 'Slides', 'general-slider' ), array( $this, 'render_slides' ), Post_Type::SLUG, 'normal', 'high' );
		add_meta_box( 'gs_css', __( 'Custom CSS', 'general-slider' ), array( $this, 'render_css' ), Post_Type::SLUG, 'normal', 'low' );
		add_meta_box( 'gs_settings', __( 'Slider settings', 'general-slider' ), array( $this, 'render_settings' ), Post_Type::SLUG, 'side', 'default' );
		add_meta_box( 'gs_embed', __( 'How to embed', 'general-slider' ), array( $this, 'render_embed' ), Post_Type::SLUG, 'side', 'default' );
	}

	/**
	 * Render the repeatable slides UI.
	 *
	 * @param \WP_Post $post Current slider.
	 */
	public function render_slides( $post ) {
		wp_nonce_field( 'gs_save_slider', 'gs_slider_nonce' );
		$slides = Data::get_slides( $post->ID );
		?>
		<div class="gs-repeater" id="gs-repeater">
			<div class="gs-repeater__list">
				<?php
				if ( $slides ) {
					foreach ( $slides as $i => $slide ) {
						$this->slide_row( $i, $slide );
					}
				}
				?>
			</div>
			<p class="gs-repeater__actions">
				<button type="button" class="button button-primary" id="gs-add-slide"><?php esc_html_e( 'Add slide', 'general-slider' ); ?></button>
			</p>
			<script type="text/html" id="gs-slide-template">
				<?php $this->slide_row( '__i__', array() ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * Output one slide row.
	 *
	 * @param int|string $i     Row index (or __i__ placeholder for the template).
	 * @param array      $slide Slide data.
	 */
	private function slide_row( $i, $slide ) {
		$slide    = wp_parse_args( $slide, Data::normalise_slide( array() ) );
		$image_id = absint( $slide['image_id'] );
		$thumb    = $image_id ? wp_get_attachment_image( $image_id, 'medium', false, array( 'class' => 'gs-slide-row__img' ) ) : '';
		$name     = 'gs_slides[' . $i . ']';
		?>
		<div class="gs-slide-row" data-index="<?php echo esc_attr( $i ); ?>">
			<div class="gs-slide-row__handle" title="<?php esc_attr_e( 'Drag to reorder', 'general-slider' ); ?>">
				<span class="dashicons dashicons-menu"></span>
				<span class="gs-slide-row__num"></span>
			</div>
			<div class="gs-slide-row__media">
				<div class="gs-slide-row__preview"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<input type="hidden" class="gs-image-id" name="<?php echo esc_attr( $name ); ?>[image_id]" value="<?php echo esc_attr( $image_id ); ?>" />
				<button type="button" class="button gs-choose-image"><?php esc_html_e( 'Choose image', 'general-slider' ); ?></button>
				<button type="button" class="button-link gs-remove-image"<?php echo $image_id ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Remove', 'general-slider' ); ?></button>
			</div>
			<div class="gs-slide-row__fields">
				<label><?php esc_html_e( 'Sub heading', 'general-slider' ); ?>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[sub_heading]" value="<?php echo esc_attr( $slide['sub_heading'] ); ?>" />
				</label>
				<label><?php esc_html_e( 'Heading', 'general-slider' ); ?>
					<input type="text" name="<?php echo esc_attr( $name ); ?>[heading]" value="<?php echo esc_attr( $slide['heading'] ); ?>" />
				</label>
				<label><?php esc_html_e( 'Text', 'general-slider' ); ?>
					<textarea name="<?php echo esc_attr( $name ); ?>[text]" rows="2"><?php echo esc_textarea( $slide['text'] ); ?></textarea>
				</label>
				<div class="gs-slide-row__cols">
					<label><?php esc_html_e( 'Button text', 'general-slider' ); ?>
						<input type="text" name="<?php echo esc_attr( $name ); ?>[btn_text]" value="<?php echo esc_attr( $slide['btn_text'] ); ?>" />
					</label>
					<label><?php esc_html_e( 'Button URL', 'general-slider' ); ?>
						<input type="text" name="<?php echo esc_attr( $name ); ?>[btn_url]" value="<?php echo esc_attr( $slide['btn_url'] ); ?>" placeholder="https://, /page or #" />
					</label>
				</div>
				<div class="gs-slide-row__cols">
					<label><?php esc_html_e( 'Whole-slide link (optional)', 'general-slider' ); ?>
						<input type="text" name="<?php echo esc_attr( $name ); ?>[link]" value="<?php echo esc_attr( $slide['link'] ); ?>" placeholder="https://" />
					</label>
					<label class="gs-slide-row__inline">
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[new_tab]" value="1" <?php checked( ! empty( $slide['new_tab'] ) ); ?> />
						<?php esc_html_e( 'Open link in new tab', 'general-slider' ); ?>
					</label>
				</div>
				<div class="gs-slide-row__cols">
					<label><?php esc_html_e( 'Background video (MP4/WebM, YouTube or Vimeo URL)', 'general-slider' ); ?>
						<input type="url" class="gs-video-url" name="<?php echo esc_attr( $name ); ?>[video]" value="<?php echo esc_attr( $slide['video'] ); ?>" placeholder="https://youtube.com/watch?v=… or …/video.mp4" />
					</label>
					<label class="gs-slide-row__inline">
						<button type="button" class="button gs-choose-video"><?php esc_html_e( 'Choose file', 'general-slider' ); ?></button>
					</label>
				</div>
			</div>
			<button type="button" class="button-link gs-remove-slide" title="<?php esc_attr_e( 'Remove slide', 'general-slider' ); ?>"><span class="dashicons dashicons-trash"></span></button>
		</div>
		<?php
	}

	/**
	 * Render the per-slider settings box.
	 *
	 * @param \WP_Post $post Current slider.
	 */
	public function render_settings( $post ) {
		$s = Data::get_settings( $post->ID );
		?>
		<p>
			<label for="gs-preset"><strong><?php esc_html_e( 'Design preset', 'general-slider' ); ?></strong></label><br />
			<select id="gs-preset" name="gs_settings[preset]" style="width:100%">
				<?php foreach ( Data::presets() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['preset'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="gs-transition"><strong><?php esc_html_e( 'Transition', 'general-slider' ); ?></strong></label><br />
			<select id="gs-transition" name="gs_settings[transition]" style="width:100%">
				<?php foreach ( Data::transitions() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['transition'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label><input type="checkbox" name="gs_settings[autoplay]" value="1" <?php checked( $s['autoplay'] ); ?> /> <?php esc_html_e( 'Autoplay', 'general-slider' ); ?></label>
		</p>
		<p>
			<label for="gs-speed"><?php esc_html_e( 'Autoplay speed (ms)', 'general-slider' ); ?></label>
			<input type="number" id="gs-speed" name="gs_settings[speed]" value="<?php echo esc_attr( $s['speed'] ); ?>" min="1000" step="500" style="width:100%" />
		</p>
		<p><label><input type="checkbox" name="gs_settings[loop]" value="1" <?php checked( $s['loop'] ); ?> /> <?php esc_html_e( 'Loop', 'general-slider' ); ?></label></p>
		<p><label><input type="checkbox" name="gs_settings[arrows]" value="1" <?php checked( $s['arrows'] ); ?> /> <?php esc_html_e( 'Show arrows', 'general-slider' ); ?></label></p>
		<p><label><input type="checkbox" name="gs_settings[dots]" value="1" <?php checked( $s['dots'] ); ?> /> <?php esc_html_e( 'Show dots', 'general-slider' ); ?></label></p>
		<p>
			<label for="gs-height"><?php esc_html_e( 'Slide height (px)', 'general-slider' ); ?></label>
			<input type="number" id="gs-height" name="gs_settings[height]" value="<?php echo esc_attr( $s['height'] ); ?>" min="120" max="1200" step="10" style="width:100%" />
		</p>
		<p>
			<label for="gs-fit"><strong><?php esc_html_e( 'Image fit', 'general-slider' ); ?></strong></label><br />
			<select id="gs-fit" name="gs_settings[fit]" style="width:100%">
				<?php foreach ( Data::image_fits() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['fit'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="gs-focus"><strong><?php esc_html_e( 'Image focus', 'general-slider' ); ?></strong></label><br />
			<select id="gs-focus" name="gs_settings[focus]" style="width:100%">
				<?php foreach ( Data::focus_positions() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['focus'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php esc_html_e( 'For "Cover" fit, use "Top" to keep faces from being cropped.', 'general-slider' ); ?></span>
		</p>
		<p>
			<label for="gs-overlay"><?php esc_html_e( 'Overlay darkness (%)', 'general-slider' ); ?></label>
			<input type="number" id="gs-overlay" name="gs_settings[overlay]" value="<?php echo esc_attr( $s['overlay'] ); ?>" min="0" max="100" step="5" style="width:100%" />
		</p>
		<p>
			<label for="gs-overlay-style"><?php esc_html_e( 'Overlay style', 'general-slider' ); ?></label>
			<select id="gs-overlay-style" name="gs_settings[overlay_style]" style="width:100%">
				<?php foreach ( Data::overlay_styles() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['overlay_style'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<hr />
		<p>
			<label for="gs-per-page"><strong><?php esc_html_e( 'Slides per view', 'general-slider' ); ?></strong></label>
			<input type="number" id="gs-per-page" name="gs_settings[per_page]" value="<?php echo esc_attr( $s['per_page'] ); ?>" min="1" max="6" style="width:100%" />
		</p>
		<p>
			<label for="gs-gap"><?php esc_html_e( 'Gap between slides (px)', 'general-slider' ); ?></label>
			<input type="number" id="gs-gap" name="gs_settings[gap]" value="<?php echo esc_attr( $s['gap'] ); ?>" min="0" max="100" step="2" style="width:100%" />
		</p>
		<p>
			<label for="gs-accent"><strong><?php esc_html_e( 'Accent color', 'general-slider' ); ?></strong></label><br />
			<input type="color" id="gs-accent" name="gs_settings[accent]" value="<?php echo esc_attr( $s['accent'] ); ?>" />
		</p>
		<hr />
		<p><label><input type="checkbox" name="gs_settings[ken_burns]" value="1" <?php checked( ! empty( $s['ken_burns'] ) ); ?> /> <?php esc_html_e( 'Ken Burns zoom', 'general-slider' ); ?></label></p>
		<p><label><input type="checkbox" name="gs_settings[animate]" value="1" <?php checked( ! empty( $s['animate'] ) ); ?> /> <?php esc_html_e( 'Animate text in', 'general-slider' ); ?></label></p>
		<p><label><input type="checkbox" name="gs_settings[thumbnails]" value="1" <?php checked( ! empty( $s['thumbnails'] ) ); ?> /> <?php esc_html_e( 'Show thumbnails', 'general-slider' ); ?></label></p>
		<?php
	}

	/**
	 * Render the custom CSS box.
	 *
	 * @param \WP_Post $post Current slider.
	 */
	public function render_css( $post ) {
		$css = get_post_meta( $post->ID, Data::META_CSS, true );
		?>
		<p class="description"><?php esc_html_e( 'CSS applied only to this slider. Use the scope selector shown in the placeholder.', 'general-slider' ); ?></p>
		<textarea name="gs_custom_css" rows="6" class="widefat code" placeholder="#gs-slider-<?php echo (int) $post->ID; ?> .gs-slide__title { color: #fff; }"><?php echo esc_textarea( $css ); ?></textarea>
		<?php
	}

	/**
	 * Render the embed-help box.
	 *
	 * @param \WP_Post $post Current slider.
	 */
	public function render_embed( $post ) {
		$shortcode = sprintf( '[general_slider id="%d"]', $post->ID );
		?>
		<p><?php esc_html_e( 'Add the "General Slider" block to any page and pick this slider, or paste this shortcode:', 'general-slider' ); ?></p>
		<input type="text" class="gs-shortcode-copy" readonly onclick="this.select()" title="<?php echo esc_attr__( 'Click to copy', 'general-slider' ); ?>" data-copied="<?php echo esc_attr__( 'Copied!', 'general-slider' ); ?>" value="<?php echo esc_attr( $shortcode ); ?>" style="width:100%" />
		<?php
	}

	/**
	 * Save slides and settings.
	 *
	 * @param int      $post_id Slider ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['gs_slider_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gs_slider_nonce'] ), 'gs_save_slider' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Slides — sanitised via Data::normalise_slide().
		// Nonce verified above. Every field is sanitised in Data::normalise_slide().
		$raw_slides = isset( $_POST['gs_slides'] ) && is_array( $_POST['gs_slides'] ) ? wp_unslash( $_POST['gs_slides'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$slides     = array();
		foreach ( $raw_slides as $raw ) {
			$slide = Data::normalise_slide( $raw );
			// Skip completely empty rows.
			if ( $slide && ( $slide['image_id'] || '' !== $slide['heading'] || '' !== $slide['sub_heading'] || '' !== $slide['text'] ) ) {
				$slides[] = $slide;
			}
		}
		update_post_meta( $post_id, Data::META_SLIDES, $slides );

		// Settings. Nonce verified above; values sanitised in Data::sanitize_settings().
		$raw_settings = isset( $_POST['gs_settings'] ) && is_array( $_POST['gs_settings'] ) ? wp_unslash( $_POST['gs_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		update_post_meta( $post_id, Data::META_SETTINGS, Data::sanitize_settings( $raw_settings ) );

		// Custom CSS. Nonce verified above; "<" stripped to prevent markup injection.
		$raw_css = isset( $_POST['gs_custom_css'] ) ? wp_unslash( $_POST['gs_custom_css'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		update_post_meta( $post_id, Data::META_CSS, Tools::clean_css( $raw_css ) );
	}
}
