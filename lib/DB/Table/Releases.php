<?php
/**
 * DB table managing releases.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\DB\Table;

use IronBound\DB\Table\BaseTable;
use IronBound\DB\Table\Column\DateTime;
use IronBound\DB\Table\Column\Enum;
use IronBound\DB\Table\Column\ForeignPost;
use IronBound\DB\Table\Column\IntegerBased;
use IronBound\DB\Table\Column\StringBased;
use ITELIC\Release;

/**
 * Class Releases
 *
 * @package ITELIC\DB\Table
 */
class Releases extends BaseTable {

	/**
	 * Retrieve the name of the database table.
	 *
	 * @since 1.0
	 *
	 * @param \wpdb $wpdb
	 *
	 * @return string
	 */
	public function get_table_name( \wpdb $wpdb ) {
		return $wpdb->prefix . 'itelic_releases';
	}

	/**
	 * Get the slug of this table.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'itelic-releases';
	}

	/**
	 * Columns in the table.
	 *
	 * key => sprintf field type
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'ID'         =>
				new IntegerBased( 'BIGINT', 'ID', array( 'unsigned', 'NOT NULL', 'auto_increment' ), array( 20 ) ),
			'product'    => new ForeignPost( 'product' ),
			'download'   => new ForeignPost( 'download' ),
			'version'    => new StringBased( 'VARCHAR', 'version', array( 'NOT NULL' ), array( 20 ) ),
			'status'     => new Enum( array_keys( Release::get_statuses() ), 'status', 'draft', false ),
			'type'       => new StringBased( 'VARCHAR', 'type', array( 'NOT NULL' ), array( 20 ) ),
			'changelog'  => new StringBased( 'TEXT', 'changelog' ),
			'start_date' => new DateTime( 'start_date' ),
		);
	}

	/**
	 * Default column values.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'ID'         => '',
			'product'    => '',
			'download'   => '',
			'version'    => '',
			'status'     => 'draft',
			'type'       => 'major',
			'changelog'  => '',
			'start_date' => ''
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function get_keys() {
		$keys   = parent::get_keys();
		$keys[] = 'product__version (product,version)';

		return $keys;
	}

	/**
	 * Retrieve the name of the primary key.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'ID';
	}

	/**
	 * Retrieve the version number of the current table schema as written.
	 *
	 * The version should be incremented by 1 for each change.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_version() {
		return 1;
	}
}