<?php
/**
 * Changelog endpoint.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API\Endpoint;

use ITELIC\API\Endpoint;
use ITELIc\API\Exception;
use ITELIC\API\Response;
use ITELIC\Key;
use ITELIC\Plugin;

/**
 * Class Changelog
 * @package ITELIC\API\Endpoint
 */
class Changelog extends Endpoint {

	/**
	 * Serve the request to this endpoint.
	 *
	 * @param \ArrayAccess $get
	 * @param \ArrayAccess $post
	 *
	 * @return Response
	 *
	 * @throws Exception|\Exception
	 *         API Exceptions will be treated as expected errors, and will be displayed as such.
	 *         All other exceptions will be treated as unexpected errors and will be displayed with error code 0.
	 */
	public function serve( \ArrayAccess $get, \ArrayAccess $post ) {

		$product = itelic_get_product( $get['ID'] );

		if ( ! $product ) {
			status_header( 404 );
			echo __( "No product found.", Plugin::SLUG );
		} else {
			echo $product->get_changelog();
		}

		die();
	}
}