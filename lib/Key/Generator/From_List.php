<?php
/**
 * List key type.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Key\Generator;

use ITELIC\Key\Generator;
use ITELIC\Product;

/**
 * Class From_List
 *
 * @package ITELIC\Key\Generator
 */
class From_List implements Generator {

	/**
	 * @var Generator
	 */
	protected $backup_provider;

	/**
	 * Constructor.
	 *
	 * @param Generator $backup_provider Used if no keys are left.
	 */
	public function __construct( Generator $backup_provider ) {
		$this->backup_provider = $backup_provider;
	}

	/**
	 * Generate a license according to this method's algorithm.
	 *
	 * @since 1.0
	 *
	 * @param array                    $options
	 * @param Product                  $product
	 * @param \IT_Exchange_Customer    $customer
	 * @param \IT_Exchange_Transaction $transaction
	 *
	 * @return string
	 */
	public function generate( $options = array(), Product $product, \IT_Exchange_Customer $customer, \IT_Exchange_Transaction $transaction ) {

		$defaults = array(
			'keys' => ''
		);
		$options  = wp_parse_args( $options, $defaults );

		$keys = $options['keys'];

		if ( empty( $keys ) ) {
			return $this->backup_provider->generate( $options, $product, $customer, $transaction );
		}

		$keys = explode( PHP_EOL, $keys );

		$key = $keys[0];

		unset( $keys[0] );

		$keys_list = trim( implode( PHP_EOL, $keys ) );

		$product->update_feature( 'licensing', array( 'keys' => $keys_list ), array(
			'key-type' => 'list'
		) );

		return trim( $key );
	}

}