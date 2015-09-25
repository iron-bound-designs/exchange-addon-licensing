<?php
/**
 * Updates query.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */


namespace ITELIC_API;

use IronBound\DB\Manager;
use IronBound\DB\Model;
use IronBound\DB\Query\Builder;
use IronBound\DB\Query\Complex_Query;
use IronBound\DB\Query\Tag\From;
use IronBound\DB\Query\Tag\Where;
use IronBound\DB\Query\Tag\Where_Date;
use ITELIC\Update;

/**
 * Class Updates
 * @package ITELIC_API
 */
class Updates extends Complex_Query {

	/**
	 * Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args = array() ) {
		parent::__construct( Manager::get( 'itelic-updates' ), $args );
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
			'activation'         => '',
			'activation__in'     => array(),
			'activation__not_in' => array(),
			'release'            => '',
			'release__in'        => array(),
			'release__not_in'    => array(),
			'update_date'        => '',
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
		return new Update( $data );
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

		if ( ( $activation = $this->parse_activation() ) !== null ) {
			$where->qAnd( $activation );
		}

		if ( ( $release = $this->parse_release() ) !== null ) {
			$where->qAnd( $release );
		}

		if ( ( $update_date = $this->parse_update_date() ) !== null ) {
			$where->qAnd( $update_date );
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
	 * Parse the activation where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_activation() {

		if ( ! empty( $this->args['activation'] ) ) {
			$this->args['activation__in'] = array( $this->args['activation'] );
		}

		return $this->parse_in_or_not_in_query( 'activation', $this->args['activation__in'], $this->args['activation__not_in'] );
	}

	/**
	 * Parse the release where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_release() {

		if ( ! empty( $this->args['release'] ) ) {
			$this->args['release__in'] = array( $this->args['release'] );
		}

		return $this->parse_in_or_not_in_query( 'release', $this->args['release__in'], $this->args['release__not_in'] );
	}

	/**
	 * Parse the start date query.
	 *
	 * @since 1.0
	 *
	 * @return Where_Date|null
	 */
	protected function parse_update_date() {
		if ( ! empty( $this->args['update_date'] ) ) {
			$date_query = new \WP_Date_Query( $this->args['update_date'], 'q.update_date' );

			return new Where_Date( $date_query );
		} else {
			return null;
		}
	}
}