<?php
/**
 * API Renewal Functions
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

/**
 * Get renewals.
 *
 * @api
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return \ITELIC\Renewal[]
 */
function itelic_get_renewals( $args = array() ) {

	$defaults = array(
		'sql_calc_found_rows' => false
	);
	$args     = wp_parse_args( $args, $defaults );

	$query = new \ITELIC\Query\Renewals( $args );

	return $query->get_results();
}

/**
 * Get a renewal record.
 *
 * @api
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return \ITELIC\Renewal
 */
function itelic_get_renewal_record( $id ) {

	$renewal = \ITELIC\Renewal::get( $id );

	/**
	 * Filters the renewal as it is retrieved from the database.
	 *
	 * @since 1.0
	 *
	 * @param \ITELIC\Renewal $renewal
	 */
	$filtered = apply_filters( 'itelic_get_renewal', $renewal );

	if ( $filtered instanceof \ITELIC\Renewal ) {
		$renewal = $filtered;
	}

	return $renewal;
}

/**
 * Generate an automatic renewal URL.
 *
 * @api
 *
 * @since 1.0
 *
 * @param \ITELIC\Key $key
 *
 * @return string
 */
function itelic_generate_auto_renewal_url( \ITELIC\Key $key ) {

	$product_link = get_permalink( $key->get_product()->ID );

	$args = array(
		'renew_key' => $key->get_key()
	);

	return add_query_arg( $args, $product_link );
}

/**
 * Create a renewal record.
 *
 * @api
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return \ITELIC\Renewal|WP_Error
 */
function itelic_create_renewal( $args ) {

	$defaults = array(
		'key'         => '',
		'transaction' => '',
		'expired'     => '',
		'renewal'     => ''
	);

	$args = ITUtility::merge_defaults( $args, $defaults );

	if ( is_string( $args['key'] ) ) {
		$key = itelic_get_key( $args['key'] );
	} else {
		$key = $args['key'];
	}

	if ( ! $key ) {
		return new WP_Error( 'invalid_key', __( "Invalid Key", \ITELIC\Plugin::SLUG ) );
	}

	if ( ! empty( $args['transaction'] ) ) {
		$transaction = it_exchange_get_transaction( $args['transaction'] );

		if ( ! $transaction ) {
			return new WP_Error( 'invalid_transaction', __( "Invalid transaction.", \ITELIC\Plugin::SLUG ) );
		}
	} else {
		$transaction = null;
	}

	$expired = is_string( $args['expired'] ) ? \ITELIC\make_date_time( $args['expired'] ) : $args['expired'];

	if ( ! $expired instanceof DateTime ) {
		return new WP_Error( 'invalid_expiration', __( "Invalid expiration date.", \ITELIC\Plugin::SLUG ) );
	}

	if ( ! empty( $args['renewal'] ) ) {
		$renewal = is_string( $args['renewal'] ) ? \ITELIC\make_date_time( $args['renewal'] ) : $args['renewal'];

		if ( ! $renewal instanceof DateTime ) {
			return new WP_Error( "invalid_renewal", __( "Invalid renewal date.", \ITELIC\Plugin::SLUG ) );
		}

	} else {
		$renewal = null;
	}

	return \ITELIC\Renewal::create( $key, $transaction, $expired, $renewal );
}

/**
 * Create a renewal transaction key.
 *
 * @api
 *
 * @param array $args {
 *
 * @type string $key  The license key to be used. If empty, one will be
 *       generated.
 * @type float  $paid If manually generating a transaction, the amount paid.
 * @tpye string $date When the transaction occurred. GMT.
 * }
 *
 * @return IT_Exchange_Transaction
 */
function itelic_create_renewal_transaction( $args ) {

	$defaults = array(
		'key'  => '',
		'paid' => '',
		'date' => ''
	);

	$args = ITUtility::merge_defaults( $args, $defaults );

	$key = itelic_get_key( $args['key'] );

	if ( ! $key ) {
		return new WP_Error( 'invalid_key', __( "Invalid key", \ITELIC\Plugin::SLUG ) );
	}

	$product = $key->get_product();

	if ( ! function_exists( 'it_exchange_manual_purchases_addon_transaction_uniqid' ) ) {
		return new WP_Error( 'no_manual_purchases',
			__( "Manual purchases add-on is not installed.", \ITELIC\Plugin::SLUG ) );
	}

	// Grab default currency
	$settings    = it_exchange_get_option( 'settings_general' );
	$currency    = $settings['default-currency'];
	$description = array();

	$product_id = $product->ID;

	$itemized_data = apply_filters( 'it_exchange_add_itemized_data_to_cart_product', array(), $product_id );
	if ( ! is_serialized( $itemized_data ) ) {
		$itemized_data = maybe_serialize( $itemized_data );
	}
	$i = $product_id . '-' . md5( $itemized_data );

	$discounted = new \ITELIC\Renewal\Discount( $key );
	$discounted = $discounted->get_discount_price();

	$products[ $i ]['product_base_price'] = $discounted;
	$products[ $i ]['product_subtotal']   = $products[ $i ]['product_base_price']; //need to add count
	$products[ $i ]['product_name']       = get_the_title( $product_id );
	$products[ $i ]['product_id']         = $product_id;
	$products[ $i ]['count']              = 1;
	$description[]                        = $products[ $i ]['product_name'];

	$description = apply_filters( 'it_exchange_get_cart_description', join( ', ', $description ), $description );

	// Package it up and send it to the transaction method add-on
	$total = empty( $args['paid'] ) ? $discounted : it_exchange_convert_to_database_number( $args['paid'] );

	$object              = new stdClass();
	$object->cart_id     = it_exchange_create_cart_id();
	$object->total       = number_format( it_exchange_convert_from_database_number( $total ), 2, '.', '' );
	$object->currency    = $currency;
	$object->description = $description;
	$object->products    = $products;

	remove_action( 'it_exchange_add_transaction_success', 'ITELIC\renew_key_on_renewal_purchase' );

	$uniquid  = it_exchange_manual_purchases_addon_transaction_uniqid();
	$txn_args = array();

	if ( isset( $args['date'] ) ) {

		$date = \ITELIC\make_date_time( $args['date'] );

		$txn_args['post_date']     = \ITELIC\convert_gmt_to_local( $date )->format( 'Y-m-d H:i:s' );
		$txn_args['post_date_gmt'] = $date->format( 'Y-m-d H:i:s' );
	}

	$customer = $key->get_customer()->id;

	$tid = it_exchange_add_transaction( 'manual-purchases', $uniquid, 'Completed', $customer, $object, $txn_args );

	add_action( 'it_exchange_add_transaction_success', 'ITELIC\renew_key_on_renewal_purchase' );

	return it_exchange_get_transaction( $tid );
}
