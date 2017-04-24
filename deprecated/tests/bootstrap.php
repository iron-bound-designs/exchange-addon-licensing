<?php
/**
 * Bootstrap Unit Tests
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Determine where the WP test suite lives.
 *
 * Support for:
 * 1. `WP_DEVELOP_DIR` environment variable, which points to a checkout
 *   of the develop.svn.wordpress.org repository (this is recommended)
 * 2. `WP_TESTS_DIR` environment variable, which points to a checkout
 * 3. `WP_ROOT_DIR` environment variable, which points to a checkout
 * 4. Plugin installed inside of WordPress.org developer checkout
 * 5. Tests checked out to /tmp
 */
if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} else if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$test_root = getenv( 'WP_TESTS_DIR' );
} else if ( false !== getenv( 'WP_ROOT_DIR' ) ) {
	$test_root = getenv( 'WP_ROOT_DIR' ) . '/tests/phpunit';
} else if ( file_exists( '../../../../tests/phpunit/includes/bootstrap.php' ) ) {
	$test_root = '../../../../tests/phpunit';
} else if ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
	$test_root = '/tmp/wordpress-tests-lib';
}

define( 'DOING_TESTS', true );

require_once $test_root . '/includes/functions.php';

require_once dirname( __FILE__ ) . '/../vendor/antecedent/patchwork/Patchwork.php';

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

if ( ! defined( 'COOKIEHASH' ) ) {
	define( 'COOKIEHASH', md5( uniqid() ) );
}

function _manually_load_plugin() {
	require_once dirname( __FILE__ ) . '/../exchange-addon-licensing.php';

	if ( ! function_exists( 'load_it_exchange' ) ) {
		require_once dirname( __FILE__ ) . '/../../ithemes-exchange/init.php';
	}
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $test_root . '/includes/bootstrap.php';

require dirname( __FILE__ ) . '/framework/shim.php';
require dirname( __FILE__ ) . '/framework/product-factory.php';
require dirname( __FILE__ ) . '/framework/key-factory.php';
require dirname( __FILE__ ) . '/framework/activation-factory.php';
require dirname( __FILE__ ) . '/framework/release-factory.php';
require dirname( __FILE__ ) . '/framework/update-factory.php';
require dirname( __FILE__ ) . '/framework/test-case.php';

if ( ! function_exists( 'load_it_exchange' ) ) {
	activate_plugin( 'ithemes-exchange/init.php' );
}

activate_plugin( 'exchange-addon-licensing/exchange-addon-licensing.php' );

\WP_Mock::setUsePatchwork( true );
\WP_Mock::bootstrap();