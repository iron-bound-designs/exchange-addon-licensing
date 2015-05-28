<?php
/**
 * Base query object for other queries.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC_API\Query;

use ITELIC\DB\Manager;
use ITELIC\DB\Query\Tag\Limit;
use ITELIC\DB\Query\Tag\Order;
use ITELIC\DB\Query\Tag\Select;
use ITELIC\DB\Query\Tag\Where;
use ITELIC\DB\Table\Base as Table;

/**
 * Class Base
 * @package ITELIC\API\Query
 */
abstract class Base {

	/**
	 * @var array
	 */
	protected $args = array();

	/**
	 * @var \ITELIC\DB\Query\Query|null
	 */
	protected $db_query;

	/**
	 * @var Table
	 */
	protected $table;

	/**
	 * @var string
	 */
	protected $sql;

	/**
	 * @var int|null
	 */
	protected $total_items = null;

	/**
	 * @var array
	 */
	protected $results = array();

	/**
	 * Constructor.
	 *
	 * @param Table $table
	 * @param array $args
	 */
	public function __construct( Table $table, array $args = array() ) {

		$this->table    = $table;
		$this->db_query = Manager::make_query_object( $table->get_slug() );

		$this->args = wp_parse_args( $args, $this->get_default_args() );

		if ( $this->args['items_per_page'] != '-1' && $this->args['sql_calc_found_rows'] === null ) {
			$this->args['sql_calc_found_rows'] = true;
		}

		$this->sql = $this->build_sql();

		$this->query();
	}

	/**
	 * Get the total items found ignoring pagination.
	 *
	 * If sql_calc_found_rows is set to false, the return value will be null.
	 *
	 * @since 1.0
	 *
	 * @return int|null
	 */
	public function get_total_items() {
		return $this->total_items;
	}

	/**
	 * Retrieve the queried results.
	 *
	 * @since 1.0
	 *
	 * @return object[]|\stdClass[]|mixed[]
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Debug function.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function _get_sql() {
		return $this->sql;
	}

	/**
	 * Get the default args.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_default_args() {
		return array(
			'order'               => array(),
			'items_per_page'      => - 1,
			'page'                => 1,
			'sql_calc_found_rows' => null,
			'return_value'        => 'object',
			'distinct'            => false
		);
	}

	/**
	 * Get a default arg.
	 *
	 * @since 1.0
	 *
	 * @param string $arg
	 *
	 * @return mixed
	 */
	protected function get_default_arg( $arg ) {

		$args = $this->get_default_args();

		if ( isset( $args[ $arg ] ) ) {
			return $args[ $arg ];
		} else {
			throw new \InvalidArgumentException();
		}
	}

	/**
	 * Query the database and store the results.
	 *
	 * @since 1.0
	 */
	protected function query() {
		$results = $GLOBALS['wpdb']->get_results( $this->sql );

		// we query for found rows first to prevent instantiation of record objects from interfering with the count
		if ( $this->args['sql_calc_found_rows'] ) {

			$count_results     = $GLOBALS['wpdb']->get_results( "SELECT FOUND_ROWS() AS COUNT" );
			$this->total_items = $count_results[0]->COUNT;
		}

		if ( is_array( $this->args['return_value'] ) ) {
			$this->results = $results;
		} elseif ( $this->args['return_value'] != 'object' ) {
			$values = array();
			$field  = $this->args['return_value'];

			foreach ( $results as $result ) {
				$values[] = $result->$field;
			}

			$this->results = $values;
		} else {
			$records = array();

			foreach ( $results as $result ) {
				$records[ $result->{$this->table->get_primary_key()} ] = $this->make_object( $result );
			}

			$this->results = $records;
		}
	}

	/**
	 * Build the sql query.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected abstract function build_sql();

	/**
	 * Convert data to its object.
	 *
	 * @since 1.0
	 *
	 * @param \stdClass $data
	 *
	 * @return object
	 */
	protected abstract function make_object( $data );

	/**
	 * Build the select query.
	 *
	 * @since 1.0
	 *
	 * @return Select
	 */
	protected function parse_select() {

		if ( is_array( $this->args['return_value'] ) ) {
			$select = new Select( null );

			foreach ( $this->args['return_value'] as $column ) {
				$select->also( "q.$column" );
			}
		} elseif ( $this->args['return_value'] != 'object' ) {
			$select = new Select( 'q.' . $this->args['return_value'] );
		} else {
			$select = new Select( 'q.*' );
		}

		if ( $this->args['sql_calc_found_rows'] ) {
			$select->calc_found_rows();
		}

		$select->filter_distinct( $this->args['distinct'] );

		return $select;
	}

	/**
	 * Generic Where builder for queries that follow the in/not_in pattern.
	 *
	 * @since 1.0
	 *
	 * @param string $column
	 * @param array  $in
	 * @param array  $not_in
	 *
	 * @return Where|null
	 */
	protected function parse_in_or_not_in_query( $column, array $in, array $not_in ) {

		if ( ! empty( $in ) ) {

			foreach ( $in as $key => $value ) {
				$in[ $key ] = $this->db_query->escape_value( $column, $value );
			}

			$in_where = new Where( $column, true, $in );
		}

		if ( ! empty( $not_in ) ) {

			foreach ( $not_in as $key => $value ) {
				$not_in[ $key ] = $this->db_query->escape_value( $column, $value );
			}

			$not_where = new Where( $column, false, $not_in );

			if ( isset( $in_where ) ) {
				$in_where->qAnd( $not_where );

				return $in_where;
			} else {
				return $not_where;
			}
		}

		if ( isset( $in_where ) ) {
			return $in_where;
		} else {
			return null;
		}
	}

	/**
	 * Parse the orderby query. There is always a default or.
	 *
	 * @since 1.0
	 *
	 * @return Order
	 */
	protected function parse_order() {

		if ( ! is_array( $this->args['order'] ) && $this->args['order'] === 'rand' ) {
			return new Order( Order::RAND );
		} elseif ( ! is_array( $this->args['order'] ) ) {
			throw new \InvalidArgumentException( "Order must either be 'rand' or an array of columns to directions." );
		}

		$white_list = $this->table->get_columns();

		foreach ( $this->args['order'] as $column => $direction ) {

			$direction = strtoupper( $direction );

			if ( ! in_array( $direction, array( Order::ASC, Order::DESC ) ) ) {
				throw new \InvalidArgumentException( "Invalid order direction $direction for column $column." );
			}

			$column = $this->translate_order_by_to_column_name( $column );

			if ( ! isset( $white_list[ $column ] ) ) {
				throw new \InvalidArgumentException( "Invalid order column $column." );
			}

			if ( ! isset( $order ) ) {
				$order = new Order( $column, $direction );
			} else {
				$order->then( $column, $direction );
			}
		}

		if ( isset( $order ) ) {
			return $order;
		} else {
			return new Order( $this->table->get_primary_key(), Order::ASC );
		}
	}

	/**
	 * Translate a human given order by, to its corresponding column name.
	 *
	 * @since 1.0
	 *
	 * @param string $order_by
	 *
	 * @return string
	 */
	protected function translate_order_by_to_column_name( $order_by ) {

		return $order_by;
	}

	/**
	 * Parse the pagination query.
	 *
	 * @since 1.0
	 *
	 * @return Limit|null
	 */
	protected function parse_pagination() {

		if ( $this->args['items_per_page'] == - 1 ) {
			return null;
		}

		if ( $this->args['page'] < 1 ) {
			throw new \InvalidArgumentException( "page parameter must be at least 1." );
		}

		$per_page = absint( $this->args['items_per_page'] );
		$page     = absint( $this->args['page'] );

		$count  = $per_page;
		$offset = $per_page * ( $page - 1 );

		return new Limit( $count, $offset );
	}

}