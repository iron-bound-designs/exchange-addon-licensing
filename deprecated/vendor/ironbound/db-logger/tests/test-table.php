<?php
/**
 * Test the concrete table.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   MIT
 * @copyright Iron Bound Designs, 2015.
 */

namespace IronBound\DBLogger\Tests;

use IronBound\DBLogger\Table;

/**
 * Class Test_Table
 * @package IronBound\DBLogger\Tests
 */
class Test_Table extends \WP_UnitTestCase {

	public function test_slug() {

		$table = new Table( 'my-slug' );

		$this->assertEquals( 'my-slug', $table->get_slug() );
	}

	public function test_dashes_replaced_when_generating_table_name() {

		$table = new Table( 'my-slug' );

		$prefix = $GLOBALS['wpdb']->prefix;

		$this->assertEquals( "{$prefix}my_slug", $table->get_table_name( $GLOBALS['wpdb'] ) );
	}

	public function test_initial_version() {

		$table = new Table( 'my-slug' );

		$this->assertEquals( 1, $table->get_version() );
	}
}