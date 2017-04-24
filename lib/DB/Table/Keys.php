<?php
/**
 * Database table for storing keys.
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
use IronBound\DB\Table\Column\ForeignPost;
use IronBound\DB\Table\Column\ForeignUser;
use IronBound\DB\Table\Column\IntegerBased;
use IronBound\DB\Table\Column\StringBased;

/**
 * Class Keys
 *
 * @package ITELIC\DB\Table
 */
class Keys extends BaseTable {

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
			'lkey'           => new StringBased( 'VARCHAR', 'lkey', array( 128 ), array( 'NOT NULL' ) ),
			'transaction_id' => new ForeignModel( 'transaction_id', 'IT_Exchange_Transaction', Manager::get( 'ite-transactions' ) ),
			'product'        => new ForeignPost( 'product' ),
			'customer'       => new ForeignUser( 'customer' ),
			'status'         => new StringBased( 'VARCHAr', 'status', array( 'NOT NULL' ), array( 20 ) ),
			'max'            => new IntegerBased( 'INTEGER', array( 'unsigned', 'NOT NULL' ) ),
			'expires'        => new DateTime( 'expires' )
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
			'lkey'           => '',
			'transaction_id' => 0,
			'product'        => 0,
			'customer'       => 0,
			'status'         => 'active',
			'max'            => 0,
			'expires'        => null
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function get_keys() {
		$keys   = parent::get_keys();
		$keys[] = 'customer (customer)';
		$keys[] = 'transaction_id (transaction_id)';
		$keys[] = 'expires (expires)';

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
		return $wpdb->prefix . 'itelic_keys';
	}

	/**
	 * Get the slug of this table.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'itelic-keys';
	}

	/**
	 * Retrieve the name of the primary key.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'lkey';
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