<?php
/**
 * Generate a license based on a pattern.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key\Generator;
use ITELIC\Key\Generator;

/**
 * Class Pattern
 * @package ITELIC\Key\Generator
 */
class Pattern extends Generator {

	/**
	 * @var string
	 */
	protected $pattern;

	/**
	 * @var array
	 */
	protected $char_map = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param string $pattern
	 */
	public function __construct( $pattern ) {

		$this->pattern = $pattern;

		$this->char_map['X'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$this->char_map['x'] = 'abcdefghijklmnopqrstuvwxyz';
		$this->char_map['9'] = '0123456789';
		$this->char_map['#'] = '!@#$%^&*()+=[]/';
		$this->char_map['?'] = $this->char_map['X'] . $this->char_map['x'] . $this->char_map['9'] . $this->char_map['#'];

		parent::__construct();
	}

	/**
	 * Generate a license according to this method's algorithm.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function generate() {

		$key = '';
		$len = strlen( $this->pattern );

		for ( $i = 0; $i < $len; $i ++ ) {
			$char = $this->pattern[ $i ];

			if ( $char == '\\' ) {

				$i += 1;
				$key .= $this->pattern[ $i ];

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