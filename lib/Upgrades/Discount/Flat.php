<?php
/**
 * Flat upgrade discount.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Upgrades\Discount;

use ITELIC\Key;

/**
 * Class Flat
 *
 * @package ITELIC\Upgrades\Discount
 */
class Flat extends Discount {

	/**
	 * @var float
	 */
	protected $amount;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param float                $amount
	 * @param Key                  $key
	 * @param \IT_Exchange_Product $upgrade_product
	 * @param string               $variant_hash
	 */
	public function __construct( $amount, Key $key, \IT_Exchange_Product $upgrade_product, $variant_hash = '' ) {
		parent::__construct( $key, $upgrade_product, $variant_hash );

		$this->amount = $amount;
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
		return $format ? it_exchange_format_price( $this->amount ) : $this->amount;
	}
}