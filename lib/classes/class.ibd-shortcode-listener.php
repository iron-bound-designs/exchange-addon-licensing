<?php
/**
 * Listener for shortcodes.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class IBD_Shortcode_Listener
 */
class IBD_Shortcode_Listener {

	/**
	 * @var string
	 */
	protected $slug;

	/**
	 * @var string
	 */
	protected $attr;

	/**
	 * @var callable
	 */
	protected $callback;

	/**
	 * Constructor.
	 *
	 * @param string   $slug
	 * @param string   $attr
	 * @param callable $callback
	 */
	public function __construct( $slug, $attr, $callback ) {
		$this->slug     = (string) $slug;
		$this->attr     = (string) $attr;
		$this->callback = $callback;
	}

	/**
	 * Get a reflection object for the callback function.
	 *
	 * @return ReflectionFunctionAbstract
	 */
	public function get_callback_reflection() {

		if ( is_array( $this->callback ) ) {
			return new ReflectionMethod( $this->callback[0], $this->callback[1] );
		} else {
			return new ReflectionFunction( $this->callback );
		}
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function render( array $args ) {

		return call_user_func_array( $this->callback, $args );
	}

	/**
	 * @return string
	 */
	public function get_attr() {
		return $this->attr;
	}

	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * @return string
	 */
	function __toString() {
		$slug = ucwords( str_replace( array( '-', '_' ), ' ', $this->get_slug() ) );
		$attr = ucwords( str_replace( array( '-', '_' ), ' ', $this->get_attr() ) );

		return "$slug – $attr";
	}
}