<?php
/**
 * Class for managing license key types.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Key_Types
 *
 * Simple class for storing information about key types. Prevents
 * overwriting information about a particular key type, either by
 * abusing global scope, or re-registering a method.
 *
 * @internal You should use the convenience methods in api/key-types.php
 *
 * @since 1.0
 */
final class ITELIC_Key_Types {

	/**
	 * @var array
	 */
	private static $methods = array();

	/**
	 * Register a key type.
	 *
	 * A type can only be registered once.
	 *
	 * @param string $slug
	 * @param string $name
	 * @param string $class
	 *
	 * @return bool
	 */
	public static function register( $slug, $name, $class ) {

		if ( isset( self::$methods[ $slug ] ) ) {
			return false;
		}

		self::$methods[ $slug ] = array(
			'slug'  => $slug,
			'name'  => $name,
			'class' => $class
		);

		return true;
	}

	/**
	 * Retrieve a ket type.
	 *
	 * @param string $slug
	 *
	 * @return array|bool
	 */
	public static function get( $slug ) {
		return isset( self::$methods[ $slug ] ) ? self::$methods[ $slug ] : false;
	}

	/**
	 * Retrieve all key types.
	 *
	 * @return array
	 */
	public static function all() {
		return self::$methods;
	}
}