<?php

/**
 * Database table for storing keys.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
class ITELIC_DB_Keys extends ITELIC_DB_Base {

	/**
	 * @var ITELIC_DB_Keys|null
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->version     = 1.0;
		$this->primary_key = 'lkey';
		$this->table_name  = $this->wpdb->prefix . 'itelic_keys';
	}

	/**
	 * Retrieve an instance of the db.
	 *
	 * @return ITELIC_DB_Keys
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new ITELIC_DB_Keys();
		}

		return self::$instance;
	}

	/**
	 * Retrieve all licenses.
	 *
	 * @return object
	 */
	public static function all() {

		$db = self::instance();

		$query = $db->build_query();

		return $db->wpdb->get_results( $query );
	}

	/**
	 * Retrieve information about a license key.
	 *
	 * @param string $key
	 *
	 * @return object
	 */
	public static function retrieve( $key ) {

		$db = self::instance();

		return $db->get( $key );
	}

	/**
	 * Find the first matching value.
	 *
	 * @param string $col Column to find a license by.
	 * @param string $val Value of that column.
	 *
	 * @return object
	 */
	public static function find( $col, $val ) {

		$db = self::instance();

		return $db->get_by( $col, $val );
	}

	/**
	 * Find many keys by a certain column or value.
	 *
	 * @param string $col Column to find licenses by.
	 * @param string $val Value of that column.
	 *
	 * @return array
	 */
	public static function many( $col, $val ) {

		$db = self::instance();

		$query = $db->build_query( "*", array( $col => $val ) );

		return $db->wpdb->get_results( $query );
	}

	/**
	 * Search for license keys by multiple values.
	 *
	 * @param array $where
	 *
	 * @return array
	 */
	public static function search( $where ) {

		$db = self::instance();

		$query = $db->build_query( '*', $where );

		return $db->wpdb->get_results( $query );
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
			'lkey'           => '%s',
			'transaction_id' => '%d',
			'product'        => '%d',
			'customer'       => '%d',
			'status'         => '%s',
			'count'          => '%d',
			'max'            => '%d'
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
			'count'          => 0,
			'max'            => 0
		);
	}

	/**
	 * Create the db table.
	 */
	public function create() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
		lkey VARCHAR(255) NOT NULL PRIMARY KEY,
		transaction_id INTEGER UNSIGNED NOT NULL,
		product INTEGER UNSIGNED NOT NULL,
		customer INTEGER UNSIGNED NOT NULL,
		status VARCHAR(255) NOT NULL,
		count INTEGER UNSIGNED NOT NULL,
		max INTEGER UNSIGNED NOT NULL,
		INDEX customer (customer),
		INDEX transaction_id (transaction_id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	/**
	 * Get the installed version of this table.
	 *
	 * @since 1.0
	 *
	 * @return float
	 */
	public function get_installed_version() {
		return (float) get_option( $this->table_name . '_db_version' );
	}

	/**
	 * Get version of this table.
	 *
	 * @since 1.0
	 *
	 * @return float
	 */
	public function get_version() {
		return $this->version;
	}
}