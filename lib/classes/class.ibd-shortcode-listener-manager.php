<?php
/**
 * Manage shortcode listenerss.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class IBD_Shortcode_Listener_Manager
 */
class IBD_Shortcode_Listener_Manager {

	/**
	 * @var array
	 */
	private static $listeners = array();

	/**
	 * Setup a listener for this shortcode.
	 *
	 * @param string                 $shortcode
	 * @param IBD_Shortcode_Listener $listener
	 */
	public static function listen( $shortcode, IBD_Shortcode_Listener $listener ) {
		self::$listeners[ $shortcode ][ self::make_key( $listener ) ] = $listener;
	}

	/**
	 * Get the necessary listener.
	 *
	 * @param string $shortcode
	 * @param string $slug
	 * @param string $attr
	 *
	 * @return IBD_Shortcode_Listener
	 */
	private static function get_listener_for( $shortcode, $slug, $attr ) {
		$key = self::make_key_from_parts( $slug, $attr );

		$listeners = self::get_listeners( $shortcode );

		return isset( $listeners[ $key ] ) ? $listeners[ $key ] : new IBD_Shortcode_Null_Listener();
	}

	/**
	 * Make a key for a shortcode listener.
	 *
	 * @param IBD_Shortcode_Listener $listener
	 *
	 * @return string
	 */
	private static function make_key( IBD_Shortcode_Listener $listener ) {
		return self::make_key_from_parts( $listener->get_slug(), $listener->get_attr() );
	}

	/**
	 * Take the parts from a listener, and translate it to a key.
	 *
	 * @param string $slug
	 * @param string $attr
	 *
	 * @return string
	 */
	private static function make_key_from_parts( $slug, $attr ) {
		return md5( $slug . $attr );
	}

	/**
	 * Get an iterator to iterate over all listeners for a certain shortcode.
	 *
	 * @param string $shortcode
	 *
	 * @return IBD_Shortcode_Listener[]
	 */
	public static function get_listeners( $shortcode ) {
		return isset( self::$listeners[ $shortcode ] ) ? self::$listeners[ $shortcode ] : array();
	}

	/**
	 *
	 * Get the shortcode for a listener.
	 *
	 * [shortcode type="" value=""]
	 *
	 * @param string                 $shortcode
	 * @param IBD_Shortcode_Listener $listener
	 *
	 * @return string
	 */
	public static function get_shortcode( $shortcode, IBD_Shortcode_Listener $listener ) {
		$shortcode = (string) $shortcode;

		return "[$shortcode type=\"{$listener->get_slug()}\" value=\"{$listener->get_attr()}\"]";
	}

	/**
	 * @var array
	 */
	private $shortcode = array();

	/**
	 * @var array
	 */
	private $data_sources = array();

	/**
	 * Constructor.
	 *
	 * @param string $shortcode
	 * @param array  $data_sources
	 */
	public function __construct( $shortcode, $data_sources ) {
		$this->shortcode    = $shortcode;
		$this->data_sources = $data_sources;

		add_shortcode( $shortcode, array( $this, 'controller' ) );
	}

	/**
	 * Render the content for this shortcode.
	 *
	 * @param $attr array
	 *
	 * @return string
	 */
	public function controller( $attr ) {

		$attr = shortcode_atts( array(
			'type'  => '',
			'value' => ''
		), $attr );

		$listener = self::get_listener_for( $this->shortcode, $attr['type'], $attr['value'] );

		$params = $listener->get_callback_reflection()->getParameters();

		$args = array();

		foreach ( $params as $param ) {

			$found = false;

			foreach ( $this->data_sources as $name => $data_source ) {

				if ( is_object( $data_source ) && ( $class = $param->getClass() ) !== null && $class->isInstance( $data_source ) ) {
					$args[] = $data_source;
					$found  = true;
				} elseif ( $param->getName() === $name ) {

					if ( isset( $class ) ) {
						throw new BadMethodCallException(
							"Data source '$name' does not match type of required parameter '{$param->getName()}'. Required type: '{$class->getName()}''"
						);
					}

					$args[] = $data_source;
					$found  = true;
				}

				if ( $found ) {
					break;
				}
			}

			if ( $found ) {
				continue;
			}

			if ( $param->isDefaultValueAvailable() ) {
				$args[] = $param->getDefaultValue();
			} else {
				throw new BadMethodCallException( "Not all required parameters were provided. Required: '{$param->getName()}'.'" );
			}
		}

		return $listener->render( $args );
	}
}