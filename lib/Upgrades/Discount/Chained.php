<?php
/**
 * Process a chain for an upgrade.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

namespace ITELIC\Upgrades\Discount;

use ITELIC\Key;

/**
 * Class Chained
 *
 * @package ITELIC\Upgrades\Discount
 */
class Chained implements I_Discount {

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


	/**
	 * Get the price a customer can upgrade to for.
	 *
	 * @since 1.0
	 *
	 * @param bool $format
	 *
	 * @return float|string
	 */
	public function get_upgrade_price( $format = false ) {

		$upgrade_price = $this->get_original_price() - $this->get_discount();

		return $format ? it_exchange_format_price( $upgrade_price ) : $upgrade_price;
	}

	/**
	 * Get the original price before any discounts are applied.
	 *
	 * @since 1.0
	 *
	 * @param bool $format
	 *
	 * @return float|string
	 */
	public function get_original_price( $format = false ) {

		/**
		 * @var $first I_Discount
		 */
		$first = reset( $this->discounts );

		return $first->get_original_price( $format );
	}

	/**
	 * Get the upgrade price formatted.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->get_upgrade_price( true );
	}
}