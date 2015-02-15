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