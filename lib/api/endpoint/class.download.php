<?php

/**
 * Endpoint for directly downloading a file.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
class ITELIC_API_Endpoint_Download extends ITELIC_API_Endpoint {

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

		if ( ! itelic_validate_query_args( $get ) ) {
			status_header( 403 );

			_e( "This download link is invalid or has expired.", ITELIC::SLUG );
			die();
		}

		$key = itelic_get_key( $get['key'] );

		$download_id = it_exchange_get_product_feature( $key->get_product()->ID, 'licensing', array( 'field' => 'update-file' ) );

		foreach ( $key->get_transaction()->get_products() as $product_hash => $product ) {

			if ( $product['product_id'] == $key->get_product()->ID ) {
				$hashes = it_exchange_get_download_hashes_for_transaction_product( $key->get_transaction(), $product, $download_id );

				it_exchange_serve_product_download( it_exchange_get_download_data_from_hash( $hashes[0] ) );
			}
		}
	}
}