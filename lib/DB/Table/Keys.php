<?php
/**
 * Database table for storing keys.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB\Table;
use IronBound\DB\Table\Table;

/**
 * Class Keys
 * @package ITELIC\DB\Table
 */
class Keys implements Table {

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
			'lkey'           => '%s',
			'transaction_id' => '%d',
			'product'        => '%d',
			'customer'       => '%d',
			'status'         => '%s',
			'max'            => '%d',
			'expires'        => '%s'
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
			'expires'        => ''
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
		lkey VARCHAR(255) NOT NULL,
		transaction_id BIGINT(20) UNSIGNED NOT NULL,
		product BIGINT(20) UNSIGNED NOT NULL,
		customer BIGINT(20) UNSIGNED NOT NULL,
		status VARCHAR(255) NOT NULL,
		max INTEGER UNSIGNED NOT NULL,
		expires DATETIME,
		PRIMARY KEY  (lkey),
		KEY customer (customer),
		KEY transaction_id (transaction_id),
		KEY expires (expires)
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