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

	/**
	 * Max number of activations is reached during activation.
	 */
	const CODE_MAX_ACTIVATIONS = 1;

	/**
	 * Invalid license key used for authentication.
	 */
	const CODE_INVALID_KEY = 2;

	/**
	 * Location required during activation.
	 */
	const CODE_NO_LOCATION = 3;

	/**
	 * Location ID required for deactivation.
	 */
	const CODE_NO_LOCATION_ID = 4;

	/**
	 * Invalid location ID used for deactivation.
	 */
	const CODE_INVALID_LOCATION = 5;

	/**
	 * Activation ID is required when getting latest version.
	 */
	const CODE_ACTIVATION_ID_REQUIRED = 6;

	/**
	 * Invalid activation ID used when getting latest version.
	 */
	const CODE_INVALID_ACTIVATION = 7;

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