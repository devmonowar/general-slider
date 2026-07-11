<?php
/**
 * Global settings page (default slider behaviour) and plugin action link.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Settings page under the Sliders menu.
 */
class Settings {

	const GROUP = 'general_slider_settings_group';
	const PAGE  = 'general-slider-settings';

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_filter( 'plugin_action_links_' . GENERAL_SLIDER_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add the settings submenu under the Sliders menu.
	 */
	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Post_Type::SLUG,
			__( 'General Slider Settings', 'general-slider' ),
			__( 'Settings', 'general-slider' ),
			'manage_options',
			self::PAGE,
			array( $this, 'page' )
		);
	}

	/**
	 * Register the option and its sanitiser.
	 */
	public function register() {
		register_setting(
			self::GROUP,
			Data::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => Data::default_settings(),
			)
		);
	}

	/**
	 * Sanitise the settings array.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		return Data::sanitize_settings( $input );
	}

	/**
	 * Add a "Settings" link on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url  = admin_url( 'edit.php?post_type=' . Post_Type::SLUG . '&page=' . self::PAGE );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'general-slider' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Render the settings page.
	 */
	public function page() {
		$s = Data::global_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'General Slider Settings', 'general-slider' ); ?></h1>
			<p><?php esc_html_e( 'These are the default behaviours applied to new sliders. Each slider can override them individually.', 'general-slider' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gs-preset"><?php esc_html_e( 'Default design preset', 'general-slider' ); ?></label></th>
						<td>
							<select id="gs-preset" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[preset]">
								<?php foreach ( Data::presets() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['preset'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-skin"><?php esc_html_e( 'Default navigation & frame skin', 'general-slider' ); ?></label></th>
						<td>
							<select id="gs-skin" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[skin]">
								<?php foreach ( Data::skins() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['skin'] ?? 'classic', $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'The look of the arrows, dots and slider frame — works with any preset.', 'general-slider' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-transition"><?php esc_html_e( 'Default transition', 'general-slider' ); ?></label></th>
						<td>
							<select id="gs-transition" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[transition]">
								<?php foreach ( Data::transitions() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['transition'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-transition-speed"><?php esc_html_e( 'Transition speed (ms)', 'general-slider' ); ?></label></th>
						<td>
							<input type="number" id="gs-transition-speed" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[transition_speed]" value="<?php echo esc_attr( $s['transition_speed'] ?? 600 ); ?>" min="100" max="3000" step="50" class="small-text" />
							<p class="description"><?php esc_html_e( 'How long one slide takes to change. Lower = snappier.', 'general-slider' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Autoplay', 'general-slider' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[autoplay]" value="1" <?php checked( $s['autoplay'] ); ?> /> <?php esc_html_e( 'Play slides automatically', 'general-slider' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-speed"><?php esc_html_e( 'Autoplay speed (ms)', 'general-slider' ); ?></label></th>
						<td><input type="number" id="gs-speed" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[speed]" value="<?php echo esc_attr( $s['speed'] ); ?>" min="1000" step="500" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Controls', 'general-slider' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[loop]" value="1" <?php checked( $s['loop'] ); ?> /> <?php esc_html_e( 'Loop', 'general-slider' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[arrows]" value="1" <?php checked( $s['arrows'] ); ?> /> <?php esc_html_e( 'Show arrows', 'general-slider' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[dots]" value="1" <?php checked( $s['dots'] ); ?> /> <?php esc_html_e( 'Show dots', 'general-slider' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-height"><?php esc_html_e( 'Slide height (px)', 'general-slider' ); ?></label></th>
						<td><input type="number" id="gs-height" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[height]" value="<?php echo esc_attr( $s['height'] ); ?>" min="120" max="1200" step="10" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-fit"><?php esc_html_e( 'Image fit', 'general-slider' ); ?></label></th>
						<td>
							<select id="gs-fit" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[fit]">
								<?php foreach ( Data::image_fits() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['fit'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-focus"><?php esc_html_e( 'Image focus', 'general-slider' ); ?></label></th>
						<td>
							<select id="gs-focus" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[focus]">
								<?php foreach ( Data::focus_positions() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['focus'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'For "Cover" fit, use "Top" to keep faces from being cropped on wide slides.', 'general-slider' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-overlay"><?php esc_html_e( 'Overlay darkness (%)', 'general-slider' ); ?></label></th>
						<td><input type="number" id="gs-overlay" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[overlay]" value="<?php echo esc_attr( $s['overlay'] ); ?>" min="0" max="100" step="5" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-overlay-style"><?php esc_html_e( 'Overlay style', 'general-slider' ); ?></label></th>
						<td>
							<select id="gs-overlay-style" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[overlay_style]">
								<?php foreach ( Data::overlay_styles() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['overlay_style'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-per-page"><?php esc_html_e( 'Slides per view', 'general-slider' ); ?></label></th>
						<td><input type="number" id="gs-per-page" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[per_page]" value="<?php echo esc_attr( $s['per_page'] ); ?>" min="1" max="6" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-gap"><?php esc_html_e( 'Gap between slides (px)', 'general-slider' ); ?></label></th>
						<td><input type="number" id="gs-gap" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[gap]" value="<?php echo esc_attr( $s['gap'] ); ?>" min="0" max="100" step="2" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="gs-accent"><?php esc_html_e( 'Accent color', 'general-slider' ); ?></label></th>
						<td><input type="color" id="gs-accent" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[accent]" value="<?php echo esc_attr( $s['accent'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Effects', 'general-slider' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[ken_burns]" value="1" <?php checked( ! empty( $s['ken_burns'] ) ); ?> /> <?php esc_html_e( 'Ken Burns zoom', 'general-slider' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[thumbnails]" value="1" <?php checked( ! empty( $s['thumbnails'] ) ); ?> /> <?php esc_html_e( 'Show thumbnails', 'general-slider' ); ?></label>
							<p style="margin:8px 0 0">
								<label for="gs-animation"><?php esc_html_e( 'Text animation', 'general-slider' ); ?></label><br />
								<select id="gs-animation" name="<?php echo esc_attr( Data::OPTION_KEY ); ?>[animation]">
									<?php $gs_anim = Data::resolve_animation( $s ); ?>
									<?php foreach ( Data::animations() as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $gs_anim, $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Responsive', 'general-slider' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Default overrides for tablet and mobile. Leave a field blank to keep the desktop value on that device. Each slider can still override these individually.', 'general-slider' ); ?></p>
				<table class="form-table" role="presentation">
					<?php
					$responsive = is_array( $s['responsive'] ?? null ) ? $s['responsive'] : array();
					$this->responsive_rows( __( 'Tablet', 'general-slider' ), 'tablet', is_array( $responsive['tablet'] ?? null ) ? $responsive['tablet'] : array() );
					$this->responsive_rows( __( 'Mobile', 'general-slider' ), 'mobile', is_array( $responsive['mobile'] ?? null ) ? $responsive['mobile'] : array() );
					?>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Demo slider', 'general-slider' ); ?></h2>
			<p><?php esc_html_e( 'New here? Create the bundled starter slider, or browse the online Demo Library for more.', 'general-slider' ); ?></p>
			<div class="gs-demo-buttons" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
				<?php Demo_Importer::button(); ?>
				<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Post_Type::SLUG . '&page=' . Demo_Library::PAGE ) ); ?>"><?php esc_html_e( 'Browse Demo Library', 'general-slider' ); ?></a>
			</div>

			<hr />
			<h2><?php esc_html_e( 'Import / Export', 'general-slider' ); ?></h2>
			<?php Tools::ui(); ?>
			<?php Tools::file_required_script(); ?>
		</div>
		<?php
	}

	/**
	 * Render one breakpoint's row of default-responsive fields.
	 *
	 * @param string $label Human label ("Tablet" / "Mobile").
	 * @param string $key   Settings key ("tablet" / "mobile").
	 * @param array  $bp    That breakpoint's saved overrides.
	 */
	private function responsive_rows( $label, $key, $bp ) {
		$name = Data::OPTION_KEY . '[responsive][' . $key . ']';
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<div style="display:flex;flex-wrap:wrap;gap:10px 20px;align-items:center;">
					<label><?php esc_html_e( 'Slides per view', 'general-slider' ); ?>
						<input type="number" name="<?php echo esc_attr( $name ); ?>[per_page]" value="<?php echo isset( $bp['per_page'] ) ? esc_attr( $bp['per_page'] ) : ''; ?>" min="1" max="6" class="small-text" placeholder="<?php esc_attr_e( 'Same as desktop', 'general-slider' ); ?>" />
					</label>
					<label><?php esc_html_e( 'Gap (px)', 'general-slider' ); ?>
						<input type="number" name="<?php echo esc_attr( $name ); ?>[gap]" value="<?php echo isset( $bp['gap'] ) ? esc_attr( $bp['gap'] ) : ''; ?>" min="0" max="100" class="small-text" placeholder="<?php esc_attr_e( 'Same as desktop', 'general-slider' ); ?>" />
					</label>
					<label><?php esc_html_e( 'Height (px)', 'general-slider' ); ?>
						<input type="number" name="<?php echo esc_attr( $name ); ?>[height]" value="<?php echo isset( $bp['height'] ) ? esc_attr( $bp['height'] ) : ''; ?>" min="120" max="1200" class="small-text" placeholder="<?php esc_attr_e( 'Same as desktop', 'general-slider' ); ?>" />
					</label>
				</div>
				<div style="display:flex;flex-wrap:wrap;gap:10px 20px;align-items:center;margin-top:10px;">
					<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_content]" value="1" <?php checked( ! empty( $bp['hide_content'] ) ); ?> /> <?php esc_html_e( 'Hide text & button', 'general-slider' ); ?></label>
					<label><?php esc_html_e( 'Arrows:', 'general-slider' ); ?>
						<?php $this->inherit_select( $name . '[arrows]', $bp['arrows'] ?? null ); ?>
					</label>
					<label><?php esc_html_e( 'Dots:', 'general-slider' ); ?>
						<?php $this->inherit_select( $name . '[dots]', $bp['dots'] ?? null ); ?>
					</label>
				</div>
			</td>
		</tr>
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
}
