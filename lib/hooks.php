<?php
/**
 * Main Plugin Hooks
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * When a new transaction is created, generate necessary license keys if applicable.
 *
 * @since 1.0
 *
 * @param int $transaction_id
 */
function itelic_on_add_transaction_generate_license_keys( $transaction_id ) {
	itelic_generate_keys_for_transaction( it_exchange_get_transaction( $transaction_id ) );
}

add_action( 'it_exchange_add_transaction_success', 'itelic_on_add_transaction_generate_license_keys' );

/**
 * Display the license key for a transaction product on the payments detail page.
 *
 * @since 1.0
 *
 * @param WP_Post $post
 * @param array   $transaction_product
 */
function itelic_display_keys_on_transaction_detail( $post, $transaction_product ) {
	$key = itelic_get_key_for_transaction_product( $post->ID, $transaction_product['product_id'] );

	if ( $key === null ) {
		return;
	}

	echo "<h4 class='product-license-key'>";
	printf( __( "License Key: %s", ITELIC::SLUG ), $key->get_key() );
	echo "</h4>";
}

add_action( 'it_exchange_transaction_details_begin_product_details', 'itelic_display_keys_on_transaction_detail', 10, 2 );

/**
 * When a transaction's expiration is updated, renew the key.
 *
 * @since 1.0
 *
 * @param int    $mid
 * @param int    $object_id
 * @param string $meta_key
 * @param mixed  $_meta_value
 */
function itelic_renew_key_on_update_expirations( $mid, $object_id, $meta_key, $_meta_value ) {

	if ( false === strpos( $meta_key, '_it_exchange_transaction_subscription_expires_' ) ) {
		return;
	}

	if ( false === it_exchange_get_transaction( $object_id ) ) {
		return;
	}

	$product_id = (int) str_replace( '_it_exchange_transaction_subscription_expires_', '', $meta_key );

	$data = ITELIC_DB_Keys::search( array(
		'transaction_id' => $object_id,
		'product'        => $product_id
	) );

	if ( empty( $data ) ) {
		return;
	}

	$key = itelic_get_key_from_data( $data[0] );

	$args = array(
		'post_parent' => $key->get_transaction()->ID,
		'post_type'   => 'it_exchange_tran',
		'orderby'     => 'date',
		'order'       => 'DESC'
	);

	$transactions = it_exchange_get_transactions( $args );
	$transaction  = reset( $transactions );

	$key->renew( $transaction );
}

add_action( 'updated_post_meta', 'itelic_renew_key_on_update_expirations', 10, 4 );