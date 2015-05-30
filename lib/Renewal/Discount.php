<?php
/**
 * Class Representing Renewal Discounts
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Renewal;

use ITELIC\Key;

/**
 * Class Discount
 * @package ITELIC\Renewal
 */
class Discount implements \Serializable {

	/**
	 * @var string
	 */
	const TYPE_FLAT = 'flat';

	/**
	 * @var string
	 */
	const TYPE_PERCENT = 'percent';

	/**
	 * @var \IT_Exchange_Product
	 */
	private $product;

	/**
	 * @var string
	 */
	private $feature_data;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param \IT_Exchange_Product $product
	 */
	public function __construct( \IT_Exchange_Product $product ) {
		$this->product      = $product;
		$this->feature_data = it_exchange_get_product_feature( $product->ID, 'licensing-discount' );
	}

	/**
	 * Get the type of discount.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->feature_data['type'];
	}

	/**
	 * Get the amount of the discount.
	 *
	 * @param bool $format
	 *
	 * @return float|string
	 */
	public function get_amount( $format = false ) {
		$amount = (float) $this->feature_data['amount'];

		if ( $format ) {

			if ( $this->get_type() == self::TYPE_PERCENT ) {
				return "$amount%";
			} else {
				return it_exchange_format_price( $amount );
			}
		} else {
			return $amount;
		}
	}

	/**
	 * Get the expiry days.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_expiry_days() {
		return $this->feature_data['expiry'];
	}

	/**
	 * Check if thie discount is still valid.
	 *
	 * @since 1.0
	 *
	 * @param Key $key
	 *
	 * @return bool
	 */
	public function is_discount_valid( Key $key ) {

		if ( "" == $this->get_expiry_days() ) {
			return true;
		}

		$expiry_date = $key->get_expires();

		$diff = $expiry_date->diff( new \DateTime() );

		return $diff->days < $this->get_expiry_days();
	}

	/**
	 * Get the discounted price of this product.
	 *
	 * @since 1.0
	 *
	 * @param bool $format
	 *
	 * @return float|string
	 */
	public function get_discount_price( $format = false ) {

		$price = it_exchange_get_product_feature( $this->product->ID, 'base-price' );

		switch ( $this->get_type() ) {
			case self::TYPE_FLAT:
				$price -= $this->get_amount();
				break;
			case self::TYPE_PERCENT:
				$price -= $price * ( $this->get_amount() / 100 );
				break;
		}

		if ( $format ) {
			return it_exchange_format_price( $price );
		} else {
			return $price;
		}
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->get_amount( true );
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize() {
		$data = array(
			'product' => $this->product->ID
		);

		return serialize( $data );
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 *
	 * @param string $serialized <p>
	 *                           The string representation of the object.
	 *                           </p>
	 *
	 * @return void
	 */
	public function unserialize( $serialized ) {

		$product = it_exchange_get_product( $serialized['product'] );

		$this->product      = $product;
		$this->feature_data = it_exchange_get_product_feature( $product->ID, 'licensing-discount' );
	}
}