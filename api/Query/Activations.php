<?php
/**
 * Query activation records.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC_API\Query;

use IronBound\DB\Model;
use IronBound\DB\Query\Complex_Query;
use ITELIC\Activation;
use IronBound\DB\Query\Builder;
use IronBound\DB\Query\Tag\From;
use IronBound\DB\Query\Tag\Where;
use IronBound\DB\Query\Tag\Where_Date;
use IronBound\DB\Manager;

/**
 * Class Activations
 * @package ITELIC_API\Query
 */
class Activations extends Complex_Query {

	/**
	 * Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args = array() ) {
		parent::__construct( Manager::get( 'itelic-activations' ), $args );
	}

	/**
	 * Get the default args.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_default_args() {
		$existing = parent::get_default_args();

		$new = array(
			'key'              => '',
			'key__in'          => array(),
			'key__not_in'      => array(),
			'location'         => '',
			'location__in'     => array(),
			'location__not_in' => array(),
			'location_search'  => '',
			'status'           => 'any',
			'activation'       => '',
			'deactivation'     => '',
		);

		return wp_parse_args( $new, $existing );
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
		return new Activation( $data );
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

		if ( ( $key = $this->parse_key() ) !== null ) {
			$where->qAnd( $key );
		}

		if ( ( $location_search = $this->parse_location_search() ) !== null ) {
			$where->qAnd( $location_search );
		}

		if ( ( $status = $this->parse_status() ) !== null ) {
			$where->qAnd( $status );
		}

		if ( ( $activation = $this->parse_activation() ) !== null ) {
			$where->qAnd( $activation );
		}

		if ( ( $deactivation = $this->parse_deactivation() ) !== null ) {
			$where->qAnd( $deactivation );
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
	 * Parse the transaction where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_key() {

		if ( ! empty( $this->args['key'] ) ) {
			$this->args['key__in'] = array( $this->args['key'] );
		}

		return $this->parse_in_or_not_in_query( 'lkey', $this->args['key__in'], $this->args['key__not_in'] );
	}

	/**
	 * Parse the transaction where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_location() {

		if ( ! empty( $this->args['location'] ) ) {
			$this->args['location__in'] = array( $this->args['location'] );
		}

		return $this->parse_in_or_not_in_query( 'location', $this->args['location__in'], $this->args['location__not_in'] );
	}

	/**
	 * Parse the location search.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_location_search() {

		if ( empty( $this->args['location_search'] ) ) {
			return null;
		}

		return new Where( 'q.location', 'LIKE', esc_sql( $this->args['location_search'] ) );
	}

	/**
	 * Parse the status query.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_status() {
		if ( $this->args['status'] === 'any' ) {
			return null;
		} else {
			$white_list = Activation::get_statuses();
			$statuses   = (array) $this->args['status'];

			foreach ( $statuses as $status ) {
				if ( ! isset( $white_list[ $status ] ) ) {
					throw new \InvalidArgumentException( "Invalid status $status" );
				}
			}

			return new Where( 'status', true, (array) $this->args['status'] );
		}
	}

	/**
	 * Parse the activation date query.
	 *
	 * @since 1.0
	 *
	 * @return Where_Date|null
	 */
	protected function parse_activation() {
		if ( ! empty( $this->args['activation'] ) ) {
			$date_query = new \WP_Date_Query( $this->args['activation'], 'q.activation' );

			return new Where_Date( $date_query );
		} else {
			return null;
		}
	}

	/**
	 * Parse the deactivation date query.
	 *
	 * @since 1.0
	 *
	 * @return Where_Date|null
	 */
	protected function parse_deactivation() {
		if ( ! empty( $this->args['deactivation'] ) ) {
			$date_query = new \WP_Date_Query( $this->args['deactivation'], 'q.deactivation' );

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
			case 'key':
				return 'lkey';
		}

		return $order_by;
	}
}