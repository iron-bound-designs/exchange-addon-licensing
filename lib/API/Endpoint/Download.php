<?php
/**
 * Endpoint for directly downloading a file.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\API\Endpoint;
use ITELIC\API\Endpoint;
use ITELIC\Key;
use ITELIC\API\Response;
use ITELIC\Plugin;

/**
 * Class Download
 * @package ITELIC\API\Endpoint
 */
class Download extends Endpoint {

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
	 */
	public function serve( \ArrayAccess $get, \ArrayAccess $post ) {

		if ( ! \ITELIC\validate_query_args( $get ) ) {
			status_header( 403 );

			_e( "This download link is invalid or has expired.", Plugin::SLUG );
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