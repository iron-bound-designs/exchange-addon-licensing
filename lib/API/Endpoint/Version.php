<?php
/**
 * Endpoint for retrieving version information about an API key.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\API\Endpoint;
use ITELIC\API\Endpoint;
use ITELIC\API\Contracts\Authenticatable;
use ITELIC\Key;
use ITELIC\API\Response;
use API\Exception;
use ITELIC\Plugin;
use ITELIC\Activation;

/**
 * Class Version
 * @package ITELIC\API\Endpoint
 */
class Version extends Endpoint implements Authenticatable {

	/**
	 * @var Key
	 */
	protected $key;

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

		if ( ! isset( $get['activation_id'] ) ) {
			throw new Exception( __( "'activation_id' is a required parameter.", Plugin::SLUG ), self::CODE_ACTIVATION_ID_REQUIRED );
		}

		$activation = itelic_get_activation( $get['activation_id'] );

		if ( ! $activation || $activation->get_status() != Activation::ACTIVE ) {
			throw new Exception( __( "Invalid activation passed.", Plugin::SLUG ), self::CODE_INVALID_ACTIVATION );
		}

		$now     = new \DateTime( 'now', new \DateTimeZone( get_option( 'timezone_string' ) ) );
		$expires = $now->add( new \DateInterval( "P1D" ) );

		return new Response( array(
			'success' => true,
			'body'    => array(
				'list' => array(
					$this->key->get_product()->ID => array(
						'version' => it_exchange_get_product_feature( $this->key->get_product()->ID, 'licensing', array( 'field' => 'version' ) ),
						'package' => \ITELIC\generate_download_link( $this->key, $this->key->get_product() ),
						'expires' => $expires->format( \DateTime::ISO8601 )
					)
				)
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
		return __( "An active license key is required.", Plugin::SLUG );
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
}