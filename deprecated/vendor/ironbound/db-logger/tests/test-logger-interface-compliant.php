<?php
/**
 * Test the logger interface.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   MIT
 * @copyright Iron Bound Designs, 2015.
 */

namespace IronBound\DBLogger\Tests;

use IronBound\DB\Manager;
use IronBound\DB\Query\Simple_Query;
use IronBound\DBLogger\Logger;
use IronBound\DBLogger\Table;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\LoggerInterfaceTest;

/**
 * Class Test_Logger_Interface_Compliant
 * @package IronBound\DBLogger\Tests
 */
class Test_Logger_Interface_Compliant extends LoggerInterfaceTest {

	/**
	 * @var Table
	 */
	private static $table;

	/**
	 * This method is called before the first test of this test class is run.
	 *
	 * @since Method available since Release 3.4.0
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		self::$table = new Table( 'test-log' );

		Manager::maybe_install_table( self::$table );
	}

	/**
	 * Sets up the fixture, for example, open a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );
		$wpdb->query( 'START TRANSACTION;' );
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Tears down the fixture, for example, close a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		parent::tearDown();

		global $wpdb;

		$wpdb->query( 'ROLLBACK' );

		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	function _create_temporary_tables( $query ) {
		if ( 'CREATE TABLE' === substr( trim( $query ), 0, 12 ) )
			return substr_replace( trim( $query ), 'CREATE TEMPORARY TABLE', 0, 12 );
		return $query;
	}

	function _drop_temporary_tables( $query ) {
		if ( 'DROP TABLE' === substr( trim( $query ), 0, 10 ) )
			return substr_replace( trim( $query ), 'DROP TEMPORARY TABLE', 0, 10 );
		return $query;
	}

	/**
	 * @return LoggerInterface
	 */
	function getLogger() {
		return new Logger( self::$table, new Simple_Query( $GLOBALS['wpdb'], self::$table ) );
	}

	/**
	 * This must return the log messages in order with a simple formatting: "<LOG LEVEL> <MESSAGE>"
	 *
	 * Example ->error('Foo') would yield "error Foo"
	 *
	 * @return string[]
	 */
	function getLogs() {

		/** @var \wpdb $wpdb */
		global $wpdb;

		$tn = self::$table->get_table_name( $wpdb );

		$results = $wpdb->get_results( "SELECT message, level FROM {$tn}" );

		$logs = array();

		foreach ( $results as $result ) {
			$logs[] = $result->level . ' ' . $result->message;
		}

		return $logs;
	}

}