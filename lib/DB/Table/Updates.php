<?php
/**
 * Store software upgrades.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\DB\Table;

use IronBound\DB\Manager;
use IronBound\DB\Table\BaseTable;
use IronBound\DB\Table\Column\DateTime;
use IronBound\DB\Table\Column\ForeignModel;
use IronBound\DB\Table\Column\IntegerBased;
use IronBound\DB\Table\Column\StringBased;

/**
 * Class Upgrades
 *
 * @package ITELIC\DB\Table
 */
class Updates extends BaseTable {

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
		return $wpdb->prefix . 'itelic_updates';
	}

	/**
	 * Get the slug of this table.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'itelic-updates';
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
			'ID'               =>
				new IntegerBased( 'BIGINT', 'ID', array( 'NOT NULL', 'auto_increment' ), array( 20 ) ),
			'activation'       => new ForeignModel( 'activation', 'ITELIC\Activation', Manager::get( 'itelic-activations' ) ),
			'release_id'       => new ForeignModel( 'release_id', 'ITELIC\Release', Manager::get( 'itelic-releases' ) ),
			'previous_version' => new StringBased( 'VARCHAR', 'previous_version', array( 'NOT NULL' ), array( 20 ) ),
			'update_date'      => new DateTime( 'update_date' ),
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
			'ID'               => '',
			'activation'       => '',
			'release_id'       => '',
			'previous_version' => '',
			'update_date'      => '',
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function get_keys() {
		$keys   = parent::get_keys();
		$keys[] = 'release_id (release_id)';
		$keys[] = 'previous_version (previous_version)';
		$keys[] = 'update_date (update_date)';

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