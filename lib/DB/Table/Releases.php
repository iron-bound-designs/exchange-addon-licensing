<?php
/**
 * DB table managing releases.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB\Table;
use IronBound\DB\Table\Table;

/**
 * Class Releases
 * @package ITELIC\DB\Table
 */
class Releases implements Table {

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
			'ID'         => '%d',
			'product'    => '%d',
			'download'   => '%d',
			'version'    => '%s',
			'status'     => '%s',
			'type'       => '%s',
			'changelog'  => '%s',
			'start_date' => '%s'
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

		return "CREATE TABLE {$tn} (
		ID bigint(20) unsigned NOT NULL auto_increment,
		product bigint(20) unsigned NOT NULL,
		download bigint(20) unsigned NOT NULL,
		version varchar(20) NOT NULL,
		status varchar(20) NOT NULL,
		type varchar(20) NOT NULL,
		changelog TEXT,
		start_date DATETIME,
		PRIMARY KEY  (ID),
		KEY product__version (product,version)
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