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
use ITELIC\API\Endpoint\Deactivate;
use ITELIC\API\Endpoint\Download;
use ITELIC\API\Endpoint\Info;
use ITELIC\API\Endpoint\Product;
use ITELIC\API\Endpoint\Version;
use ITELIC\API\Responder\JSON_Responder;

$dispatch = new Dispatch();

Dispatch::register_endpoint( new Activate(), 'activate' );
Dispatch::register_endpoint( new Deactivate(), 'deactivate' );
Dispatch::register_endpoint( new Info(), 'info' );
Dispatch::register_endpoint( new Version(), 'version' );
Dispatch::register_endpoint( new Download(), 'download' );
Dispatch::register_endpoint( new Product(), 'product' );

$dispatch->set_responder( new JSON_Responder() );