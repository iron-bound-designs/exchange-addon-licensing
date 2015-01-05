<?php

/**
 * DB for storing activations.
 *
 * Each time a site is activated, a new record should be inserted.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
class ITELIC_DB_Activations extends ITELIC_DB_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->version     = 1.0;
		$this->primary_key = 'key';
		$this->table_name  = $this->wpdb->prefix . 'itelic_activations';
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
			'id'           => '%d',
			'lkey'         => '%s',
			'location'     => '%s',
			'status'       => '%s',
			'activation'   => '%s',
			'deactivation' => '%s'
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
			'deactivation' => ''
		);
	}

	/**
	 * Create the db table.
	 */
	public function create() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
		id INT NOT NULL AUTO_INCREMENT,
		lkey VARCHAR(255) NOT NULL,
		location VARCHAR(255) NOT NULL,
		activation DATETIME NOT NULL,
		deactivation DATETIME,
		status VARCHAR(255) NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY id (id),
		INDEX lkey (lkey)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}


}