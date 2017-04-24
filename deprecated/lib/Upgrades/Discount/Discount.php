<?php
/**
 * Base upgrade discount class.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Upgrades\Discount;

use ITELIC\Key;
use ITELIC\Product;

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
	 * @var Product
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
	 * @param Key     $key
	 * @param Product $upgrade_product
	 * @param string  $variant_hash
	 */
	public function __construct( Key $key, Product $upgrade_product, $variant_hash = '' ) {
		$this->key = $key;

		if ( ! $upgrade_product->has_feature( 'licensing' ) ) {
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

		$base_price = $this->get_original_price();

		$upgrade_price = $base_price - $discount;

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

		$base_price = $this->upgrade_product->get_feature( 'base-price' );

		if ( ! empty( $this->variant_hash ) ) {

			$variants = $this->upgrade_product->get_feature( 'base-price', array( 'setting' => 'variants' ) );

			if ( isset( $variants[ $this->variant_hash ] ) ) {
				$base_price = $variants[ $this->variant_hash ]['value'];
			}
		}

		return $format ? it_exchange_format_price( $base_price ) : $base_price;
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