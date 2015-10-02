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
	require dirname( __FILE__ ) . '/../exchange-addon-licensing.php';
	require dirname( __FILE__ ) . '/../../ithemes-exchange/init.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

//activate_plugin( 'ithemes-exchange/init.php' );
activate_plugin( 'exchange-addon-licensing/exchange-addon-licensing.php' );
