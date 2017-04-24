<?php
/**
 * DB for storing activations.
 *
 * Each time a site is activated, a new record should be inserted.
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
 * Class Activations
 *
 * @package ITELIC\DB\Table
 */
class Activations extends BaseTable {

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
			'id'           =>
				new IntegerBased( 'BIGINT', 'id', array( 'unsigned', 'NOT NULL', 'auto_increment' ), array( 20 ) ),
			'lkey'         => new ForeignModel( 'lkey', 'ITELIC\Key', Manager::get( 'itelic-keys' ) ),
			'location'     => new StringBased( 'VARCHAR', 'location', array( 'NOT NULL' ), array( 191 ) ),
			'status'       => new StringBased( 'VARCHAR', 'status', array( 'NOT NULL' ), array( 20 ) ),
			'activation'   => new DateTime( 'activation', array( 'NOT NULL' ) ),
			'deactivation' => new DateTime( 'deactivation' ),
			'release_id'   => new ForeignModel( 'release_id', 'ITELIC\Release', Manager::get( 'itelic-releases' ) ),
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
			'id'           => 0,
			'lkey'         => '',
			'location'     => '',
			'status'       => 'active',
			'activation'   => '',
			'deactivation' => '',
			'release_id'   => '',
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function get_keys() {
		$keys   = parent::get_keys();
		$keys[] = 'key__location (lkey,location)';

		return $keys;
	}

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
		return $wpdb->prefix . 'itelic_activations';
	}

	/**
	 * Get the slug of this table.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'itelic-activations';
	}

	/**
	 * Retrieve the name of the primary key.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'id';
	}

	/**
	 * Get version of this table.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_version() {
		return 1;
	}
}