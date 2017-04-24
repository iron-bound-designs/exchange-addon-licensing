<?php
/**
 * Activation Meta Table
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\DB\Table;

use IronBound\DB\Table\Table;

/**
 * Class Activation_Meta
 *
 * @package ITELIC\DB\Table
 */
class Activation_Meta implements Table {

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
		return $wpdb->prefix . 'itelic_activationmeta';
	}

	/**
	 * Get the slug of this table.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'itelic-activation-meta';
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
			'meta_id'       => '%d',
			'activation_id' => '%d',
			'meta_key'      => '%s',
			'meta_value'    => '%s'
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
			'meta_id'       => 0,
			'activation_id' => 0,
			'meta_key'      => '',
			'meta_value'    => ''
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
		return 'meta_id';
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
		meta_id bigint(20) unsigned NOT NULL auto_increment,
		activation_id bigint(20) unsigned NOT NULL,
		meta_key varchar(255) NOT NULL,
		meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY activation_id (activation_id),
		KEY meta_key (meta_key(191))
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