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

	const CODE_NO_LOCATION_ID = 4;
	const CODE_INVALID_LOCATION = 5;

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

		if ( ! isset( $post['location_id'] ) ) {
			return new ITELIC_API_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => self::CODE_NO_LOCATION_ID,
					'message' => __( "Activation location ID is required.", ITELIC::SLUG )
				)
			), 400 );
		}

		$location_id = absint( $post['location_id'] );

		try {
			$activation = ITELIC_Activation::with_id( $location_id );
			$activation->deactivate();
		}
		catch ( Exception $e ) {

		}

		if ( ! isset( $activation ) || ! $activation ) {
			return new ITELIC_API_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => self::CODE_NO_LOCATION_ID,
					'message' => __( "Activation record could not be found.", ITELIC::SLUG )
				)
			) );
		}

		return new ITELIC_API_Response( array(
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
	public function get_mode() {
		return ITELIC_API_Interface_Authenticatable::MODE_EXISTS;
	}

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_error_message() {
		return __( "A license key is required for this request.", ITELIC::SLUG );
	}

	/**
	 * Get the error code to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_error_code() {
		return self::CODE_INVALID_KEY;
	}

	/**
	 * Give a reference of the API key to this object.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_Key $key
	 */
	public function give_license_key( ITELIC_Key $key ) {
		$this->key = $key;
	}
}