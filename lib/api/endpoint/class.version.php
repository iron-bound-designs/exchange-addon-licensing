<?php
/**
 * Endpoint for retrieving version information about an API key.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_API_Endpoint_Version
 */
class ITELIC_API_Endpoint_Version extends ITELIC_API_Endpoint implements ITELIC_API_Interface_Authenticatable {

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
		$now     = new DateTime();
		$expires = $now->add( new DateInterval( "P1D" ) );

		$download = ITELIC_API_Dispatch::get_url( 'download' );
		$download = add_query_arg( itelic_generate_download_query_args( $this->key, $expires ), $download );


		return new ITELIC_API_Response( array(
			'success' => true,
			'body'    => array(
				'version' => it_exchange_get_product_feature( $this->key->get_product()->ID, 'licensing', array( 'field' => 'version' ) ),
				'package' => $download,
				'expires' => $expires->format( DateTime::ISO8601 )
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
	public function get_mode() {
		return ITELIC_API_Interface_Authenticatable::MODE_ACTIVE;
	}

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_error_message() {
		return __( "An active license key is required.", ITELIC::SLUG );
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