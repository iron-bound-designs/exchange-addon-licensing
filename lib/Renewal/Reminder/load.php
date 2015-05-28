<?php
/**
 * Load the renewal reminders scripts.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Renewal\Reminder;

use ITELIC\Key;
use ITELIC\Notifications\Template\Listener;
use ITELIC\Notifications\Template\Manager;
use ITELIC\Renewal\Discount;

new CPT();
new Sender();

/**
 * Register listeners for renewal reminders.
 *
 * @since 1.0
 *
 * @param Manager $manager
 */
function register_listeners( Manager $manager ) {

	$shared = \ITELIC\Notifications\get_shared_tags();

	foreach ( $shared as $listener ) {
		$manager->listen( $listener );
	}

	$manager->listen( new Listener( 'key', function ( Key $key ) {
		return $key->get_key();
	} ) );

	$manager->listen( new Listener( 'key_expiry_date', function ( Key $key ) {
		return $key->get_expires()->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
	} ) );

	$manager->listen( new Listener( 'key_days_from_expiry', function ( Key $key ) {

		$diff = $key->get_expires()->diff( new \DateTime(), true );

		return $diff->days;
	} ) );

	$manager->listen( new Listener( 'product_name', function ( Key $key ) {
		return $key->get_product()->post_title;
	} ) );

	$manager->listen( new Listener( 'transaction_order_number', function ( Key $key ) {
		return it_exchange_get_transaction_order_number( $key->get_transaction() );
	} ) );

	$manager->listen( new Listener( 'discount_amount', function ( Discount $discount ) {
		return $discount->get_amount( true );
	} ) );
}

add_action( 'itelic_notifications_template_manager_renewal-reminder', 'ITELIC\Renewal\Reminder\register_listeners' );