<?php
/**
 * DB for storing renewal records.
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
use IronBound\DB\Table\Column\DecimalBased;
use IronBound\DB\Table\Column\ForeignModel;
use IronBound\DB\Table\Column\IntegerBased;

/**
 * Class Renewals
 *
 * @package ITELIC\DB\Table
 */
class Renewals extends BaseTable {

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
			'id'               =>
				new IntegerBased( 'BIGINT', 'id', array( 'unsigned', 'NOT NULL', 'auto_increment' ), array( 20 ) ),
			'lkey'             => new ForeignModel( 'lkey', 'ITELIC\Key', Manager::get( 'itelic-keys' ) ),
			'renewal_date'     => new DateTime( 'renewal_date', array( 'NOT NULL' ) ),
			'key_expired_date' => new DateTime( 'key_expired_date' ),
			'transaction_id'   => new ForeignModel( 'transaction_id', 'IT_Exchange_Transaction', Manager::get( 'ite-transactions' ) ),
			'revenue'          => new DecimalBased( 'DECIMAL', 'revenue', array( 'NOT NULL' ), array( 10, 2 ) ),
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
			'id'               => 0,
			'lkey'             => '',
			'renewal_date'     => '',
			'key_expired_date' => '',
			'transaction_id'   => 0,
			'revenue'          => '0.00'
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function get_keys() {
		$keys   = parent::get_keys();
		$keys[] = 'key_to_dates (lkey,renewal_date,key_expired_date)';

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
		return $wpdb->prefix . 'itelic_renewals';
	}

	/**
	 * Get the slug of this table.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'itelic-renewals';
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