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

			$product  = it_exchange_get_product( $product['product_id'] );
			$customer = it_exchange_get_transaction_customer( $transaction );

			$factory = new ITELIC_Key_Factory( $product, $customer, $transaction );
			$key     = $factory->make();

			$max = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'limit' ) );

			ITELIC_Key::create( $key, $transaction, $product, $customer, $max, $status );

			$result = true;
		}
	}

	return $result;
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