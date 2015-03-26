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

	const ACTIVATION_ID_REQUIRED = 6;
	const INVALID_ACTIVATION = 7;

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

		if ( ! isset( $get['activation_id'] ) ) {
			return new ITELIC_API_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => self::ACTIVATION_ID_REQUIRED,
					'message' => __( "'activation_id' is a required parameter.", ITELIC::SLUG )
				)
			) );
		}

		$activation = itelic_get_activation( $get['activation_id'] );

		if ( ! $activation || $activation->get_status() != ITELIC_Activation::ACTIVE ) {
			return new ITELIC_API_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => self::INVALID_ACTIVATION,
					'message' => __( "Invalid activation passed.", ITELIC::SLUG )
				)
			) );
		}

		$now     = new DateTime( 'now', new DateTimeZone( get_option( 'timezone_string' ) ) );
		$expires = $now->add( new DateInterval( "P1D" ) );

		return new ITELIC_API_Response( array(
			'success' => true,
			'body'    => array(
				'list' => array(
					$this->key->get_product()->ID => array(
						'version' => it_exchange_get_product_feature( $this->key->get_product()->ID, 'licensing', array( 'field' => 'version' ) ),
						'package' => itelic_generate_download_link( $this->key, $this->key->get_product() ),
						'expires' => $expires->format( DateTime::ISO8601 )
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