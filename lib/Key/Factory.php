<?php
/**
 * Factory class for generating keys.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key;
use ITELIC\Key\Generator\Pattern;
use ITELIC\Key\Generator\Random;

/**
 * Class Factory
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
	 * @param \IT_Exchange_Product     $product
	 * @param \IT_Exchange_Customer    $customer
	 * @param \IT_Exchange_Transaction $transaction
	 */
	public function __construct( \IT_Exchange_Product $product, \IT_Exchange_Customer $customer, \IT_Exchange_Transaction $transaction ) {
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

		$type  = it_exchange_get_product_feature( $this->product->ID, 'licensing', array( 'field' => 'key-type' ) );
		$class = itelic_get_key_type_class( $type );

		$options = it_exchange_get_product_feature( $this->product->ID, 'licensing', array( 'field' => "type.$type" ) );

		switch ( $class ) {
			case 'ITELIC_Key_Generator_Random':
				$generator = new Random( $options['length'] );
				break;
			case 'ITELIC_Key_Generator_Pattern':
				$generator = new Pattern( $options['pattern'] );
				break;
			default:

				/**
				 * Filter used when the factory doesn't know how to construct the desired class.
				 *
				 * @since 1.0
				 *
				 * @param Generator $generator
				 * @param Factory   $this
				 */
				$generator = apply_filters( "it_exchange_itelic_key_factory_make_$class", null, $this );
				break;
		}

		return $generator->generate();
	}

	/**
	 * @return \IT_Exchange_Product
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