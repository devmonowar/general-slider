<?php
/**
 * Dynamic slides provider: turns a WP_Query into slides.
 *
 * A "dynamic" slider does not store slides — it maps published posts (or any
 * public post type, including WooCommerce products) onto the very same slide
 * structure a manual slider uses, so every preset, skin and effect works for
 * free. Results are cached in a transient and refreshed whenever content
 * changes.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Builds and caches slides from post content.
 */
class Dynamic_Slides {

	/** Option holding a cache-busting version, bumped whenever content changes. */
	const CACHE_VERSION_OPTION = 'general_slider_dynamic_cache_v';

	/** Cached-slides transient lifetime (a safety net; content edits bust it sooner). */
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * Register cache-invalidation hooks.
	 */
	public function hooks() {
		add_action( 'save_post', array( __CLASS__, 'bump_cache_version' ) );
		add_action( 'deleted_post', array( __CLASS__, 'bump_cache_version' ) );
		add_action( 'trashed_post', array( __CLASS__, 'bump_cache_version' ) );
	}

	/**
	 * Bump the cache version so every cached dynamic slider rebuilds.
	 *
	 * Cheap and blunt: rather than tracking which slider queries which post,
	 * we invalidate all dynamic caches on any real content change. Revisions
	 * and autosaves are skipped so they don't churn the cache needlessly.
	 *
	 * @param int $post_id The post being saved/deleted/trashed.
	 */
	public static function bump_cache_version( $post_id = 0 ) {
		if ( $post_id && ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) ) {
			return;
		}
		$v = (int) get_option( self::CACHE_VERSION_OPTION, 0 );
		update_option( self::CACHE_VERSION_OPTION, $v + 1, false );
	}

	/**
	 * Post types a dynamic slider may pull from: public types with an
	 * archive-like nature, minus attachments and the slider type itself.
	 *
	 * @return array<string,string> post type => label.
	 */
	public static function available_post_types() {
		$types  = get_post_types( array( 'public' => true ), 'objects' );
		$result = array();
		foreach ( $types as $type ) {
			if ( in_array( $type->name, array( 'attachment', Post_Type::SLUG ), true ) ) {
				continue;
			}
			$result[ $type->name ] = $type->labels->name;
		}
		return $result;
	}

	/**
	 * Build the post-type / taxonomy / term map the editor UI needs to power
	 * its cascading dropdowns, without an AJAX round-trip. Terms are capped to
	 * keep the payload small on large sites.
	 *
	 * @return array<string,array>
	 */
	public static function ui_data() {
		$map = array();
		foreach ( self::available_post_types() as $type => $label ) {
			$taxes = array();
			foreach ( get_object_taxonomies( $type, 'objects' ) as $tax ) {
				if ( ! $tax->public || ! $tax->show_ui ) {
					continue;
				}
				$terms = get_terms(
					array(
						'taxonomy'   => $tax->name,
						'hide_empty' => false,
						'number'     => 200,
						'orderby'    => 'name',
					)
				);
				if ( is_wp_error( $terms ) || empty( $terms ) ) {
					continue;
				}
				$term_list = array();
				foreach ( $terms as $term ) {
					$term_list[] = array(
						'id'   => (int) $term->term_id,
						'name' => $term->name,
					);
				}
				$taxes[] = array(
					'tax'   => $tax->name,
					'label' => $tax->labels->singular_name,
					'terms' => $term_list,
				);
			}
			$map[ $type ] = array(
				'label'      => $label,
				'taxonomies' => $taxes,
			);
		}
		return $map;
	}

	/**
	 * Get the resolved slides for a dynamic slider (cached).
	 *
	 * @param int   $post_id Slider ID.
	 * @param array $source  Sanitised source config.
	 * @return array<int,array> Normalised slides.
	 */
	public static function get( $post_id, $source ) {
		$key = self::cache_key( $post_id, $source );

		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$slides = self::build( $post_id, $source );
		set_transient( $key, $slides, self::CACHE_TTL );
		return $slides;
	}

	/**
	 * Transient key for a slider + its source config + the content version.
	 *
	 * @param int   $post_id Slider ID.
	 * @param array $source  Source config.
	 * @return string
	 */
	private static function cache_key( $post_id, $source ) {
		$version = (int) get_option( self::CACHE_VERSION_OPTION, 0 );
		return 'gs_dyn_' . $post_id . '_' . substr( md5( wp_json_encode( $source ) . '|' . $version ), 0, 12 );
	}

	/**
	 * Run the query and map each post onto a slide.
	 *
	 * @param int   $post_id Slider ID.
	 * @param array $source  Source config.
	 * @return array<int,array>
	 */
	private static function build( $post_id, $source ) {
		$post_type = ( '' !== ( $source['post_type'] ?? '' ) ) ? $source['post_type'] : 'post';
		if ( ! post_type_exists( $post_type ) || ! array_key_exists( $post_type, self::available_post_types() ) ) {
			$post_type = 'post';
		}

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => min( 20, max( 1, absint( $source['count'] ?? 6 ) ) ),
			'orderby'             => array_key_exists( ( $source['orderby'] ?? '' ), Data::source_orderbys() ) ? $source['orderby'] : 'date',
			'order'               => 'ASC' === strtoupper( (string) ( $source['order'] ?? '' ) ) ? 'ASC' : 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( ! empty( $source['taxonomy'] ) && ! empty( $source['term'] ) && taxonomy_exists( $source['taxonomy'] ) ) {
			// One taxonomy and one term_id — no slug lookup — over at most 20 posts
			// with no_found_rows, and get() caches the whole result for six hours,
			// so this runs on a cache miss only.
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- bounded and cached, see above.
				array(
					'taxonomy' => $source['taxonomy'],
					'field'    => 'term_id',
					'terms'    => absint( $source['term'] ),
				),
			);
		}

		/**
		 * Filter the WP_Query args for a dynamic slider.
		 *
		 * @param array $args    Query args.
		 * @param int   $post_id Slider ID.
		 * @param array $source  Source config.
		 */
		$args  = apply_filters( 'general_slider_dynamic_query_args', $args, $post_id, $source );
		$query = new \WP_Query( $args );

		$show_excerpt = ! empty( $source['show_excerpt'] );
		$words        = min( 100, max( 0, absint( $source['excerpt_words'] ?? 20 ) ) );
		$button_text  = sanitize_text_field( $source['button_text'] ?? '' );

		// Preserve whatever post is current so we can restore it afterwards —
		// wp_reset_postdata() alone can't in contexts without a main query.
		$original_post = $GLOBALS['post'] ?? null;

		$slides = array();
		foreach ( $query->posts as $post ) {
			$permalink = (string) get_permalink( $post );

			$text = '';
			if ( $show_excerpt && $words > 0 ) {
				// Set up the loop context so auto-generated excerpts (and any
				// the_content / excerpt filters) behave as they do on the post.
				$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $post );
				$text = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), $words, '&hellip;' );
			}

			$slide = array(
				'image_id'    => (int) get_post_thumbnail_id( $post ),
				'video'       => '',
				'sub_heading' => '',
				'heading'     => get_the_title( $post ),
				'text'        => $text,
				'btn_text'    => $button_text,
				'btn_url'     => $permalink,
				'link'        => $permalink,
				'new_tab'     => false,
			);

			/**
			 * Filter a single dynamic slide before it is normalised.
			 *
			 * @param array    $slide  Slide data.
			 * @param \WP_Post $post   Source post.
			 * @param array    $source Source config.
			 */
			$slide = apply_filters( 'general_slider_dynamic_slide', $slide, $post, $source );

			$normalised = Data::normalise_slide( $slide );
			if ( $normalised ) {
				$slides[] = $normalised;
			}
		}
		wp_reset_postdata();
		$GLOBALS['post'] = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $slides;
	}
}
