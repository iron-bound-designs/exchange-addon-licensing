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
	 * @var ITELIC_DB_Activations|null
	 */
	protected static $instance = null;

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
	 * Retrieve an instance of the db.
	 *
	 * @return ITELIC_DB_Activations
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new ITELIC_DB_Activations();
		}

		return self::$instance;
	}

	/**
	 * Retrieve all activations.
	 *
	 * @return object
	 */
	public static function all() {

		$db = self::instance();

		$query = $db->build_query();

		return $db->wpdb->get_results( $query );
	}

	/**
	 * Retrieve information about an activation.
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
	 * Find many activations by a certain column or value.
	 *
	 * @param string $col Column to find licenses by.
	 * @param string $val Value of that column.
	 *
	 * @return object
	 */
	public static function many( $col, $val ) {

		$db = self::instance();

		$query = $db->build_query( "*", array( $col => $val ) );

		return $db->wpdb->get_results( $query );
	}

	/**
	 * Search for activations by multiple values.
	 *
	 * @param array $where
	 *
	 * @return object
	 */
	public static function search( $where ) {

		$db = self::instance();

		$query = $db->build_query( '*', $where );

		return $db->wpdb->get_results( $query );
	}

	/**
	 * Search for keys by date.
	 *
	 * @param string $column (activation|deactivation)
	 * @param array  $query  Query to be passed to WP_Date_Query
	 * @param array  $where  Additional where clauses.
	 *
	 * @return mixed
	 */
	public static function search_by_date( $column, $query, $where = array() ) {

		if ( ! in_array( $column, array( 'activation', 'deactivation' ) ) ) {
			throw new InvalidArgumentException( "Invalid date type" );
		}

		$db = self::instance();

		$date_query = new WP_Date_Query( $query, $column );
		$where_sql  = $date_query->get_sql();

		if ( ! empty( $where ) ) {
			$where_sql .= ' AND ' . $db->translate_where( $where );
		}

		$sql = $db->assemble_statement( '*', $where_sql );

		return $db->wpdb->get_results( $sql );
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