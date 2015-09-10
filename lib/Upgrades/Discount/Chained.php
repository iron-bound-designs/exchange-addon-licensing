<?php
/**
 * Process a chain for an upgrade.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

namespace ITELIC\Upgrade_Paths\Discount;

use ITELIC\Key;

/**
 * Class Chained
 *
 * @package ITELIC\Upgrades\Discount
 */
class Chained extends Discount {

	/**
	 * @var I_Discount[]
	 */
	protected $discounts = array();

	/**
	 * Add a discount to the chain.
	 *
	 * @since 1.0
	 *
	 * @param I_Discount $discount
	 */
	public function chain( I_Discount $discount ) {
		$this->discounts[] = $discount;
	}

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

		if ( empty( $this->discounts ) ) {
			throw new \UnexpectedValueException( "No discounts to pull from." );
		}

		$total = 0.00;

		foreach ( $this->discounts as $discount ) {
			$total += $discount->get_discount();
		}

		return $format ? it_exchange_format_price( $total ) : $total;
	}
}