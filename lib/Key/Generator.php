<?php
/**
 * License Generator
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key;

use ITELIC\Product;

/**
 * Class Generator
 *
 * This class should be overridden for each license generation type.
 * It should be instantiated for each license generation.
 *
 * @since   1.0
 * @package ITELIC\Key\Generator
 */
abstract class Generator {

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var Product
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
	 * @param array                    $options Key options
	 * @param Product                  $product
	 * @param \IT_Exchange_Customer    $customer
	 * @param \IT_Exchange_Transaction $transaction
	 */
	public function __construct( $options = array(), Product $product, \IT_Exchange_Customer $customer, \IT_Exchange_Transaction $transaction ) {
		$this->options     = $options;
		$this->product     = $product;
		$this->customer    = $customer;
		$this->transaction = $transaction;
	}

	/**
	 * Generate a license according to this method's algorithm.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public abstract function generate();

}