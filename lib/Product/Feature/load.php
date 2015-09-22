<?php
/**
 * Load the product features.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Product\Feature;

new Base();
new Discount();
new Readme();

if ( function_exists( 'it_exchange_register_variants_addon' ) ) {
	new Upgrades();
}