<?php
/**
 * Generate a license based on a pattern.
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
 * Class Pattern
 *
 * @package ITELIC\Key\Generator
 */
class Pattern implements Generator {

	/**
	 * @var array
	 */
	protected $char_map = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		$this->char_map['X'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$this->char_map['x'] = 'abcdefghijklmnopqrstuvwxyz';
		$this->char_map['9'] = '0123456789';
		$this->char_map['#'] = '!@#$%^&*()+=[]/';
		$this->char_map['?'] = $this->char_map['X'] . $this->char_map['x'] . $this->char_map['9'] . $this->char_map['#'];
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

		if ( ! isset( $options['pattern'] ) ) {
			throw new \InvalidArgumentException( "'pattern' option required." );
		}

		$pattern = $options['pattern'];

		$key = '';
		$len = strlen( $pattern );

		for ( $i = 0; $i < $len; $i ++ ) {
			$char = $pattern[ $i ];

			if ( $char == '\\' ) {

				$i += 1;
				$key .= $pattern[ $i ];

			} else {
				$key .= $this->map_char( $char );
			}
		}

		return $key;
	}

	/**
	 * Map a char.
	 *
	 * @since 1.0
	 *
	 * @param string $char
	 *
	 * @return string
	 */
	protected function map_char( $char ) {
		if ( isset( $this->char_map[ $char ] ) ) {

			$rand = mt_rand( 0, strlen( $this->char_map[ $char ] ) - 1 );

			return $this->char_map[ $char ][ $rand ];
		} else {
			return $char;
		}
	}

}