<?php
/**
 * Factory class for generating keys.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Key;

use ITELIC\Key\Generator\Pattern;
use ITELIC\Key\Generator\Random;
use ITELIC\Product;

/**
 * Class Factory
 *
 * @package ITELIC\Key
 */
class Factory {

	/**
	 * @var \IT_Exchange_Product
	 */
	protected $product;

	/**
	 * @var \IT_Exchange_Customer
	 */
	protected $customer;

	/**
	 * @var \IT_Exchange_Transaction
	 */
	protected $transaction;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Product                  $product
	 * @param \IT_Exchange_Customer    $customer
	 * @param \IT_Exchange_Transaction $transaction
	 */
	public function __construct( Product $product, \IT_Exchange_Customer $customer, \IT_Exchange_Transaction $transaction ) {
		$this->product     = $product;
		$this->customer    = $customer;
		$this->transaction = $transaction;
	}

	/**
	 * Make the license key.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function make() {

		$type      = $this->get_product()->get_feature( 'licensing', array( 'field' => 'key-type' ) );
		$options = $this->get_product()->get_feature( 'licensing', array( 'field' => "type.$type" ) );

		$generator = itelic_get_key_type_generator( $type );

		if ( $generator ) {
			$key = $generator->generate(
				$options, $this->get_product(),
				$this->get_customer(), $this->get_transaction()
			);

			return substr( trim( $key ), 0, 128 );
		} else {
			throw new \UnexpectedValueException( "Invalid key type '$type''" );
		}
	}

	/**
	 * @return Product
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * @return \IT_Exchange_Customer
	 */
	public function get_customer() {
		return $this->customer;
	}

	/**
	 * @return \IT_Exchange_Transaction
	 */
	public function get_transaction() {
		return $this->transaction;
	}
}