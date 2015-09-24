<?php
/**
 * DB for storing renewal records.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB\Table;

use IronBound\DB\Table\Table;

/**
 * Class Renewals
 * @package ITELIC\DB\Table
 */
class Renewals implements Table {

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
			'id'               => '%d',
			'lkey'             => '%s',
			'renewal_date'     => '%s',
			'key_expired_date' => '%s',
			'transaction_id'   => '%d',
			'revenue'          => '%f'
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
	 * Get creation SQL.
	 *
	 * @since 1.0
	 *
	 * @param \wpdb $wpdb
	 *
	 * @return string
	 */
	public function get_creation_sql( \wpdb $wpdb ) {
		$tn = $this->get_table_name( $wpdb );

		return "CREATE TABLE {$tn} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		lkey VARCHAR(255) NOT NULL,
		renewal_date DATETIME NOT NULL,
		key_expired_date DATETIME,
		transaction_id BIGINT(20) NOT NULL,
		revenue DECIMAL(10,2) NOT NULL,
		PRIMARY KEY  (id),
		KEY key_to_dates (lkey,renewal_date,key_expired_date)
		) {$wpdb->get_charset_collate()};";
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