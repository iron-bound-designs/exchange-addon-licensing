<?php
/**
 * API Methods for interacting with keys.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Get license keys.
 *
 * @since 1.0
 *
 * @param array $args
 *
 * @return \ITELIC\Key[]
 */
function itelic_get_keys( $args = array() ) {

	$query = new \ITELIC_API\Query\Keys( $args );

	return $query->get_results();
}

/**
 * Get a key.
 *
 * @since 1.0
 *
 * @param string $key
 *
 * @return \ITELIC\Key
 */
function itelic_get_key( $key ) {

	/**
	 * Filters the key as it is retrieved from the database.
	 *
	 * @since 1.0
	 *
	 * @param \ITELIC\Key $key
	 */
	return apply_filters( 'itelic_get_key', \ITELIC\Key::get( $key ) );
}

/**
 * Get a key from data pulled from the DB.
 *
 * @since 1.0
 *
 * @param stdClass $data
 *
 * @return \ITELIC\Key
 */
function itelic_get_key_from_data( stdClass $data ) {
	return new \ITELIC\Key( $data );
}

/**
 * Get the admin edit link for a particular key.
 *
 * @since 1.0
 *
 * @param string $key
 *
 * @return string
 */
function itelic_get_admin_edit_key_link( $key ) {
	return add_query_arg( array(
		'view' => 'single',
		'key'  => (string) $key,
	), \ITELIC\Admin\Tab\Dispatch::get_tab_link( 'licenses' ) );
}

/**
 * Create a license key.
 *
 * @param array $args        {
 *
 * @type string $key         The license key to be used. If empty, one will be
 *       generated.
 * @type int    $transaction Transaction ID. If empty, one will be manually
 *       generated
 * @type int    $product     Product ID.
 * @type int    $customer    Customer ID.
 * @type string $status      The key's status. Accepts 'active', 'expired',
 *       'disabled'
 * @type float  $paid        If manually generating a transaction, the amount
 *       paid.
 * @type int    $limit       Activation limit.
 * @type string $expires     Expiration date. Pass null or empty string for
 *       forever.
 * }
 *
 * @return \ITELIC\Key|null
 */
function itelic_create_key( $args ) {

	$defaults = array(
		'key'         => '',
		'transaction' => '',
		'product'     => '',
		'customer'    => '',
		'status'      => '',
		'paid'        => ''
	);

	$args = ITUtility::merge_defaults( $args, $defaults );

	if ( ! $args['customer'] ) {
		return null;
	}

	$product = itelic_get_product( $args['product'] );

	if ( ! it_exchange_product_has_feature( $product->ID, 'licensing' ) ) {
		return null;
	}

	$customer = it_exchange_get_customer( $args['customer'] );

	if ( ! $customer ) {
		return null;
	}

	$transaction = it_exchange_get_transaction( $args['transaction'] );

	if ( ! $args['transaction'] ) {

		if ( ! function_exists( 'it_exchange_register_manual_purchases_addon' ) ) {
			return null;
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
		$key = $product_id . '-' . md5( $itemized_data );

		$products[ $key ]['product_base_price'] = it_exchange_get_product_feature( $product_id, 'base-price' );
		$products[ $key ]['product_subtotal']   = $products[ $key ]['product_base_price']; //need to add count
		$products[ $key ]['product_name']       = get_the_title( $product_id );
		$products[ $key ]['product_id']         = $product_id;
		$products[ $key ]['count']              = 1;
		$description[]                          = $products[ $key ]['product_name'];

		$description = apply_filters( 'it_exchange_get_cart_description', join( ', ', $description ), $description );

		// Package it up and send it to the transaction method add-on
		$total = empty( $args['paid'] ) ? 0 : it_exchange_convert_to_database_number( $args['paid'] );

		$object              = new stdClass();
		$object->total       = number_format( it_exchange_convert_from_database_number( $total ), 2, '.', '' );
		$object->currency    = $currency;
		$object->description = $description;
		$object->products    = $products;

		remove_action( 'it_exchange_add_transaction_success', 'ITELIC\on_add_transaction_generate_license_keys' );
		$transaction_id = it_exchange_manual_purchases_addon_process_transaction( $customer->id, $object );
		add_action( 'it_exchange_add_transaction_success', 'ITELIC\on_add_transaction_generate_license_keys' );

		$transaction = it_exchange_get_transaction( $transaction_id );
	}

	$key = \ITELIC\generate_key_for_transaction_product( $transaction, $product, $args['status'], $args['key'] );

	if ( isset( $args['limit'] ) ) {

		if ( empty( $args['limit'] ) || $args['limit'] == '-' ) {
			$limit = '';
		} else {
			$limit = $args['limit'];
		}

		$key->set_max( $limit );
	}

	if ( isset( $args['expires'] ) ) {
		if ( empty( $args['expires'] ) ) {
			$expires = null;
		} else {
			$expires = new DateTime( $args['expires'] );
		}

		$key->set_expires( $expires );
	}

	return $key;
}
