<?php
/**
 * Query renewal records.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Query;

use IronBound\DB\Model;
use IronBound\DB\Query\Complex_Query;
use IronBound\DB\Query\Builder;
use IronBound\DB\Query\Tag\From;
use IronBound\DB\Query\Tag\Where;
use IronBound\DB\Query\Tag\Where_Date;
use IronBound\DB\Manager;
use ITELIC\Renewal;

/**
 * Class Renewals
 * @package ITELIC_API\Query
 */
class Renewals extends Complex_Query {

	/**
	 * Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args = array() ) {
		parent::__construct( Manager::get( 'itelic-renewals' ), $args );
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
			'key'                 => '',
			'key__in'             => array(),
			'key__not_in'         => array(),
			'transaction'         => '',
			'transaction__in'     => array(),
			'transaction__not_in' => array(),
			'renewal_date'        => '',
			'key_expired_date'    => '',
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
		return new Renewal( $data );
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

		if ( ( $transaction = $this->parse_transaction() ) !== null ) {
			$where->qAnd( $transaction );
		}

		if ( ( $renewal_date = $this->parse_renewal_date() ) !== null ) {
			$where->qAnd( $renewal_date );
		}

		if ( ( $key_expired_date = $this->parse_key_expired_date() ) !== null ) {
			$where->qAnd( $key_expired_date );
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
	protected function parse_transaction() {

		if ( ! empty( $this->args['transaction'] ) ) {
			$this->args['transaction__in'] = array( $this->args['transaction'] );
		}

		return $this->parse_in_or_not_in_query( 'transaction_id', $this->args['transaction__in'], $this->args['transaction__not_in'] );
	}

	/**
	 * Parse the renewal date query.
	 *
	 * @since 1.0
	 *
	 * @return Where_Date|null
	 */
	protected function parse_renewal_date() {
		if ( ! empty( $this->args['renewal_date'] ) ) {
			$date_query = new \WP_Date_Query( $this->args['renewal_date'], 'q.renewal_date' );

			return new Where_Date( $date_query );
		} else {
			return null;
		}
	}

	/**
	 * Parse the key expired date query.
	 *
	 * @since 1.0
	 *
	 * @return Where_Date|null
	 */
	protected function parse_key_expired_date() {
		if ( ! empty( $this->args['key_expired_date'] ) ) {
			$date_query = new \WP_Date_Query( $this->args['key_expired_date'], 'q.key_expired_date' );

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
			case 'transaction':
				return 'transaction_id';
		}

		return $order_by;
	}
}