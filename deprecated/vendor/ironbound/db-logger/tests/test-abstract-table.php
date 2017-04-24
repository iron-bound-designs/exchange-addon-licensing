<?php
/**
 * Test the abstract table class.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   MIT
 * @copyright Iron Bound Designs, 2015.
 */

namespace IronBound\DBLogger\Tests;

/**
 * Class Test_Abstract_Table
 * @package IronBound\DBLogger\Tests
 */
class Test_Abstract_Table extends \WP_UnitTestCase {

	/**
	 * @dataProvider _columns_provider
	 */
	public function test_columns( $column ) {

		$stub = $this->getMockForAbstractClass( 'IronBound\DBLogger\AbstractTable' );

		$columns = $stub->get_columns();

		$this->assertArrayHasKey( $column, $columns );
	}

	public function _columns_provider() {
		return array(
			array( 'id' ),
			array( 'level' ),
			array( 'message' ),
			array( 'lgroup' ),
			array( 'time' ),
			array( 'user' ),
			array( 'ip' ),
			array( 'exception' ),
			array( 'trace' ),
			array( 'context' )
		);
	}

	public function test_primary_key_is_id() {

		$stub = $this->getMockForAbstractClass( 'IronBound\DBLogger\AbstractTable' );

		$this->assertEquals( 'id', $stub->get_primary_key() );
	}

	public function test_creation_sql_is_valid() {

		/** @var \wpdb $wpdb */
		global $wpdb;

		$stub = $this->getMockForAbstractClass( 'IronBound\DBLogger\AbstractTable' );
		$stub->expects( $this->any() )->method( 'get_table_name' )->willReturn( $wpdb->prefix . 'abstract_table' );

		$sql = $stub->get_creation_sql( $wpdb );

		$this->assertTrue( $wpdb->query( $sql ) );
	}

	/**
	 * @dataProvider _column_types_provider
	 */
	public function test_column_types( $column, $type ) {

		/** @var \wpdb $wpdb */
		global $wpdb;

		$stub = $this->getMockForAbstractClass( 'IronBound\DBLogger\AbstractTable' );
		$stub->expects( $this->any() )->method( 'get_table_name' )->willReturn( $wpdb->prefix . 'abstract_table' );
		$wpdb->query( $stub->get_creation_sql( $wpdb ) );

		// load the column meta
		$wpdb->get_col_length( $wpdb->prefix . 'abstract_table', 'id' );
		$types = $wpdb->col_meta[ $wpdb->prefix . 'abstract_table' ];

		$this->assertArrayHasKey( $column, $types );
		$this->assertEquals( $types[ $column ]->Type, $type );
	}

	public function _column_types_provider() {
		return array(
			array( 'id', 'bigint(20)' ),
			array( 'level', 'varchar(20)' ),
			array( 'message', 'varchar(255)' ),
			array( 'lgroup', 'varchar(20)' ),
			array( 'time', 'datetime' ),
			array( 'user', 'bigint(20)' ),
			array( 'ip', 'binary(16)' ),
			array( 'exception', 'varchar(255)' ),
			array( 'trace', 'longtext' ),
			array( 'context', 'longtext' )
		);
	}

}