<?php
/**
 * Endpoint for activating a license key.
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
use ITELIC\Key;
use ITELIC\API\Response;
use ITELIC\API\Exception;
use ITELIC\Plugin;

/**
 * Class Activate
 * @package ITELIC\API\Endpoint
 */
class Activate extends Endpoint implements Authenticatable {

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

		if ( ! isset( $post['location'] ) ) {
			throw new Exception( __( "Activation location is required.", Plugin::SLUG ), self::CODE_NO_LOCATION );
		}

		$location = sanitize_text_field( $post['location'] );

		$version = isset( $post['version'] ) ? $post['version'] : '';

		if ( isset( $post['track'] ) && in_array( $post['track'], array( 'stable', 'pre-release' ) ) ) {
			$track = $post['track'];
		} else {
			$track = 'stable';
		}

		if ( $version ) {
			$release = itelic_get_release_by_version( $this->key->get_product()->ID, $version );
		} else {
			$release = null;
		}

		$activation = itelic_get_activation_by_location( $location, $this->key );

		try {
			if ( $activation ) {

				if ( $activation->get_status() == Activation::DEACTIVATED ) {
					$activation->reactivate();
				}

				$activation->update_meta( 'track', $track );

				if ( $release ) {
					$activation->set_release( $release );
				}
			} else {
				$activation = itelic_activate_license_key( $this->key, $location, null, $release, $track );
			}
		}
		catch ( \LengthException $e ) {
			throw new Exception( $e->getMessage(), Endpoint::CODE_INVALID_LOCATION, $e );
		}
		catch ( \OverflowException $e ) {
			throw new Exception( $e->getMessage(), Endpoint::CODE_MAX_ACTIVATIONS, $e );
		}

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
	 * @return string One of MODE_EXISTS, MODE_ACTIVE
	 */
	public function get_auth_mode() {
		return Authenticatable::MODE_ACTIVE;
	}

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_auth_error_message() {
		return __( "Your license key has expired.", Plugin::SLUG );
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