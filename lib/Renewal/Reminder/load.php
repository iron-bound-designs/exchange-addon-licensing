<?php
/**
 * Load the renewal reminders scripts.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Renewal\Reminder;

use ITELIC\Key;
use IronBound\WP_Notifications\Template\Listener;
use IronBound\WP_Notifications\Template\Manager;
use ITELIC\Renewal\Discount;

$cpt = new CPT();
$cpt->add_hooks();

new Sender();

/**
 * Register listeners for renewal reminders.
 *
 * @since 1.0
 *
 * @param Manager $manager
 */
function register_listeners( Manager $manager ) {

	$shared = \ITELIC\get_shared_tags();

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

	$manager->listen( new Listener( 'renewal_link', function ( Key $key ) {

		if ( $key->is_renewable() ) {
			return itelic_generate_auto_renewal_url( $key );
		} else {
			return get_permalink( $key->get_product()->ID );
		}
	} ) );

	$manager->listen( new Listener( 'discount_amount', function ( Discount $discount ) {
		return $discount->get_amount( true );
	} ) );
}

add_action( 'ibd_wp_notifications_template_manager_itelic-renewal-reminder', 'ITELIC\Renewal\Reminder\register_listeners' );