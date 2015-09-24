<?php
/**
 * Generate a random license.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key\Generator;

use ITELIC\Key\Generator;

/**
 * Class Random
 * @package ITELIC\Key\Generator
 */
class Random extends Generator {

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param array                    $options Key options
	 * @param \IT_Exchange_Product     $product
	 * @param \IT_Exchange_Customer    $customer
	 * @param \IT_Exchange_Transaction $transaction
	 */
	public function __construct( $options = array(), $product, $customer, $transaction ) {

		if ( empty( $options['length'] ) ) {
			throw new \InvalidArgumentException( "Length is required to generate a key based on the random strategy." );
		}

		$options['length'];

		parent::__construct( $options, $product, $customer, $transaction );
	}

	/**
	 * Generate a license according to this method's algorithm.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function generate() {
		return $this->rand_sha1( $this->options['length'] );
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