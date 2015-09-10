<?php
/**
 * Base upgrade discount class.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

namespace ITELIC\Upgrade_Paths\Discount;

use ITELIC\Key;

/**
 * Class Discount
 *
 * @package ITELIC\Upgrades\Discount
 */
abstract class Discount implements I_Discount {

	/**
	 * @var Key
	 */
	protected $key;

	/**
	 * @var \IT_Exchange_Product
	 */
	protected $upgrade_product;

	/**
	 * @var string
	 */
	protected $variant_hash;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Key                  $key
	 * @param \IT_Exchange_Product $upgrade_product
	 * @param string               $variant_hash
	 */
	public function __construct( Key $key, \IT_Exchange_Product $upgrade_product, $variant_hash = '' ) {
		$this->key = $key;

		if ( ! it_exchange_product_has_feature( $upgrade_product->ID, 'licensing' ) ) {
			throw new \InvalidArgumentException( "Upgrade product must have licensing enabled." );
		}

		$this->upgrade_product = $upgrade_product;
		$this->variant_hash    = $variant_hash;
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

		$discount = $this->get_discount();

		$base_price = it_exchange_get_product_feature( $this->upgrade_product->ID, 'base-price' );

		if ( ! empty( $this->variant_hash ) ) {

			$variants = it_exchange_get_product_feature( $this->upgrade_product->ID, 'base-price', array( 'setting' => 'variants' ) );

			if ( isset( $variants[ $this->variant_hash ] ) ) {
				$base_price = $variants[ $this->variant_hash ]['value'];
			}
		}

		$upgrade_price = $base_price - $discount;

		return $format ? it_exchange_format_price( $upgrade_price ) : $upgrade_price;
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