<?php
/**
 * Base DB File
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_DB_Base
 */
abstract class ITELIC_DB_Base {

	/**
	 * The name of our database table
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * The version of our database table
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * The name of the primary column
	 *
	 * @since 1.0
	 * @var string
	 */
	protected $primary_key;

	/**
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Get things started
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->wpdb = $GLOBALS['wpdb'];
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
	public abstract function get_columns();

	/**
	 * Default column values.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public abstract function get_column_defaults();

	/**
	 * Retrieve a row by the primary key
	 *
	 * @since 1.0
	 *
	 * @param string       $row_key
	 * @param array|string $columns
	 *
	 * @return object
	 */
	public function get( $row_key, $columns = '*' ) {

		$statement = $this->build_query( $columns, array( $this->primary_key => $row_key ), array(), 1 );

		return $this->wpdb->get_row( $statement );
	}

	/**
	 * Retrieve a row by a specific column / value
	 *
	 * @since 1.0
	 *
	 * @param string       $column Column name
	 * @param string       $value  Value for the column.
	 * @param string|array $columns
	 *
	 * @return object
	 */
	public function get_by( $column, $value, $columns = '*' ) {

		$statement = $this->build_query( $columns, array( $column => $value ), array(), 1 );

		return $this->wpdb->get_row( $statement );
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 *
	 * @since 1.0
	 *
	 * @param string $column
	 * @param string $row_key
	 *
	 * @return  string
	 */
	public function get_column( $column, $row_key ) {

		$statement = $this->build_query( $column, array( $this->primary_key => $row_key ), array(), 1 );

		return $this->wpdb->get_var( $statement );
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 *
	 * @since 1.0
	 *
	 * @param string $column Var to retrieve
	 * @param string $where
	 * @param string $value
	 *
	 * @return  string
	 */
	public function get_column_by( $column, $where, $value ) {

		$statement = $this->build_query( $column, array( $where => $value ), array(), 1 );

		return $this->wpdb->get_var( $statement );
	}

	/**
	 * Retrieve the number of rows matching a certain where clause
	 *
	 * @since 1.0
	 *
	 * @param array $where
	 *
	 * @return int
	 */
	public function count( $where = array() ) {

		$statement = $this->assemble_statement( "SELECT COUNT(*)", $this->translate_where( $where ) );

		return (int) $this->wpdb->get_var( $statement );
	}

	/**
	 * Insert a new row
	 *
	 * @since 1.0
	 *
	 * @param array $data
	 *
	 * @return mixed Insert ID
	 */
	public function insert( $data ) {
		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$this->wpdb->insert( $this->table_name, $data, $column_formats );

		return $this->wpdb->insert_id;
	}

	/**
	 * Update a row
	 *
	 * @since 1.0
	 *
	 * @param string $row_key
	 * @param array  $data
	 * @param array  $where
	 *
	 * @return  bool
	 */
	public function update( $row_key, $data, $where = array() ) {

		if ( empty( $row_key ) ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = array( $this->primary_key => $row_key );
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $this->wpdb->update( $this->table_name, $data, $where, $column_formats ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @since 1.0
	 *
	 * @param string $row_key
	 *
	 * @return  bool
	 */
	public function delete( $row_key ) {

		if ( empty( $row_key ) ) {
			return false;
		}

		$row_key = $this->escape_value( $this->primary_key, $row_key );

		if ( false === $this->wpdb->query( "DELETE FROM {$this->table_name} WHERE {$this->primary_key} = '$row_key'" ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Build a simple mysql query.
	 *
	 * @param string|array $select
	 * @param array        $wheres
	 * @param array        $orders
	 * @param int          $count
	 * @param int          $offset
	 *
	 * @return string
	 */
	public function build_query( $select = '*', $wheres = array(), $orders = array(), $count = null, $offset = null ) {

		$select   = $this->translate_select( $select );
		$where    = $this->translate_where( $wheres );
		$order_by = $this->translate_order_by( $orders );

		return $this->assemble_statement( $select, $where, $order_by, $count, $offset );
	}

	/**
	 * Assemble a sql statement.
	 *
	 * @param string $select
	 * @param string $where
	 * @param string $order_by
	 * @param int    $count
	 * @param int    $offset
	 *
	 * @return string
	 */
	public function assemble_statement( $select = '*', $where = '', $order_by = '', $count = null, $offset = null ) {

		$statement = "SELECT $select FROM {$this->table_name}";

		if ( ! empty( $where ) ) {
			$statement .= " WHERE $where";
		}

		if ( ! empty( $order_by ) ) {
			$statement .= " ORDER BY $order_by";
		}

		if ( $count !== null && $offset !== null ) {
			$statement .= " LIMIT $count, $offset";
		} elseif ( $count !== null && $offset === null ) {
			$statement .= " LIMIT $count";
		}

		return "$statement;";
	}

	/**
	 * Retrieve the fully qualified name of this table.
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Retrieve the primary key for this table.
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return $this->primary_key;
	}

	/**
	 * Escape a value using sprintf.
	 *
	 * @param string $column
	 * @param mixed  $value
	 *
	 * @return mixed
	 */
	protected function escape_value( $column, $value ) {

		$columns       = $this->get_columns();
		$column_format = $columns[ $column ];

		if ( $value[0] == '%' ) {
			$value = '%' . $value;
		}

		if ( $value[ strlen( $value ) - 1 ] == '%' ) {
			$value = $value . '%';
		}

		return sprintf( $column_format, $value );
	}

	/**
	 * Build the select statement.
	 *
	 * @param string|array $columns
	 *
	 * @return string
	 */
	public function translate_select( $columns ) {

		if ( $columns == '*' ) {
			return $columns;
		}

		return $this->implode( $columns );
	}

	/**
	 * Build the where statement.
	 *
	 * @param array[] $wheres [column => value]
	 * @param string  $mode   Either = or LIKE
	 *
	 * @return string
	 */
	public function translate_where( $wheres, $mode = '=' ) {

		if ( ! in_array( $mode, array( '=', 'LIKE' ) ) ) {
			$mode = '=';
		}

		$statements = array();

		foreach ( $wheres as $column => $value ) {

			$value = $this->escape_value( $column, $value );

			$statements[] = "$column $mode '$value'";
		}

		return implode( ' AND ', $statements );
	}

	/**
	 * Build the order by statement.
	 *
	 * @param array $orders [column => type (ASC|DESC)]
	 *
	 * @return string
	 */
	public function translate_order_by( $orders ) {

		$statements = array();

		foreach ( $orders as $column => $order ) {

			$order = strtoupper( $order );

			if ( $order != 'ASC' ) {
				$order = 'DESC';
			}

			$statements[] = "$column $order";
		}

		return $this->implode( $statements );
	}

	/**
	 * Implode an array if necessary.
	 *
	 * @param string|array $values
	 *
	 * @return string
	 */
	private function implode( $values ) {
		if ( is_array( $values ) ) {
			return implode( ', ', $values );
		} else {
			return $values;
		}
	}
}