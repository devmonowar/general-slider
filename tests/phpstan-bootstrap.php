<?php
/**
 * PHPStan bootstrap: constants the plugin defines at runtime.
 *
 * @package General_Slider
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- these mirror runtime constants defined elsewhere.

define( 'GENERAL_SLIDER_FILE', __DIR__ . '/../general-slider.php' );
define( 'GENERAL_SLIDER_DIR', __DIR__ . '/../' );
define( 'GENERAL_SLIDER_URL', 'https://example.com/wp-content/plugins/general-slider/' );
define( 'GENERAL_SLIDER_BASENAME', 'general-slider/general-slider.php' );
define( 'GENERAL_SLIDER_VERSION', '0.0.0' );
define( 'GS_DEMO_LIBRARY_URL', 'https://devmonowar.github.io/wp-plugin-demo-library/general-slider/demo-library.json' );
define( 'WP_UNINSTALL_PLUGIN', 'general-slider/general-slider.php' );

// WordPress time constants (defined at runtime in wp-includes/default-constants.php).
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WEEK_IN_SECONDS', 604800 );
define( 'MONTH_IN_SECONDS', 2592000 );
define( 'YEAR_IN_SECONDS', 31536000 );
