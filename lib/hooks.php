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