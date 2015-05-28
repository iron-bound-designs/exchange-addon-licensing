<?php
/**
 * License Generator
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key;

/**
 * Class Generator
 *
 * This class should be overridden for each license generation type.
 * It should be instantiated for each license generation.
 *
 * @since 1.0
 * @package ITELIC\Key\Generator
 */
abstract class Generator {

	/**
	 * @var int Length of the key to generate.
	 */
	protected $length = 64;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param int $length Length of the key to generate.
	 */
	public function __construct( $length = 64 ) {
		$this->length = absint( $length );
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