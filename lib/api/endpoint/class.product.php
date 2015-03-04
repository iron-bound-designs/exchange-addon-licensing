<?php
/**
 * Product Endpoint API
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_API_Endpoint_Product
 */
class ITELIC_API_Endpoint_Product extends ITELIC_API_Endpoint implements ITELIC_API_Interface_Authenticatable {

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

		$now     = new DateTime( 'now', new DateTimeZone( get_option( 'timezone_string' ) ) );
		$expires = $now->add( new DateInterval( "P1D" ) );

		$download = ITELIC_API_Dispatch::get_url( 'download' );
		$download = add_query_arg( itelic_generate_download_query_args( $this->key, $expires ), $download );

		$readme = it_exchange_get_product_feature( $this->key->get_product()->ID, 'licensing-readme' );

		$body = array(
			'id'              => $this->key->get_product()->ID,
			'name'            => $this->key->get_product()->post_title,
			'description'     => it_exchange_get_product_feature( $this->key->get_product()->ID, 'description' ),
			'version'         => it_exchange_get_product_feature( $this->key->get_product()->ID, 'licensing', array( 'field' => 'version' ) ),
			'tested'          => $readme['tested'],
			'requires'        => $readme['requires'],
			'author'          => $readme['author'],
			'last_updated'    => empty( $readme['last_updated'] ) ? '' : $readme['last_updated']->format( DateTime::ISO8601 ),
			'banner_low'      => $readme['banner_low'],
			'banner_high'     => $readme['banner_high'],
			'package_url'     => $download,
			'description_url' => get_permalink( $this->key->get_product()->ID ),
			'changelog'       => it_exchange_get_product_feature( $this->key->get_product()->ID, 'licensing', array( 'field' => 'changelog' ) ),
			'sections'        => array()
		);

		return new ITELIC_API_Response( array(
			'success' => true,
			'body'    => $body
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
		return __( "A license key is required.", ITELIC::SLUG );
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