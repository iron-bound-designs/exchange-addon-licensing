<?php
/**
 * Query keys.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC_API\Query;

use ITELIC\DB\Query\Builder;
use ITELIC\DB\Query\Tag\From;
use ITELIC\DB\Query\Tag\Join;
use ITELIC\DB\Query\Tag\Where;
use ITELIC\DB\Query\Tag\Where_Date;
use ITELIC\DB\Query\Tag\Where_Raw;
use ITELIC\Key;
use ITELIC\DB\Manager;

/**
 * Class Keys
 * @package ITELIC_API\Query
 */
class Keys extends Base {

	/**
	 * Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args = array() ) {
		parent::__construct( Manager::get( 'keys' ), $args );
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
			'transaction'             => '',
			'transaction__in'         => array(),
			'transaction__not_in'     => array(),
			'product'                 => '',
			'product__in'             => array(),
			'product__not_in'         => array(),
			'customer'                => '',
			'customer__in'            => array(),
			'customer__not_in'        => array(),
			'max'                     => '',
			'max__in'                 => array(),
			'max__not_in'             => array(),
			'status'                  => 'any',
			'expires'                 => '',
			'customer_search'         => '',
			'customer_search_columns' => array(
				'user_login',
				'user_nicename',
				'user_email',
				'display_name'
			),
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
	 * @return object
	 */
	protected function make_object( $data ) {
		return new Key( $data );
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

		if ( ( $transaction = $this->parse_transaction() ) !== null ) {
			$where->qAnd( $transaction );
		}

		if ( ( $product = $this->parse_product() ) !== null ) {
			$where->qAnd( $product );
		}

		if ( ( $customer = $this->parse_customer() ) !== null ) {
			$where->qAnd( $customer );
		}

		if ( ( $max = $this->parse_max() ) !== null ) {
			$where->qAnd( $max );
		}

		if ( ( $status = $this->parse_status() ) !== null ) {
			$where->qAnd( $status );
		}

		if ( ( $expires = $this->parse_expires() ) !== null ) {
			$where->qAnd( $expires );
		}

		$order = $this->parse_order();
		$limit = $this->parse_pagination();

		$builder->append( $select )->append( $from );

		$search = $this->parse_customer_search();

		if ( $search !== null ) {
			$builder->append( $search );
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
	protected function parse_transaction() {

		if ( ! empty( $this->args['transaction'] ) ) {
			$this->args['transaction__in'] = array( $this->args['transaction'] );
		}

		return $this->parse_in_or_not_in_query( 'transaction_id', $this->args['transaction__in'], $this->args['transaction__not_in'] );
	}

	/**
	 * Parse the product where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_product() {

		if ( ! empty( $this->args['product'] ) ) {
			$this->args['product__in'] = array( $this->args['product'] );
		}

		return $this->parse_in_or_not_in_query( 'product', $this->args['product__in'], $this->args['product__not_in'] );
	}


	/**
	 * Parse the customer where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_customer() {

		if ( ! empty( $this->args['customer'] ) ) {
			$this->args['customer__in'] = array( $this->args['customer'] );
		}

		return $this->parse_in_or_not_in_query( 'customer', $this->args['customer__in'], $this->args['customer__not_in'] );
	}


	/**
	 * Parse the max activations where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_max() {

		if ( ! empty( $this->args['max'] ) ) {
			$this->args['max__in'] = array( $this->args['max'] );
		}

		return $this->parse_in_or_not_in_query( 'max', $this->args['max__in'], $this->args['max__not_in'] );
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
			$white_list = Key::get_statuses();
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
	 * Parse the expires query.
	 *
	 * @since 1.0
	 *
	 * @return Where_Date|null
	 */
	protected function parse_expires() {
		if ( ! empty( $this->args['expires'] ) ) {
			$date_query = new \WP_Date_Query( $this->args['expires'], 'q.expires' );

			return new Where_Date( $date_query );
		} else {
			return null;
		}
	}

	/**
	 * Parse the search query.
	 *
	 * @since 1.0
	 *
	 * @return Join|null
	 */
	protected function parse_customer_search() {

		if ( empty( $this->args['customer_search'] ) || empty( $this->args['customer_search_columns'] ) ) {
			return null;
		}

		$clause     = esc_sql( $this->args['customer_search'] );
		$search_ids = new Where_Raw( 'u.ID = q.customer' );
		$white_list = $this->get_default_arg( 'customer_search_columns' );

		foreach ( (array) $this->args['customer_search_columns'] as $column ) {

			if ( ! in_array( $column, $white_list ) ) {
				throw new \InvalidArgumentException( "Invalid customer_search_column $column." );
			}

			if ( ! isset( $search_where ) ) {
				$search_where = new Where( "u.$column", 'LIKE', $clause );
			} else {
				/**
				 * @var Where $search_where
				 */
				$search_where->qOr( new Where( "u.$column", 'LIKE', $clause ) );
			}
		}

		if ( ! isset( $search_where ) ) {
			return null;
		}

		$search_ids->qAnd( $search_where );

		return new Join( new From( $GLOBALS['wpdb']->users, 'u' ), $search_ids );
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
			case 'max_active':
				return 'max';
		}

		return $order_by;
	}

}