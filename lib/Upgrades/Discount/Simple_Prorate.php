<?php
/**
 * Simple proration discount.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

namespace ITELIC\Upgrades\Discount;

/**
 * Class Simple_Prorate
 *
 * @package ITELIC\Upgrades\Discount
 */
class Simple_Prorate extends Discount {

	/**
	 * Get the total amount of the upgrade discount.
	 *
	 * @since 1.0
	 *
	 * @param bool $format
	 *
	 * @return float|string
	 */
	public function get_discount( $format = false ) {

		$transaction = $this->key->get_transaction();

		foreach ( it_exchange_get_transaction_products( $transaction ) as $product ) {

			if ( $product['product_id'] == $this->key->get_product()->ID ) {
				return $product['product_base_price'];
			}
		}

		return 0.00;
	}
}