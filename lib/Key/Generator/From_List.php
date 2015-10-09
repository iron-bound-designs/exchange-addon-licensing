<?php
/**
 * List key type.
 *
 * @author Iron Bound Designs
 * @since  1.0
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
		$keys = $options['keys'];
		$keys = explode( PHP_EOL, $keys );

		if ( empty( $keys ) ) {
			$random = new Random();

			return $random->generate( $options, $product, $customer, $transaction );
		}

		$key = $keys[0];

		unset( $keys[0] );

		$keys_list = implode( PHP_EOL, $keys );

		$product->update_feature( 'licensing', array( 'keys' => $keys_list ), array(
			'key-type' => 'list'
		) );

		return $key;
	}

}