<?php
/**
 * Abstract complex query class.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   MIT
 * @copyright Iron Bound Designs, 2015.
 */

namespace IronBound\DBLogger;

use IronBound\DB\Model;
use IronBound\DB\Query\Builder;
use IronBound\DB\Query\Complex_Query;
use IronBound\DB\Query\Tag\From;
use IronBound\DB\Query\Tag\Where;
use IronBound\DB\Query\Tag\Where_Date;

/**
 * Class LogQuery
 * @package IronBound\DBLogger
 */
class LogQuery extends Complex_Query {

	/**
	 * @var string
	 */
	private $model_class;

	/**
	 * Constructor.
	 *
	 * @param AbstractTable $table
	 * @param string        $model_class
	 * @param array         $args
	 */
	public function __construct( AbstractTable $table, $model_class, array $args ) {

		$this->model_class = $model_class;

		parent::__construct( $table, $args );
	}

	/**
	 * Get the default args.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_default_args() {
		return wp_parse_args( array(
			'level'        => '',
			'message'      => '',
			'group'        => '',
			'time'         => '',
			'user'         => '',
			'user__in'     => array(),
			'user__not_in' => array()
		), parent::get_default_args() );
	}

	/**
	 * Convert data to its object.
	 *
	 * @since 1.0
	 *
	 * @param \stdClass $data
	 *
	 * @return Model
	 */
	protected function make_object( \stdClass $data ) {

		$class = $this->model_class;

		return new $class( $data );
	}

	/**
	 * Build the sql query.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected function build_sql() {

		$builder = new Builder();

		$select = $this->parse_select();
		$from   = new From( $this->table->get_table_name( $GLOBALS['wpdb'] ), 'q' );

		$where = new Where( 1, true, 1 );

		if ( ( $message = $this->parse_message() ) !== null ) {
			$where->qAnd( $message );
		}

		if ( ( $level = $this->parse_level() ) !== null ) {
			$where->qAnd( $level );
		}

		if ( ( $user = $this->parse_user() ) !== null ) {
			$where->qAnd( $user );
		}

		if ( ( $group = $this->parse_group() ) !== null ) {
			$where->qAnd( $group );
		}

		if ( ( $time = $this->parse_time() ) !== null ) {
			$where->qAnd( $time );
		}

		$order = $this->parse_order();
		$limit = $this->parse_pagination();

		$builder->append( $select )->append( $from );

		$builder->append( $where );
		$builder->append( $order );

		if ( $limit !== null ) {
			$builder->append( $limit );
		}

		return $builder->build();
	}

	/**
	 * Parse a partial search for a message.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_message() {

		if ( empty( $this->args['message'] ) ) {
			return null;
		}

		$like = esc_sql( $this->args['message'] );

		return new Where( 'message', 'LIKE', "%{$like}%" );
	}

	/**
	 * Parse the level where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_level() {

		if ( empty( $this->args['level'] ) ) {
			return null;
		}

		return new Where( 'level', true, $this->args['level'] );
	}


	/**
	 * Parse the group where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_group() {

		if ( empty( $this->args['group'] ) ) {
			return null;
		}

		return new Where( 'lgroup', true, $this->args['group'] );
	}

	/**
	 * Parse the user where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_user() {

		if ( ! empty( $this->args['user'] ) ) {
			$this->args['user__in'] = array( $this->args['user'] );
		}

		return $this->parse_in_or_not_in_query( 'user', $this->args['user__in'], $this->args['user__not_in'] );
	}


	/**
	 * Parse the time query.
	 *
	 * @since 1.0
	 *
	 * @return Where_Date|null
	 */
	protected function parse_time() {
		if ( ! empty( $this->args['time'] ) ) {
			$date_query = new \WP_Date_Query( $this->args['time'], 'q.time' );

			return new Where_Date( $date_query );
		} else {
			return null;
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

		switch ( $order_by ) {
			case 'group':
				return 'lgroup';
		}

		return $order_by;
	}
}