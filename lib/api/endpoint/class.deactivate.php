<?php
/**
 * Endpoint for deactivating a license key at a location.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_API_Endpoint_Deactivate
 */
class ITELIC_API_Endpoint_Deactivate extends ITELIC_API_Endpoint implements ITELIC_API_Interface_Authenticatable {

	/**
	 * @var ITELIC_Key
	 */
	protected $key;

	/**
	 * Serve the request to this endpoint.
	 *
	 * @param ArrayAccess $get
	 * @param ArrayAccess $post
	 *
	 * @return ITELIC_API_Response
	 */
	public function serve( ArrayAccess $get, ArrayAccess $post ) {

		if ( ! isset( $post['id'] ) ) {
			return new ITELIC_API_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => self::CODE_NO_LOCATION_ID,
					'message' => __( "Activation ID is required.", ITELIC::SLUG )
				)
			), 400 );
		}

		$location_id = absint( $post['id'] );

		try {
			$activation = ITELIC_Activation::with_id( $location_id );

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

		return new ITELIC_API_Response( array(
			'success' => true,
			'body'    => $activation
		) );
	}

	/**
	 * Trigger the location not found.
	 *
	 * @since 1.0
	 *
	 * @return ITELIC_API_Response
	 */
	protected function trigger_not_found() {
		return new ITELIC_API_Response( array(
			'success' => false,
			'error'   => array(
				'code'    => self::CODE_INVALID_LOCATION,
				'message' => __( "Activation record could not be found.", ITELIC::SLUG )
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
		return ITELIC_API_Interface_Authenticatable::MODE_EXISTS;
	}

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_auth_error_message() {
		return __( "A license key is required for this request.", ITELIC::SLUG );
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
	 * @param ITELIC_Key $key
	 */
	public function set_auth_license_key( ITELIC_Key $key ) {
		$this->key = $key;
	}
}