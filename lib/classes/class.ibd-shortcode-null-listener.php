<?php
/**
 * Null listener to implement the null object pattern.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class IBD_Shortcode_Null_Listener
 */
class IBD_Shortcode_Null_Listener extends IBD_Shortcode_Listener {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( '', '', array( $this, 'callback' ) );
	}

	/**
	 * @return string
	 */
	public function callback() {
		return '';
	}

	/**
	 * Render an empty string.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function render( array $args ) {
		return "";
	}
}