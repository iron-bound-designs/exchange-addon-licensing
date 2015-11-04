<?php
/**
 * Endpoint for deactivating a license key at a location.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API\Endpoint;

use ITELIC\Activation;
use ITELIC\API\Endpoint;
use ITELIC\API\Contracts\Authenticatable;
use ITELIC\API\Response;
use ITELIC\API\Exception;
use ITELIC\Plugin;
use ITELIC\Key;

/**
 * Class Deactivate
 *
 * @package ITELIC\API\Endpoint
 */
class Deactivate extends Endpoint implements Authenticatable {

	/**
	 * @var Key
	 */
	protected $key;

	/**
	 * @var Activation|null
	 */
	protected $activation;

	/**
	 * Serve the request to this endpoint.
	 *
	 * @param \ArrayAccess $get
	 * @param \ArrayAccess $post
	 *
	 * @return Response
	 *
	 * @throws Exception|\Exception
	 */
	public function serve( \ArrayAccess $get, \ArrayAccess $post ) {

		if ( ! isset( $post['id'] ) ) {
			throw new Exception( __( "Activation ID is required.", Plugin::SLUG ), self::CODE_NO_LOCATION_ID );
		}

		/**
		 * Fires when the deactivate API endpoint is being validated.
		 *
		 * This occurs after authentication has taken place. You can return an error response by throwing an
		 * \ITELIC\API\Exception in your callback.
		 *
		 * @since 1.0
		 *
		 * @param \ArrayAccess $get
		 * @param \ArrayAccess $post
		 */
		do_action( 'itelic_api_validate_deactivate_request', $get, $post );

		$activation_id = absint( $post['id'] );

		$activation = itelic_get_activation( $activation_id );

		if ( $activation ) {
			if ( $activation->get_key()->get_key() !== $this->key->get_key() ) {
				throw new Exception( __( "Activation record ID does not match license key.", Plugin::SLUG ), self::CODE_INVALID_LOCATION );
			} else {
				$activation->deactivate();
			}
		} else {
			throw new Exception( __( "Activation record could not be found.", Plugin::SLUG ), self::CODE_INVALID_LOCATION );
		}

		/**
		 * Fires when an activation is deactivated via the HTTP API.
		 *
		 * @since 1.0
		 *
		 * @param Activation   $activation
		 * @param \ArrayAccess $get
		 * @param \ArrayAccess $post
		 */
		do_action( 'itelic_api_deactivate_key', $activation, $get, $post );

		return new Response( array(
			'success' => true,
			'body'    => $activation
		) );
	}

	/**
	 * Retrieve the mode of authentication.
	 *
	 * @since 1.0
	 *
	 * @return string One of MODE_VALID, MODE_ACTIVE
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
		return __( "A license key is required for this request.", Plugin::SLUG );
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
		$this->activation = $activation;
	}
}