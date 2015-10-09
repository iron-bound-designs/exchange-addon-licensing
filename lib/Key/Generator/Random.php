<?php
/**
 * Generate a random license.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key\Generator;

use ITELIC\Key\Generator;
use ITELIC\Product;

/**
 * Class Random
 *
 * @package ITELIC\Key\Generator
 */
class Random implements Generator {

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
			'length' => 32
		);
		$options  = wp_parse_args( $options, $defaults );

		return $this->rand_sha1( $options['length'] );
	}

	/**
	 * Generate a random string using sha1.
	 *
	 * @link http://stackoverflow.com/questions/637278/what-is-the-best-way-to-generate-a-random-key-within-php
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	private function rand_sha1( $length ) {
		$max    = ceil( $length / 40 );
		$random = '';
		for ( $i = 0; $i < $max; $i ++ ) {
			$random .= sha1( microtime( true ) . mt_rand( 10000, 90000 ) );
		}

		return substr( $random, 0, $length );
	}
}