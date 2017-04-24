<?php
/*
Plugin Name: iThemes Exchange - Licensing Add-on
Plugin URI: https://ironbounddesigns.com/product/licensing/
Description: Sell and manage license keys for your software.
Version: 2.0.0
Author: Iron Bound Designs
Author URI: https://ironbounddesigns.com
License: AGPL
Text Domain: ibd-exchange-addon-licensing
Domain Path: /lang
*/

namespace ITELIC;

/**
 * Load the licensing plugin.
 *
 * @since 2.0.0
 */
function load_addon() {
	if ( ! function_exists( 'it_exchange_load_deprecated' ) || it_exchange_load_deprecated() ) {
		require_once dirname( __FILE__ ) . '/deprecated/exchange-addon-licensing.php';
	} else {
		require_once dirname( __FILE__ ) . '/plugin.php';
	}
}

add_action( 'plugins_loaded', 'ITELIC\load_addon' );

/**
 * On activation, set a time, frequency and name of an action hook to be scheduled.
 *
 * @since 1.0
 */
function activation() {
	wp_schedule_event( strtotime( 'Tomorrow 4AM' ), 'daily', 'it_exchange_itelic_daily_schedule' );
}

register_activation_hook( __FILE__, 'ITELIC\activation' );

/**
 * On deactivation, remove all functions from the scheduled action hook.
 *
 * @since 1.0
 */
function deactivation() {
	wp_clear_scheduled_hook( 'it_exchange_itelic_daily_schedule' );
}

register_deactivation_hook( __FILE__, 'ITELIC\deactivation' );