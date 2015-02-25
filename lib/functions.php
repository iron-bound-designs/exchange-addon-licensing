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