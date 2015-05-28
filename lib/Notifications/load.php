<?php
/**
 * Load the notifications module.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Notifications;

use ITELIC\Notifications\Queue\WP_Cron;
use ITELIC\Notifications\Template\Listener;
use ITELIC\Notifications\Queue\Manager as Queue_Manager;

/**
 * Get tags that are shared between managers.
 *
 * @since 1.0
 *
 * @return Listener[]
 */
function get_shared_tags() {
	return array(
		new Listener( 'full_customer_name', function ( \IT_Exchange_Customer $customer ) {
			return $customer->wp_user->first_name . " " . $customer->wp_user->last_name;
		} ),
		new Listener( 'customer_first_name', function ( \IT_Exchange_Customer $customer ) {
			return $customer->wp_user->first_name;
		} ),
		new Listener( 'customer_last_name', function ( \IT_Exchange_Customer $customer ) {
			return $customer->wp_user->last_name;
		} ),
		new Listener( 'customer_email', function ( \IT_Exchange_Customer $customer ) {
			return $customer->wp_user->user_email;
		} ),
		new Listener( 'store_name', function () {
			$settings = it_exchange_get_option( 'settings_general' );

			return $settings['company-name'];
		} )
	);
}

Queue_Manager::register( 'wp-cron', new WP_Cron() );