<?php
/**
 * Store software upgrades.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB\Table;

use IronBound\DB\Table\Table;

/**
 * Class Upgrades
 * @package ITELIC\DB\Table
 */
class Updates implements Table {

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
			'ID'               => '%d',
			'activation'       => '%d',
			'release_id'       => '%d',
			'previous_version' => '%s',
			'update_date'     => '%s',
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
			'upgrade_date'     => '',
		);
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

		// todo figure out what indexes will be necessary

		return "CREATE TABLE {$tn} (
		ID BIGINT(20) NOT NULL AUTO_INCREMENT,
		activation BIGINT(20) UNSIGNED NOT NULL,
		release_id BIGINT(20) UNSIGNED NOT NULL,
		previous_version VARCHAR(20) NOT NULL,
		update_date DATETIME DEFAULT NULL,
		PRIMARY KEY  (ID),
		KEY release_id (release_id),
		KEY previous_version (previous_version),
		KEY update_date (update_date)
		) {$wpdb->get_charset_collate()};";
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