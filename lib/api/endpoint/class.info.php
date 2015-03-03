<?php
/**
 * Endpoint for retrieving info about an API key.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_API_Endpoint_Info
 */
class ITELIC_API_Endpoint_Info extends ITELIC_API_Endpoint implements ITELIC_API_Interface_Authenticatable {

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

		return new ITELIC_API_Response( array(
			'success' => true,
			'body'    => $this->key
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
		return __( "Invalid license key.", ITELIC::SLUG );
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