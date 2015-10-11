<?php
/**
 * Query activation records.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Query;

use IronBound\DB\Model;
use IronBound\DB\Query\Complex_Query;
use IronBound\DB\Query\Tag\Join;
use IronBound\DB\Query\Tag\Where_Raw;
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
			'release'          => '',
			'release__in'      => array(),
			'release__not_in'  => array(),
			'product'          => '',
			'customer'         => ''
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

		if ( ( $location = $this->parse_location() ) !== null ) {
			$where->qAnd( $location );
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

		if ( ( $release = $this->parse_release() ) !== null ) {
			$where->qAnd( $release );
		}

		$order = $this->parse_order();
		$limit = $this->parse_pagination();

		$builder->append( $select )->append( $from );

		if ( ( $join = $this->parse_join() ) !== null ) {
			$builder->append( $join );
		}

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

			return new Where( 'q.status', true, (array) $this->args['status'] );
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
	 * Parse the release query.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_release() {

		if ( ! empty( $this->args['release'] ) ) {
			$this->args['release__in'] = array( $this->args['release'] );
		}

		return $this->parse_in_or_not_in_query( 'release_id', $this->args['release__in'], $this->args['release__not_in'] );
	}

	/**
	 * Parse the product query.
	 *
	 * @since 1.0
	 *
	 * @return Join|null
	 */
	protected function parse_join() {

		if ( empty( $this->args['product'] ) && empty( $this->args['customer'] ) ) {
			return null;
		}

		$on = new Where_Raw( 'k.lkey = q.lkey' );

		if ( ! empty( $this->args['product'] ) ) {
			$on->qAnd( new Where( 'k.product', true, absint( $this->args['product'] ) ) );
		}

		if ( ! empty( $this->args['customer'] ) ) {
			$on->qAnd( new Where( 'k.customer', true, absint( $this->args['customer'] ) ) );
		}

		return new Join( new From( Manager::get( 'itelic-keys' )->get_table_name( $GLOBALS['wpdb'] ), 'k' ), $on );
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