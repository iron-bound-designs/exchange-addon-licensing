<?php
/**
 * Load the API module.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

new ITELIC_API_Dispatch();

ITELIC_API_Dispatch::register_endpoint( new ITELIC_API_Endpoint_Activate(), 'activate' );
ITELIC_API_Dispatch::register_endpoint( new ITELIC_API_Endpoint_Deactivate(), 'deactivate' );
ITELIC_API_Dispatch::register_endpoint( new ITELIC_API_Endpoint_Info(), 'info' );
ITELIC_API_Dispatch::register_endpoint( new ITELIC_API_Endpoint_Version(), 'version' );