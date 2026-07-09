<?php
/**
 * A polite "enjoying this plugin?" review request.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Review prompts, confined to the plugin's own admin screens:
 *
 * - A small, always-visible rating link in the admin footer.
 * - A notice that first appears after ~two weeks of real use, and returns
 *   once a month until the user actually rates the plugin.
 * - Once "Rate it" is clicked, every prompt (notice and footer) disappears
 *   for good.
 */
class Review_Notice {

	const OPTION     = 'general_slider_review';
	const REVIEW_URL = 'https://wordpress.org/support/plugin/general-slider/reviews/#new-post';

	/**
	 * Days of use before the notice first appears.
	 */
	const WAIT_DAYS = 15;

	/**
	 * Days between repeat appearances (once a month).
	 */
	const SNOOZE_DAYS = 30;

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'start_clock' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render' ) );
		add_filter( 'admin_footer_text', array( $this, 'footer_text' ) );
	}

	/**
	 * A small, permanent rating link in the admin footer — only on this
	 * plugin's own screens, and only until the user has rated.
	 *
	 * @param string $text Default footer text.
	 * @return string
	 */
	public function footer_text( $text ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || Post_Type::SLUG !== $screen->post_type ) {
			return $text;
		}
		$state = (array) get_option( self::OPTION, array() );
		if ( ! empty( $state['rated'] ) ) {
			return $text;
		}
		$link = '<a href="' . esc_url( self::REVIEW_URL ) . '" target="_blank" rel="noopener noreferrer">';
		return sprintf(
			/* translators: 1: opening link tag to the WordPress.org review form, 2: closing link tag. */
			esc_html__( 'Enjoying General Slider? Leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733; review%2$s — it keeps development going.', 'general-slider' ),
			$link,
			'</a>'
		);
	}

	/**
	 * Record when the plugin was first seen in the admin, so existing installs
	 * also wait a full period after updating to a version with this notice.
	 */
	public function start_clock() {
		$state = get_option( self::OPTION );
		if ( ! is_array( $state ) || empty( $state['since'] ) ) {
			update_option( self::OPTION, array( 'since' => time() ), false );
		}
	}

	/**
	 * Process the notice's action links.
	 *
	 * Clicking "Rate it" only records that the review page was opened —
	 * the plugin cannot see WordPress.org reviews, so the prompts stop for
	 * good only after the user confirms "Yes, I left a review".
	 */
	public function handle_actions() {
		if ( empty( $_GET['gs_review'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'gs_review_notice' );

		$state  = (array) get_option( self::OPTION, array() );
		$action = sanitize_key( wp_unslash( $_GET['gs_review'] ) );

		if ( 'rated' === $action ) {
			$state['rated'] = true;
		} elseif ( 'rate' === $action ) {
			// Opened the review form: ask for confirmation on the next visit.
			$state['asked'] = true;
			unset( $state['snooze_until'] );
		} else {
			$state['snooze_until'] = time() + self::SNOOZE_DAYS * DAY_IN_SECONDS;
		}
		update_option( self::OPTION, $state, false );

		wp_safe_redirect( remove_query_arg( array( 'gs_review', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Render the notice when every polite condition is met.
	 */
	public function maybe_render() {
		if ( ! current_user_can( 'manage_options' ) || ! $this->should_show() ) {
			return;
		}

		$state = (array) get_option( self::OPTION, array() );
		$later = wp_nonce_url( add_query_arg( 'gs_review', 'later' ), 'gs_review_notice' );
		$rate  = wp_nonce_url( add_query_arg( 'gs_review', 'rate' ), 'gs_review_notice' );
		$rated = wp_nonce_url( add_query_arg( 'gs_review', 'rated' ), 'gs_review_notice' );

		if ( empty( $state['asked'] ) ) {
			$message = '<strong>' . esc_html__( 'Enjoying General Slider?', 'general-slider' ) . '</strong> '
				. esc_html__( 'A quick 5-star review helps other people find the plugin and keeps development going. Thank you!', 'general-slider' );
		} else {
			$message = '<strong>' . esc_html__( 'Did you get a chance to leave that review?', 'general-slider' ) . '</strong> '
				. esc_html__( 'If you did — thank you! Confirm below and we will never ask again.', 'general-slider' );
		}
		?>
		<div class="notice notice-info" style="padding:12px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
			<p style="margin:0;"><?php echo wp_kses( $message, array( 'strong' => array() ) ); ?></p>
			<p style="margin:0;white-space:nowrap;">
				<?php if ( ! empty( $state['asked'] ) ) : ?>
					<a class="button" href="<?php echo esc_url( $rated ); ?>" style="margin-right:6px;">
						<?php esc_html_e( 'Yes, I left a review', 'general-slider' ); ?>
					</a>
				<?php endif; ?>
				<a class="button" href="<?php echo esc_url( $later ); ?>" style="margin-right:6px;">
					<?php esc_html_e( 'Maybe later', 'general-slider' ); ?>
				</a>
				<a class="button button-primary" href="<?php echo esc_url( self::REVIEW_URL ); ?>" target="_blank" rel="noopener noreferrer" onclick="window.location='<?php echo esc_js( $rate ); ?>';return true;">
					<?php esc_html_e( 'Rate it ★★★★★', 'general-slider' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * All the polite conditions in one place.
	 *
	 * @return bool
	 */
	private function should_show() {
		// Only on this plugin's own screens — never anywhere else in the admin.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || Post_Type::SLUG !== $screen->post_type ) {
			return false;
		}

		$state = (array) get_option( self::OPTION, array() );
		if ( ! empty( $state['rated'] ) ) {
			return false;
		}
		if ( ! empty( $state['snooze_until'] ) && time() < (int) $state['snooze_until'] ) {
			return false;
		}
		if ( empty( $state['since'] ) || time() < (int) $state['since'] + self::WAIT_DAYS * DAY_IN_SECONDS ) {
			return false;
		}

		// Only ask people who actually use the plugin: at least one published slider.
		$sliders = get_posts(
			array(
				'post_type'      => Post_Type::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return ! empty( $sliders );
	}
}
