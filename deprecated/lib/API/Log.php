<?php
/**
 * Log model for API logs.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API;

use IronBound\DBLogger\AbstractLog;
use IronBound\DBLogger\Table;

/**
 * Class Log
 * @package ITELIC\API
 */
class Log extends AbstractLog {

	/**
	 * Get the table object for this model.
	 *
	 * @since 1.0
	 *
	 * @returns Table
	 */
	protected static function get_table() {
		return new Table( 'itelic-api-logs' );
	}
}