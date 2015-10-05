<?php
/**
 * List key type.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key\Generator;

use ITELIC\Key\Generator;

/**
 * Class From_List
 * @package ITELIC\Key\Generator
 */
class From_List extends Generator {

	/**
	 * Generate a license according to this method's algorithm.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function generate() {
		$keys = $this->options['keys'];
		$keys = explode( PHP_EOL, $keys );

		if ( empty( $keys ) ) {
			$random = new Random( array( 'length' => 32 ), $this->product, $this->customer, $this->transaction );

			return $random->generate();
		}

		$key = $keys[0];

		unset( $keys[0] );

		$keys_list = implode( PHP_EOL, $keys );

		$this->product->update_feature( 'licensing', array( 'keys' => $keys_list ), array(
			'key-type' => 'list'
		) );

		return $key;
	}
}