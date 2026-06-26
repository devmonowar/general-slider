<?php
/**
 * Main plugin orchestrator.
 *
 * @package General_Slider
 */

namespace GeneralSlider;

defined( 'ABSPATH' ) || exit;

/**
 * Wires up every component of the plugin. Single instance.
 */
final class Plugin {

	/**
	 * Single instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the shared instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: boot components. Translations for WordPress.org-hosted
	 * plugins load automatically (since WP 4.6), so no manual loading is needed.
	 */
	private function __construct() {
		$this->boot();
	}

	/**
	 * Instantiate the components and register their hooks.
	 */
	private function boot() {
		( new Post_Type() )->hooks();
		( new Taxonomy() )->hooks();
		( new Slides_Meta() )->hooks();
		( new Settings() )->hooks();
		( new Assets() )->hooks();
		( new Shortcode() )->hooks();
		( new Block() )->hooks();
		( new Demo_Importer() )->hooks();
		( new Demo_Library() )->hooks();
		( new Demo_Export() )->hooks();
		( new Duplicator() )->hooks();
		( new Tools() )->hooks();
		( new Elementor() )->hooks();
	}
}
