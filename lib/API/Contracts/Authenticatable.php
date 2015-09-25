<?php
/**
 * Interface to be used on endpoints that require authentication.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\API\Contracts;

use ITELIC\Activation;
use ITELIC\Key;

/**
 * Interface Authenticatable
 * @package ITELIC\API\Contracts
 */
interface Authenticatable {

	/**
	 * @var string. Used when the license key must be valid ( not expired ).
	 */
	const MODE_ACTIVE = 'active';

	/**
	 * @var string. Used when the license key only has to exist.
	 */
	const MODE_EXISTS = 'exists';

	/**
	 * @var string. Used when the activation record has to be active.
	 */
	const MODE_VALID_ACTIVATION = 'valid-activation';

	/**
	 * Retrieve the mode of authentication.
	 *
	 * @since 1.0
	 *
	 * @return string One of MODE_ACTIVE, MODE_EXISTS
	 */
	public function get_auth_mode();

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_auth_error_message();

	/**
	 * Get the error code to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_auth_error_code();

	/**
	 * Give a reference of the API key to this object.
	 *
	 * @since 1.0
	 *
	 * @param Key $key
	 */
	public function set_auth_license_key( Key $key );

	/**
	 * Give a reference of the activation record to this object.
	 *
	 * @since 1.0
	 *
	 * @param Activation $activation
	 */
	public function set_auth_activation( Activation $activation = null );
}