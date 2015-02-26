<?php
/**
 * Main init file.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Load the DBs
 */
require_once( ITELIC::$dir . 'lib/db/load.php' );

/**
 * Load key types API methods.
 */
require_once( ITELIC::$dir . 'api/key-types.php' );

/**
 * Load keys API methods.
 */
require_once( ITELIC::$dir . 'api/keys.php' );

/**
 * Load the main plugin functions.
 */
require_once( ITELIC::$dir . 'lib/functions.php' );

/**
 * Load the main plugin hooks
 */
require_once( ITELIC::$dir . 'lib/hooks.php' );

/**
 * Load the plugin settings page.
 */
require_once( ITELIC::$dir . 'lib/settings.php' );

/**
 * Load the key types.
 */
require_once( ITELIC::$dir . 'lib/key/load.php' );

/**
 * Load the product features.
 */
require_once( ITELIC::$dir . 'lib/product/feature/load.php' );

/**
 * Load the admin.
 */
require_once( ITELIC::$dir . 'lib/admin/load.php' );

/**
 * Run the upgrade routine if necessary.
 */
ITELIC::upgrade();