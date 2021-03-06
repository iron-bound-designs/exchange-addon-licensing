<?php
/**
 * Query keys.
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
use IronBound\DB\Query\Tag\Join;
use IronBound\DB\Query\Tag\Where;
use IronBound\DB\Query\Tag\Where_Date;
use IronBound\DB\Query\Tag\Where_Raw;
use ITELIC\Key;
use IronBound\DB\Manager;

/**
 * Class Keys
 * @package ITELIC_API\Query
 */
class Keys extends Complex_Query {

	/**
	 * Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args = array() ) {
		parent::__construct( Manager::get( 'itelic-keys' ), $args );
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
			'key_like'                => '',
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
	 * @return Model
	 */
	protected function make_object( \stdClass $data ) {
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

		if ( ( $key_like = $this->parse_key_like() ) !== null ) {
			$where->qAnd( $key_like );
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
	 * Parse a partial search for a key.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_key_like() {

		if ( empty( $this->args['key_like'] ) ) {
			return null;
		}

		$like = esc_sql( $this->args['key_like'] );

		return new Where( 'lkey', 'LIKE', "%{$like}%" );
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