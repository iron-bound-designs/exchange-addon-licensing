<?php
/**
 * Query releases records.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Query;

use IronBound\DB\Model;
use IronBound\DB\Query\Complex_Query;
use ITELIC\Release;
use IronBound\DB\Manager;
use IronBound\DB\Query\Builder;
use IronBound\DB\Query\Tag\From;
use IronBound\DB\Query\Tag\Where;
use IronBound\DB\Query\Tag\Where_Date;

/**
 * Class Releases
 * @package ITELIC_API\Query
 */
class Releases extends Complex_Query {

	/**
	 * Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args = array() ) {
		parent::__construct( Manager::get( 'itelic-releases' ), $args );
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
			'product'          => '',
			'product__in'      => array(),
			'product__not_in'  => array(),
			'download'         => '',
			'download__in'     => array(),
			'download__not_in' => array(),
			'status'           => 'any',
			'type'             => 'any',
			'version'          => '',
			'version_search'   => '',
			'changelog_search' => '',
			'start_date'       => '',
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
		return new Release( $data );
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

		if ( ( $product = $this->parse_product() ) !== null ) {
			$where->qAnd( $product );
		}

		if ( ( $download = $this->parse_download() ) !== null ) {
			$where->qAnd( $download );
		}

		if ( ( $status = $this->parse_status() ) !== null ) {
			$where->qAnd( $status );
		}

		if ( ( $type = $this->parse_type() ) !== null ) {
			$where->qAnd( $type );
		}

		if ( ( $version = $this->parse_version() ) !== null ) {
			$where->qAnd( $version );
		}

		if ( ( $version_search = $this->parse_version_search() ) !== null ) {
			$where->qAnd( $version_search );
		}

		if ( ( $changelog_search = $this->parse_changelog_search() ) !== null ) {
			$where->qAnd( $changelog_search );
		}

		if ( ( $start_date = $this->parse_start_date() ) !== null ) {
			$where->qAnd( $start_date );
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
	 * Parse the download where.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_download() {

		if ( ! empty( $this->args['download'] ) ) {
			$this->args['download__in'] = array( $this->args['download'] );
		}

		return $this->parse_in_or_not_in_query( 'download', $this->args['download__in'], $this->args['download__not_in'] );
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
			$white_list = Release::get_statuses();
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
	 * Parse the status query.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_type() {
		if ( $this->args['type'] === 'any' ) {
			return null;
		} else {
			$white_list = Release::get_types();
			$types      = (array) $this->args['type'];

			foreach ( $types as $type ) {
				if ( ! isset( $white_list[ $type ] ) ) {
					throw new \InvalidArgumentException( "Invalid type $type" );
				}
			}

			return new Where( 'type', true, (array) $this->args['type'] );
		}
	}

	/**
	 * Parse the version search.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_version() {

		if ( empty( $this->args['version'] ) ) {
			return null;
		}

		return new Where( 'q.version', true, esc_sql( $this->args['version'] ) );
	}

	/**
	 * Parse the version search.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_version_search() {

		if ( empty( $this->args['version_search'] ) ) {
			return null;
		}

		return new Where( 'q.version', 'LIKE', esc_sql( $this->args['version_search'] ) );
	}

	/**
	 * Parse the changelog search.
	 *
	 * @since 1.0
	 *
	 * @return Where|null
	 */
	protected function parse_changelog_search() {

		if ( empty( $this->args['changelog_search'] ) ) {
			return null;
		}

		return new Where( 'q.changelog', 'LIKE', esc_sql( $this->args['changelog_search'] ) );
	}

	/**
	 * Parse the start date query.
	 *
	 * @since 1.0
	 *
	 * @return Where_Date|null
	 */
	protected function parse_start_date() {
		if ( ! empty( $this->args['start_date'] ) ) {
			$date_query = new \WP_Date_Query( $this->args['start_date'], 'q.start_date' );

			return new Where_Date( $date_query );
		} else {
			return null;
		}
	}

}