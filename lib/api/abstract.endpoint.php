<?php
/**
 * Abstract base class for API endpoints.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_API_Endpoint
 */
abstract class ITELIC_API_Endpoint {

	const CODE_INVALID_KEY = 2;

	/**
	 * Serve the request to this endpoint.
	 *
	 * @param ArrayAccess $get
	 * @param ArrayAccess $post
	 *
	 * @return ITELIC_API_Response
	 */
	abstract public function serve( ArrayAccess $get, ArrayAccess $post );

}