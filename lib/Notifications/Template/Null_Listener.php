<?php
/**
 * Null listener.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Notifications\Template;

/**
 * Class Null_Listener
 * @package ITELIC\Notifications\Template
 */
class Null_Listener extends Listener {

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct( '', array( $this, 'callback' ) );
	}

	/**
	 * Empty callback.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function callback() {
		return '';
	}

	/**
	 * Render an empty string.
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function render( array $args ) {
		return "";
	}
}