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

		$now     = new \DateTime( 'now', new \DateTimeZone( get_option( 'timezone_string' ) ) );
		$expires = $now->add( new \DateInterval( "P1D" ) );

		$release = $this->activation->get_key()->get_product()->get_latest_release_for_activation( $this->activation );

		if ( ! $release ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid version number', Plugin::SLUG )
			) );

			die();
		}

		return new Response( array(
			'success' => true,
			'body'    => array(
				'list' => array(
					$this->key->get_product()->ID => array(
						'version' => $release->get_version(),
						'package' => \ITELIC\generate_download_link( $this->activation ),
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
		return Authenticatable::MODE_VALID_ACTIVATION;
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