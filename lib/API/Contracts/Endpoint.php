<?php
/**
 * Interface for endpoints.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\API\Contracts;

use API\Exception;
use ITELIC\API\Response;

interface Endpoint {

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
	public function serve( \ArrayAccess $get, \ArrayAccess $post );

}