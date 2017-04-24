<?php
/**
 * Abstract table for logs.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   MIT
 * @copyright Iron Bound Designs, 2015.
 */

namespace IronBound\DBLogger;

use IronBound\DB\Table\Table;

/**
 * Class AbstractTable
 * @package IronBound\DBLogger
 */
abstract class AbstractTable implements Table {

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
			'id'        => '%d',
			'message'   => '%s',
			'level'     => '%s',
			'lgroup'    => '%s',
			'time'      => '%s',
			'user'      => '%d',
			'ip'        => '%d',
			'exception' => '%s',
			'trace'     => '%s',
			'context'   => '%s'
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
			'id'        => '',
			'message'   => '',
			'level'     => '',
			'lgroup'    => '',
			'time'      => '',
			'user'      => '',
			'ip'        => '',
			'exception' => '',
			'trace'     => '',
			'context'   => ''
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
		{$this->get_column_definitions()}
		{$this->get_index_definitions()}
		) {$wpdb->get_charset_collate()};";
	}

	/**
	 * Get the column definitions.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected function get_column_definitions() {
		return "id BIGINT(20) NOT NULL AUTO_INCREMENT,
		message VARCHAR (255),
		level VARCHAR(20),
		lgroup VARCHAR (20),
		time DATETIME DEFAULT NULL,
		user BIGINT(20),
		ip BINARY(16),
		exception VARCHAR (255),
		trace LONGTEXT,
		context LONGTEXT,";
	}

	/**
	 * Get the index definitions.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected function get_index_definitions() {
		return "PRIMARY KEY  (id),
		KEY lgroup (lgroup),
		KEY user (user)";
	}
}