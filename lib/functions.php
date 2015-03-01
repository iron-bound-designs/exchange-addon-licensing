<?php
/**
 * Main Plugin Functions
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Generate license keys for a transaction.
 *
 * @since 1.0
 *
 * @param IT_Exchange_Transaction $transaction
 * @param string                  $status Default ITELIC_Key::ACTIVE
 *
 * @return boolean
 */
function itelic_generate_keys_for_transaction( IT_Exchange_Transaction $transaction, $status = '' ) {

	$result = false;

	foreach ( $transaction->get_products() as $product ) {

		if ( isset( $product['renewed_key'] ) && $product['renewed_key'] ) {
			continue; // this is a renewal purchase we shouldn't generate keys here.
		}

		if ( it_exchange_product_has_feature( $product['product_id'], 'licensing' ) ) {

			$product = it_exchange_get_product( $product['product_id'] );

			if ( itelic_generate_key_for_transaction_product( $transaction, $product, $status ) ) {
				$result = true;
			} else {
				$result = false;
			}
		}
	}

	return $result;
}

/**
 * Generate a key for a certain transaction product.
 *
 * @since 1.0
 *
 * @param IT_Exchange_Transaction $transaction
 * @param IT_Exchange_Product     $product
 * @param string                  $status
 *
 * @return ITELIC_Key
 */
function itelic_generate_key_for_transaction_product( IT_Exchange_Transaction $transaction, IT_Exchange_Product $product, $status = '' ) {

	$customer = it_exchange_get_transaction_customer( $transaction );

	$factory = new ITELIC_Key_Factory( $product, $customer, $transaction );
	$key     = $factory->make();

	$max = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'limit' ) );

	if ( ! it_exchange_product_has_feature( $product->ID, 'recurring-payments' ) ) {
		$expires = null;
	} else {

		$type  = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'interval' ) );
		$count = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'interval-count' ) );

		$interval = itelic_convert_rp_to_date_interval( $type, $count );

		$expires = new DateTime( 'now', new DateTimeZone( get_option( 'timezone_string' ) ) );
		$expires->add( $interval );
	}

	ITELIC_Key::create( $key, $transaction, $product, $customer, $max, $expires, $status );
}

/**
 * Convert a recurring payments interval to a DateInterval object.
 *
 * @since 1.0
 *
 * @param string $type
 * @param int    $count
 *
 * @return DateInterval
 *
 * @throws Exception if invalid interval spec.
 */
function itelic_convert_rp_to_date_interval( $type, $count ) {

	$count = absint( $count );

	switch ( $type ) {
		case 'day':
			$period_designator = 'D';
			break;
		case 'week':
			$period_designator = 'W';
			break;
		case 'month':
			$period_designator = 'M';
			break;
		case 'year':
			$period_designator = 'Y';
			break;
	}

	if ( isset( $period_designator ) ) {
		$interval_spec = $count . $period_designator;
	} else {

		/**
		 * Filters the interval spec if the period designator is unknown.
		 *
		 * @see   http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
		 *
		 * @since 1.0
		 *
		 * @param string|null $interval_spec Conforms to PHP date interval_spec without the P prefix.
		 * @param string      $type          Raw recurring payments type.
		 * @param int         $count         Recurrence count.
		 */
		$interval_spec = apply_filters( 'itelic_convert_rp_to_date_interval_unknown_designator', null, $type, $count );
	}

	return new DateInterval( "P$interval_spec" );
}

/**
 * Get the license key for a particular transaction product.
 *
 * @since 1.0
 *
 * @param int $transaction_id
 * @param int $product_id
 *
 * @return ITELIC_Key
 */
function itelic_get_key_for_transaction_product( $transaction_id, $product_id ) {
	$data = ITELIC_DB_Keys::search( array(
		'transaction_id' => absint( $transaction_id ),
		'product'        => absint( $product_id )
	) );

	if ( empty( $data ) ) {
		return null;
	}

	return new ITELIC_Key( reset( $data ) );
}

/**
 * Checks if the renew product purchase requirement has been met.
 *
 * @since 1.0
 *
 * @return boolean
 */
function itelic_purchase_requirement_renew_product() {

	$customer   = it_exchange_get_current_customer_id();
	$product_id = itelic_get_current_product_id();

	if ( ! it_exchange_product_has_feature( $product_id, 'licensing' ) ) {
		return true;
	}

	$keys = itelic_get_keys( array(
		'customer' => $customer,
		'product'  => $product_id
	) );


	if ( empty( $keys ) ) {
		return true;
	}

	// we only want to prompt for a renewal, if there is a key that exists that has an expiry date.
	// because we can't renew keys with no expiry date.
	$one_with_expiry = false;

	foreach ( $keys as $key ) {
		if ( $key->get_expires() !== null ) {
			$one_with_expiry = true;
		}
	}

	if ( $one_with_expiry === false ) {
		return true;
	}

	$session = itelic_get_purchase_requirement_renew_product_session();

	return $session['renew'] !== null;
}

/**
 * Get the purchase requirement session.
 *
 * @since 1.0
 *
 * @return array
 */
function itelic_get_purchase_requirement_renew_product_session() {

	$session = (array) it_exchange_get_session_data( 'itelic_renew_product' );

	$defaults = array(
		'renew'   => null,
		'product' => null
	);

	return ITUtility::merge_defaults( $session, $defaults );
}

/**
 * Update the purchase requirement session.
 *
 * @since 1.0
 *
 * @param array $data
 */
function itelic_update_purchase_requirement_renew_product_session( array $data ) {
	it_exchange_update_session_data( 'itelic_renew_product', $data );
}

/**
 * Clear the session data.
 *
 * @since 1.0
 */
function itelic_clear_purchase_requirement_renew_product_session() {
	it_exchange_clear_session_data( 'itelic_renew_product' );
}

/**
 * Save the purchase requirement renewal key to the user's session.
 *
 * @since 1.0
 *
 * @param string|bool $key If false, user opted to purchase a new key.
 * @param int         $product
 */
function itelic_set_purchase_requirement_renewal_key( $key, $product ) {

	$session = itelic_get_purchase_requirement_renew_product_session();

	if ( $session['renew'] === null ) {
		$session['renew']   = $key;
		$session['product'] = absint( $product );
	}

	itelic_update_purchase_requirement_renew_product_session( $session );
}

/**
 * Exchange isn't very consistent in getting access to the current product.
 *
 * This function tries to abstract that away and provide all the possible means of getting the product.
 *
 * @return int
 */
function itelic_get_current_product_id() {
	if ( isset( $GLOBALS['it_exchange']['product'] ) ) {
		return $GLOBALS['it_exchange']['product']->ID;
	} elseif ( isset( $GLOBALS['post'] ) ) {
		return $GLOBALS['post']->ID;
	}

	return 0;
}