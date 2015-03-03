<?php
/**
 * Interface to be used on endpoints that require authentication.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Interface ITELIC_API_Endpoint_Authenticatable
 */
interface ITELIC_API_Interface_Authenticatable {

	/**
	 * @var string. Used when the license key must be valid ( not expired ).
	 */
	const MODE_ACTIVE = 'active';

	/**
	 * @var string. Used when the license key only has to exist.
	 */
	const MODE_EXISTS = 'exists';

	/**
	 * Retrieve the mode of authentication.
	 *
	 * @since 1.0
	 *
	 * @return string One of MODE_VALID, MODE_ACTIVE
	 */
	public function get_mode();

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_error_message();

	/**
	 * Get the error code to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_error_code();

	/**
	 * Give a reference of the API key to this object.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_Key $key
	 */
	public function give_license_key( ITELIC_Key $key );

}