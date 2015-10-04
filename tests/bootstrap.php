<?php
/**
 * Bootstrap Unit Tests
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}
require_once $_tests_dir . '/includes/functions.php';

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

if ( ! defined( 'COOKIEHASH' ) ) {
	define( 'COOKIEHASH', md5( uniqid() ) );
}

function _manually_load_plugin() {
	require_once dirname( __FILE__ ) . '/../exchange-addon-licensing.php';

	if ( ! function_exists( 'load_it_exchange' ) ) {
		require_once dirname( __FILE__ ) . '/../../ithemes-exchange/init.php';
	}

	require_once dirname( __FILE__ ) . '/../../exchange-addon-recurring-payments/exchange-addon-recurring-payments.php';
	require_once dirname( __FILE__ ) . '/../../exchange-addon-manual-purchases/exchange-addon-manual-purchases.php';

	// exchange pulls from a saved option to determine the current version
	// which isn't yet saved when running unit tests
	require_once dirname( __FILE__ ) . '/../../exchange-addon-manual-purchases/init.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

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
activate_plugin( 'exchange-addon-manual-purchases/exchange-addon-manual-purchases.php' );
activate_plugin( 'exchange-addon-recurring-payments/exchange-addon-recurring-payments.php' );
