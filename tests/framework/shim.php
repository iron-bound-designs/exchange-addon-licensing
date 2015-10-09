<?php
/**
 * Shim functions.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

if ( ! function_exists( 'it_exchange_manual_purchases_addon_transaction_uniqid' ) ):
	function it_exchange_manual_purchases_addon_transaction_uniqid() {
		return uniqid( '', true );
	}
endif;