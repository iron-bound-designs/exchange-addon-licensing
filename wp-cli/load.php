<?php
/**
 * Load WP-CLI commands
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

include_once __DIR__ . '/Fetcher.php';

// commands
include_once __DIR__ . '/commands/ITELIC.php';
include_once __DIR__ . '/commands/Key.php';
include_once __DIR__ . '/commands/Activation.php';