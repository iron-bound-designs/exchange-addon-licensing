<?php
/**
 * Test the logger.
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
use Psr\Log\LogLevel;

/**
 * Class Test_Logger
 * @package IronBound\DBLogger\Tests
 */
class Test_Logger extends \WP_UnitTestCase {

	public function test_exception_converted_to_trace_and_class() {

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$exception = new \LogicException();

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'exception' => get_class( $exception ),
				'trace'     => $exception->getTraceAsString(),
				'context'   => wp_json_encode( array() )
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message', array(
			'exception' => $exception
		) );
	}

	public function test_context_is_saved_as_json() {

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$context = array(
			'this' => 'that',
			'list' => array(
				'a',
				'b',
				'c'
			)
		);

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'context' => wp_json_encode( $context )
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message', $context );
	}

	public function test_current_time_is_saved() {

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'time' => date( 'Y-m-d H:i:s' )
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message' );
	}

	public function test_current_user_saved() {

		wp_set_current_user( 1 );

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'user' => get_current_user_id()
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message' );
	}

	public function test_custom_user_value_takes_priority_over_current_user() {

		wp_set_current_user( 1 );

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'user' => 2
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message', array( '_user' => 2 ) );
	}

	public function test_current_user_not_saved_if_false_user_passed_as_context() {

		wp_set_current_user( 1 );

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'user' => false
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message', array( '_user' => false ) );
	}

	public function test_current_user_not_saved_if_user_not_logged_in() {

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalNot(
			$this->arrayHasKey( 'user' )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message' );
	}

	public function test_group_saved() {

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'lgroup' => 'my-group'
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message', array( '_group' => 'my-group' ) );
	}

	public function test_additional_columns_saved_if_passed_as_context_and_valid_column() {

		$table = $this->getMockBuilder( '\IronBound\DBLogger\AbstractTable' )->setMethods( array(
			'get_columns'
		) )->getMockForAbstractClass();
		$table->expects( $this->once() )->method( 'get_columns' )->willReturn( array(
			'custom_column' => '%s'
		) );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'custom_column' => 'custom_value'
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message', array( '_custom_column' => 'custom_value' ) );
	}

	public function test_additional_columns_not_saved_if_passed_as_context_and_invalid_column() {

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalNot(
			$this->arrayHasKey( 'custom_column' )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'My message', array( '_custom_column' => 'custom_value' ) );
	}

	public function test_context_replaced_with_object_to_string() {

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'message' => 'Message DUMMY'
			) )
		) );

		$dummy = $this->getMock( 'stdClass', array( '__toString' ) );
		$dummy->expects( $this->once() )
		      ->method( '__toString' )
		      ->will( $this->returnValue( 'DUMMY' ) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'Message {context}', array(
			'context' => $dummy
		) );
	}

	public function test_context_replaced_with_class_name_if_no_to_string_method() {

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'message' => 'Message (stdClass)'
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'Message {context}', array(
			'context' => new \stdClass()
		) );
	}

	public function test_context_replaced_with_word_Array_if_array() {

		$table = $this->getMockForAbstractClass( '\IronBound\DBLogger\AbstractTable' );

		$query = $this->getMockBuilder( '\IronBound\DB\Query\Simple_Query' )->disableOriginalConstructor()->getMock();
		$query->expects( $this->once() )->method( 'insert' )->with( $this->logicalAnd(
			new \PHPUnit_Framework_Constraint_ArraySubset( array(
				'message' => 'Message (Array)'
			) )
		) );

		$log = new Logger( $table, $query );
		$log->log( LogLevel::DEBUG, 'Message {context}', array(
			'context' => array()
		) );
	}

	public function test_purge() {

		/** @var \wpdb $wpdb */
		global $wpdb;

		$table = $this->getMockBuilder( '\IronBound\DBLogger\AbstractTable' )
		              ->setMethods( array( 'get_slug', 'get_table_name', 'get_version' ) )
		              ->getMockForAbstractClass();
		$table->method( 'get_slug' )->willReturn( 'test_logs' );
		$table->method( 'get_table_name' )->willReturn( "{$wpdb->prefix}test_logs" );
		$table->method( 'get_version' )->willReturn( 1 );

		Manager::maybe_install_table( $table );

		$tn = $table->get_table_name( $wpdb );

		$wpdb->insert( $tn, array(
			'message' => 'a',
			'time'    => date( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS )
		) );

		$wpdb->insert( $tn, array(
			'message' => 'b',
			'time'    => date( 'Y-m-d H:i:s' )
		) );

		$logger = new Logger( $table, new Simple_Query( $wpdb, $table ) );
		$logger->purge( 2, $wpdb );

		$a = $wpdb->get_results( "SELECT * FROM $tn WHERE message = 'a'" );
		$b = $wpdb->get_results( "SELECT * FROM $tn WHERE message = 'b'" );

		$this->assertEmpty( $a );
		$this->assertEquals( 1, count( $b ) );
	}
}