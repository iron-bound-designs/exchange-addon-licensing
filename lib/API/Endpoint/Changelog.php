<?php
/**
 * Changelog endpoint.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API\Endpoint;

use ITELIC\Activation;
use ITELIC\API\Contracts\Authenticatable;
use ITELIC\API\Endpoint;
use ITELIc\API\Exception;
use ITELIC\API\Response;
use ITELIC\Key;
use ITELIC\Plugin;

/**
 * Class Changelog
 * @package ITELIC\API\Endpoint
 */
class Changelog extends Endpoint implements Authenticatable {

	/**
	 * @var Key
	 */
	private $key;

	/**
	 * Serve the request to this endpoint.
	 *
	 * @param \ArrayAccess $get
	 * @param \ArrayAccess $post
	 *
	 * @return Response
	 *
	 * @throws Exception|\Exception
	 *         API Exceptions will be treated as expected errors, and will be displayed as such.
	 *         All other exceptions will be treated as unexpected errors and will be displayed with error code 0.
	 */
	public function serve( \ArrayAccess $get, \ArrayAccess $post ) {
		echo $this->key->get_product()->get_changelog();

		die();
	}

	/**
	 * Retrieve the mode of authentication.
	 *
	 * @since 1.0
	 *
	 * @return string One of MODE_ACTIVE, MODE_EXISTS
	 */
	public function get_auth_mode() {
		return Authenticatable::MODE_EXISTS;
	}

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_auth_error_message() {
		return __( "Invalid license key.", Plugin::SLUG );
	}

	/**
	 * Get the error code to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_auth_error_code() {
		return self::CODE_INVALID_KEY;
	}

	/**
	 * Give a reference of the API key to this object.
	 *
	 * @since 1.0
	 *
	 * @param Key $key
	 */
	public function set_auth_license_key( Key $key ) {
		$this->key = $key;
	}

	/**
	 * Give a reference of the activation record to this object.
	 *
	 * @since 1.0
	 *
	 * @param Activation $activation
	 */
	public function set_auth_activation( Activation $activation = null ) {

	}
}