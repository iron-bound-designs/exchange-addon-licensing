<?php
/**
 * Template tag listener.
 *
 * @author Iron Bound Designs
 * @since  1.0
 *
 * @copyright   Copyright (c) 2015, Iron Bound Designs, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License v2 or later
 */

namespace IronBound\WP_Notifications\Template;

/**
 * Class Listener
 * @package IronBound\WP_Notifications\Template
 */
class Listener {

	/**
	 * @var string
	 */
	private $tag;

	/**
	 * @var callable
	 */
	private $callback;

	/**
	 * Listener constructor.
	 *
	 * @since 1.0
	 *
	 * @param string   $tag
	 * @param callable $callback
	 */
	public function __construct( $tag, $callback ) {
		$this->tag      = $tag;
		$this->callback = $callback;
	}

	/**
	 * Get a reflection object for the callback function.
	 *
	 * @since 1.0
	 *
	 * @return \ReflectionFunctionAbstract
	 */
	public function get_callback_reflection() {
		if ( is_array( $this->callback ) ) {
			return new \ReflectionMethod( $this->callback[0], $this->callback[1] );
		} else {
			return new \ReflectionFunction( $this->callback );
		}
	}

	/**
	 * Render the template tag.
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function render( array $args ) {
		return call_user_func_array( $this->callback, $args );
	}

	/**
	 * Get the template tag.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_tag() {
		return $this->tag;
	}

	/**
	 * Render a human readable form of the tag.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	function __toString() {
		return ucwords( str_replace( array( '-', '_' ), ' ', $this->get_tag() ) );
	}

}