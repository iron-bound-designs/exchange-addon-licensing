<?php
/**
 * Factory for generating an API dispatcher.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API;

use IronBound\DB\Query\Simple_Query;
use IronBound\DBLogger\Logger;
use IronBound\DBLogger\Table;
use ITELIC\API\Responder\JSON_Responder;

/**
 * Class Factory
 * @package ITELIC\API
 */
class Factory {

	/**
	 * Make an API Dispatcher.
	 *
	 * @return \ITELIC\API\Dispatch
	 */
	public function make() {

		$dispatch = new Dispatch();
		$dispatch->set_responder( new JSON_Responder() );

		/**
		 * Filter the API dispatcher.
		 *
		 * If the filtered dispatcher is not a subclass of \ITELIC\API\Dispatch,
		 * the original dispatcher will be used.
		 *
		 * @since 1.0
		 *
		 * @param \ITELIC\API\Dispatch $dispatch
		 */
		$filtered = apply_filters( 'itelic_api_dispatcher', $dispatch );

		if ( $filtered instanceof Dispatch ) {
			$dispatch = $filtered;
		}

		$dispatch->setLogger( new Logger(
				new Table( 'itelic-api-logs' ),
				new Simple_Query( $GLOBALS['wpdb'], new Table( 'itelic-api-logs' ) )
		) );

		/**
		 * Fires when custom API endpoints should be registered with the dispatcher.
		 *
		 * @since 1.0
		 *
		 * @param \ITELIC\API\Dispatch $dispatch
		 */
		do_action( 'itelic_api_register_endpoints', $dispatch );

		return $dispatch;
	}
}