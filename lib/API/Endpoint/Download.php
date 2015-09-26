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
use ITELIC\Update;

/**
 * Class Download
 *
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

		$activation = itelic_get_activation( $get['activation'] );
		$key        = $get['key'];

		if ( ! $activation || $activation->get_key()->get_key() != $key ) {
			status_header( 403 );

			_e( "This download link is invalid or has expired.", Plugin::SLUG );
			die();
		}

		$release = $activation->get_key()->get_product()->get_latest_release_for_activation( $activation );

		$file = $release->get_download();

		Update::create( $activation, $release );

		\ITELIC\serve_download( wp_get_attachment_url( $file->ID ) );
	}
}