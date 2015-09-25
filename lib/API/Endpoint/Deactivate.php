<?php
/**
 * Endpoint for deactivating a license key at a location.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\API\Endpoint;

use ITELIC\Activation;
use ITELIC\API\Endpoint;
use ITELIC\API\Contracts\Authenticatable;
use ITELIC\API\Response;
use API\Exception;
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

		$location_id = absint( $post['id'] );

		try {
			$activation = itelic_get_activation( $location_id );

			if ( $activation ) {
				if ( $activation->get_key()->get_key() !== $this->key->get_key() ) {
					return $this->trigger_not_found();
				} else {
					$activation->deactivate();
				}
			} else {
				return $this->trigger_not_found();
			}
		}
		catch ( Exception $e ) {
			return $this->trigger_not_found();
		}

		return new Response( array(
			'success' => true,
			'body'    => $activation
		) );
	}

	/**
	 * Trigger the location not found.
	 *
	 * @since 1.0
	 *
	 * @return Response
	 */
	protected function trigger_not_found() {
		return new Response( array(
			'success' => false,
			'error'   => array(
				'code'    => self::CODE_INVALID_LOCATION,
				'message' => __( "Activation record could not be found.", Plugin::SLUG )
			)
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