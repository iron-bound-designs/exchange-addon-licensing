<?php
/**
 * API Renewal Functions
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Get a renewal record.
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return \ITELIC\Renewal
 */
function itelic_get_renewal_record( $id ) {

	/**
	 * Filters the renewal as it is retrieved from the database.
	 *
	 * @since 1.0
	 *
	 * @param \ITELIC\Renewal $renewal
	 */
	return apply_filters( 'itelic_get_renewal', \ITELIC\Renewal::get( $id ) );
}

/**
 * Generate an automatic renewal URL.
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
 * Create a renewal transaction key.
 *
 * @param array $args {
 *
 * @type string $key  The license key to be used. If empty, one will be
 *       generated.
 * @type float  $paid If manually generating a transaction, the amount paid.
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

	if ( ! function_exists( 'it_exchange_register_manual_purchases_addon' ) ) {
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
	$object->total       = number_format( it_exchange_convert_from_database_number( $total ), 2, '.', '' );
	$object->currency    = $currency;
	$object->description = $description;
	$object->products    = $products;

	remove_action( 'it_exchange_add_transaction_success', 'ITELIC\renew_key_on_renewal_purchase' );

	$uniquid  = it_exchange_manual_purchases_addon_transaction_uniqid();
	$txn_args = array();

	if ( isset( $args['date'] ) ) {
		$txn_args['post_date'] = $args['date'];
	}

	$customer = $key->get_customer()->id;

	$tid = it_exchange_add_transaction( 'manual-purchases', $uniquid, 'Completed', $customer, $object, $txn_args );

	add_action( 'it_exchange_add_transaction_success', 'ITELIC\renew_key_on_renewal_purchase' );

	return it_exchange_get_transaction( $tid );
}
