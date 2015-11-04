<?php
/**
 * Load the API module.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API;

use ITELIC\API\Endpoint\Activate;
use ITELIC\API\Endpoint\Changelog;
use ITELIC\API\Endpoint\Deactivate;
use ITELIC\API\Endpoint\Download;
use ITELIC\API\Endpoint\Info;
use ITELIC\API\Endpoint\Product;
use ITELIC\API\Endpoint\Version;
use ITELIC\API\Responder\JSON_Responder;

add_action( 'itelic_api_register_endpoints', function ( Dispatch $dispatch ) {

	$dispatch->register_endpoint( new Activate(), 'activate' );
	$dispatch->register_endpoint( new Deactivate(), 'deactivate' );
	$dispatch->register_endpoint( new Info(), 'info' );
	$dispatch->register_endpoint( new Version(), 'version' );
	$dispatch->register_endpoint( new Download(), 'download' );
	$dispatch->register_endpoint( new Product(), 'product' );
	$dispatch->register_endpoint( new Changelog(), 'changelog' );
} );

$factory  = new Factory();
$dispatch = $factory->make();

$dispatch->add_hooks();