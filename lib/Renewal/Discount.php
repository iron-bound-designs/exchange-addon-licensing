<?php
/**
 * Class Representing Renewal Discounts
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Renewal;

use ITELIC\Key;
use ITELIC\Product;

/**
 * Class Discount
 *
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
	 * @var Product
	 */
	private $product;

	/**
	 * @var string
	 */
	private $feature_data;

	/**
	 * @var Key
	 */
	private $key;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Key $key
	 */
	public function __construct( Key $key ) {
		$this->product      = $key->get_product();
		$this->feature_data = $this->product->get_feature( 'licensing-discount' );
		$this->key          = $key;
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
	 * Check if the discount is still valid.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_discount_valid() {

		if ( '' === trim( $this->get_expiry_days() ) ) {
			return true;
		}

		$expiry_date = $this->key->get_expires();

		$now = \ITELIC\make_date_time();

		if ( $expiry_date > $now ) {
			return true;
		}

		$diff = $expiry_date->diff( $now );

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

		$price = $this->get_amount_paid();

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
	 * Get the total amount paid.
	 *
	 * @since 1.0
	 *
	 * @return float
	 */
	public function get_amount_paid() {

		$txn      = $this->key->get_transaction();
		$products = $txn->get_products();

		$amount = $this->product->get_feature( 'base-price' );

		foreach ( $products as $product ) {

			if ( $product['product_id'] == $this->product->ID ) {
				$amount = $product['product_subtotal'];

				break;
			}
		}

		return $amount;
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
	 *
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize() {
		$data = array(
			'product' => $this->product->ID,
			'key'     => $this->key->get_key()
		);

		return serialize( $data );
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Constructs the object
	 *
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 *
	 * @param string $serialized <p>
	 *                           The string representation of the object.
	 *                           </p>
	 *
	 * @return void
	 */
	public function unserialize( $serialized ) {

		$data = unserialize( $serialized );

		$this->product      = itelic_get_product( $data['product'] );
		$this->feature_data = $this->product->get_feature( 'licensing-discount' );
		$this->key          = itelic_get_key( $data['key'] );
	}
}