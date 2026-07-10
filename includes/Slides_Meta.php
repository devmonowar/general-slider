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
		$settings   = Data::get_settings( $post->ID );
		$source     = is_array( $settings['source'] ?? null ) ? $settings['source'] : Data::default_source();
		$is_dynamic = 'dynamic' === ( $source['type'] ?? 'manual' );
		// For the manual editor, read the stored slides directly (not the
		// dynamic resolver, which would return query results here).
		$manual_slides = get_post_meta( $post->ID, Data::META_SLIDES, true );
		$manual_slides = is_array( $manual_slides ) ? array_values( array_filter( array_map( array( 'GeneralSlider\\Data', 'normalise_slide' ), $manual_slides ) ) ) : array();

		// On a brand-new, empty slider, invite the user to start from a demo.
		if ( 'auto-draft' === $post->post_status && empty( $manual_slides ) ) {
			$this->render_start_from_demo();
		}

		$this->render_source( $source );
		?>
		<div class="gs-repeater" id="gs-repeater"<?php echo $is_dynamic ? ' style="display:none"' : ''; ?>>
			<div class="gs-repeater__list">
				<?php
				if ( $manual_slides ) {
					foreach ( $manual_slides as $i => $slide ) {
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
	 * Invite a first-time user to import a ready-made demo instead of starting
	 * from a blank editor. Shown only on a new, empty slider.
	 */
	private function render_start_from_demo() {
		$url = admin_url( 'edit.php?post_type=' . Post_Type::SLUG . '&page=' . Demo_Library::PAGE );
		?>
		<div class="gs-start-demo">
			<span class="dashicons dashicons-images-alt2" aria-hidden="true"></span>
			<div class="gs-start-demo__text">
				<strong><?php esc_html_e( 'Want a head start?', 'general-slider' ); ?></strong>
				<?php esc_html_e( 'Import a ready-made slider from the Demo Library — images included — then tweak it here.', 'general-slider' ); ?>
			</div>
			<a href="<?php echo esc_url( $url ); ?>" class="button"><?php esc_html_e( 'Browse demos', 'general-slider' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Render the "Slides source" selector and the dynamic-query panel.
	 *
	 * @param array $source Sanitised source config.
	 */
	private function render_source( $source ) {
		$type       = 'dynamic' === ( $source['type'] ?? 'manual' ) ? 'dynamic' : 'manual';
		$post_types = Dynamic_Slides::available_post_types();
		?>
		<div class="gs-source">
			<p class="gs-source__toggle">
				<label><input type="radio" name="gs_settings[source][type]" value="manual" <?php checked( $type, 'manual' ); ?> /> <?php esc_html_e( 'Build slides manually', 'general-slider' ); ?></label>
				&nbsp;&nbsp;
				<label><input type="radio" name="gs_settings[source][type]" value="dynamic" <?php checked( $type, 'dynamic' ); ?> /> <?php esc_html_e( 'Pull slides from posts (dynamic)', 'general-slider' ); ?></label>
			</p>
			<div class="gs-source__panel" id="gs-source-panel"<?php echo 'dynamic' === $type ? '' : ' style="display:none"'; ?>>
				<p class="description"><?php esc_html_e( 'Slides are generated from your published content — the featured image, title, excerpt and link. Every preset and skin still applies.', 'general-slider' ); ?></p>
				<div class="gs-source__grid">
					<label><?php esc_html_e( 'Content type', 'general-slider' ); ?>
						<select id="gs-source-post-type" name="gs_settings[source][post_type]">
							<?php foreach ( $post_types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $source['post_type'] ?? 'post', $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label><?php esc_html_e( 'Filter by', 'general-slider' ); ?>
						<select id="gs-source-taxonomy" name="gs_settings[source][taxonomy]" data-selected="<?php echo esc_attr( $source['taxonomy'] ?? '' ); ?>"></select>
					</label>
					<label><?php esc_html_e( 'Term', 'general-slider' ); ?>
						<select id="gs-source-term" name="gs_settings[source][term]" data-selected="<?php echo esc_attr( $source['term'] ?? 0 ); ?>"></select>
					</label>
					<label><?php esc_html_e( 'How many', 'general-slider' ); ?>
						<input type="number" name="gs_settings[source][count]" value="<?php echo esc_attr( $source['count'] ?? 6 ); ?>" min="1" max="20" />
					</label>
					<label><?php esc_html_e( 'Order by', 'general-slider' ); ?>
						<select name="gs_settings[source][orderby]">
							<?php foreach ( Data::source_orderbys() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $source['orderby'] ?? 'date', $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label><?php esc_html_e( 'Order', 'general-slider' ); ?>
						<select name="gs_settings[source][order]">
							<option value="DESC" <?php selected( $source['order'] ?? 'DESC', 'DESC' ); ?>><?php esc_html_e( 'Newest first', 'general-slider' ); ?></option>
							<option value="ASC" <?php selected( $source['order'] ?? 'DESC', 'ASC' ); ?>><?php esc_html_e( 'Oldest first', 'general-slider' ); ?></option>
						</select>
					</label>
				</div>
				<p>
					<label><input type="checkbox" name="gs_settings[source][show_excerpt]" value="1" <?php checked( ! empty( $source['show_excerpt'] ) ); ?> /> <?php esc_html_e( 'Show excerpt', 'general-slider' ); ?></label>
					&nbsp;&nbsp;
					<label><?php esc_html_e( 'Excerpt words', 'general-slider' ); ?>
						<input type="number" name="gs_settings[source][excerpt_words]" value="<?php echo esc_attr( $source['excerpt_words'] ?? 20 ); ?>" min="0" max="100" style="width:70px" />
					</label>
				</p>
				<p>
					<label><?php esc_html_e( 'Button text (leave blank for no button)', 'general-slider' ); ?><br />
						<input type="text" name="gs_settings[source][button_text]" value="<?php echo esc_attr( $source['button_text'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Read more', 'general-slider' ); ?>" style="width:100%;max-width:300px" />
					</label>
				</p>
			</div>
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
				<input type="hidden" class="gs-image-id" name="<?php echo esc_attr( $name ); ?>[image_id]" value="<?php echo esc_attr( (string) $image_id ); ?>" />
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
			<label for="gs-skin"><strong><?php esc_html_e( 'Navigation & frame skin', 'general-slider' ); ?></strong></label><br />
			<select id="gs-skin" name="gs_settings[skin]" style="width:100%">
				<?php foreach ( Data::skins() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['skin'] ?? 'classic', $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php esc_html_e( 'The look of the arrows, dots and slider frame — works with any preset.', 'general-slider' ); ?></span>
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
		<p>
			<label for="gs-animation"><strong><?php esc_html_e( 'Text animation', 'general-slider' ); ?></strong></label><br />
			<select id="gs-animation" name="gs_settings[animation]" style="width:100%">
				<?php $gs_anim = Data::resolve_animation( $s ); ?>
				<?php foreach ( Data::animations() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $gs_anim, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php esc_html_e( 'The sub-heading, heading, text and button enter one after another.', 'general-slider' ); ?></span>
		</p>
		<p><label><input type="checkbox" name="gs_settings[thumbnails]" value="1" <?php checked( ! empty( $s['thumbnails'] ) ); ?> /> <?php esc_html_e( 'Show thumbnails', 'general-slider' ); ?></label></p>
		<hr />
		<p><strong><?php esc_html_e( 'Responsive', 'general-slider' ); ?></strong><br />
		<span class="description"><?php esc_html_e( 'Leave a field blank to keep the desktop value on that device.', 'general-slider' ); ?></span></p>
		<?php
		$responsive = is_array( $s['responsive'] ?? null ) ? $s['responsive'] : array();
		$this->responsive_group( __( 'Tablet', 'general-slider' ), 'tablet', is_array( $responsive['tablet'] ?? null ) ? $responsive['tablet'] : array() );
		$this->responsive_group( __( 'Mobile', 'general-slider' ), 'mobile', is_array( $responsive['mobile'] ?? null ) ? $responsive['mobile'] : array() );
	}

	/**
	 * Render one breakpoint's group of responsive override fields.
	 *
	 * @param string $label Human label ("Tablet" / "Mobile").
	 * @param string $key   Settings key ("tablet" / "mobile").
	 * @param array  $bp    That breakpoint's saved overrides.
	 */
	private function responsive_group( $label, $key, $bp ) {
		$name = 'gs_settings[responsive][' . $key . ']';
		?>
		<div class="gs-responsive-group">
			<p><em><?php echo esc_html( $label ); ?></em></p>
			<div class="gs-slide-row__cols">
				<label><?php esc_html_e( 'Slides per view', 'general-slider' ); ?>
					<input type="number" name="<?php echo esc_attr( $name ); ?>[per_page]" value="<?php echo isset( $bp['per_page'] ) ? esc_attr( $bp['per_page'] ) : ''; ?>" min="1" max="6" placeholder="<?php esc_attr_e( 'Same as desktop', 'general-slider' ); ?>" />
				</label>
				<label><?php esc_html_e( 'Gap (px)', 'general-slider' ); ?>
					<input type="number" name="<?php echo esc_attr( $name ); ?>[gap]" value="<?php echo isset( $bp['gap'] ) ? esc_attr( $bp['gap'] ) : ''; ?>" min="0" max="100" placeholder="<?php esc_attr_e( 'Same as desktop', 'general-slider' ); ?>" />
				</label>
			</div>
			<div class="gs-slide-row__cols">
				<label><?php esc_html_e( 'Height (px)', 'general-slider' ); ?>
					<input type="number" name="<?php echo esc_attr( $name ); ?>[height]" value="<?php echo isset( $bp['height'] ) ? esc_attr( $bp['height'] ) : ''; ?>" min="120" max="1200" placeholder="<?php esc_attr_e( 'Same as desktop', 'general-slider' ); ?>" />
				</label>
				<label class="gs-slide-row__inline" style="align-self:end">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_content]" value="1" <?php checked( ! empty( $bp['hide_content'] ) ); ?> />
					<?php esc_html_e( 'Hide text & button', 'general-slider' ); ?>
				</label>
			</div>
			<div class="gs-slide-row__cols">
				<label><?php esc_html_e( 'Arrows', 'general-slider' ); ?>
					<?php $this->inherit_select( $name . '[arrows]', $bp['arrows'] ?? null ); ?>
				</label>
				<label><?php esc_html_e( 'Dots', 'general-slider' ); ?>
					<?php $this->inherit_select( $name . '[dots]', $bp['dots'] ?? null ); ?>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * A tri-state Inherit / Show / Hide select.
	 *
	 * @param string    $name  Field name.
	 * @param bool|null $value Saved value, or null when unset (inherit).
	 */
	private function inherit_select( $name, $value ) {
		$current = null === $value ? 'inherit' : ( $value ? 'show' : 'hide' );
		?>
		<select name="<?php echo esc_attr( $name ); ?>">
			<option value="inherit" <?php selected( $current, 'inherit' ); ?>><?php esc_html_e( 'Same as desktop', 'general-slider' ); ?></option>
			<option value="show" <?php selected( $current, 'show' ); ?>><?php esc_html_e( 'Show', 'general-slider' ); ?></option>
			<option value="hide" <?php selected( $current, 'hide' ); ?>><?php esc_html_e( 'Hide', 'general-slider' ); ?></option>
		</select>
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
