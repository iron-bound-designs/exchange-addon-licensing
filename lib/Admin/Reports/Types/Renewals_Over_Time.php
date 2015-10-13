<?php
/**
 * Renewals over time report.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Reports\Types;

use IronBound\DB\Manager;
use ITELIC\Admin\Chart\Base;
use ITELIC\Admin\Chart\Line;
use ITELIC\Admin\Reports\Date_Filterable;
use ITELIC\Admin\Reports\Product_Filterable;
use ITELIC\Admin\Reports\Report;
use ITELIC\Plugin;

/**
 * Class Renewals_Over_Time
 * @package ITELIC\Admin\Reports\Types
 */
class Renewals_Over_Time extends Report implements Date_Filterable, Product_Filterable {

	/**
	 * Get the title of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( "Renewals over time", Plugin::SLUG );
	}

	/**
	 * Get the slug of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'renewals-over-time';
	}

	/**
	 * Get the description of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_description() {
		return __(
			"Chart renewals for all products, or a specific product, over time.",
			Plugin::SLUG
		);
	}

	/**
	 * Return boolean true if a product is required to view this report.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_product_required() {
		return false;
	}

	/**
	 * Get the chart for this report.
	 *
	 * @since 1.0
	 *
	 * @param string $date_type
	 * @param int    $product
	 *
	 * @return Base
	 */
	public function get_chart( $date_type = 'this_year', $product = 0 ) {

		$start = date( 'Y-m-d H:i:s', $this->convert_date( $date_type ) );
		$end   = date( 'Y-m-d H:i:s', $this->convert_date( $date_type, true ) );

		$grouping = self::get_grouping_for_date_type( $date_type );

		$sql   = self::get_group_by( $grouping, 'r.renewal_date' );
		$group = $sql['group'];
		$per   = $sql['per'];

		if ( $per ) {
			$per .= ' AS d, ';
		}

		if ( $group ) {
			$group = "GROUP BY $group";
		}

		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$rtn = Manager::get( 'itelic-renewals' )->get_table_name( $wpdb );
		$ktn = Manager::get( 'itelic-keys' )->get_table_name( $wpdb );

		if ( $product ) {
			$raw = "SELECT {$per}COUNT(1) as c FROM $rtn r JOIN $ktn k ON(k.lkey = r.lkey AND k.product = %d) WHERE r.renewal_date BETWEEN %s AND %s";
			$raw .= $group;
			$renewals = $wpdb->get_results( $wpdb->prepare( $raw, $product, $start, $end ) );
		} else {
			$raw = "SELECT {$per}COUNT(1) as c FROM $rtn r  WHERE r.renewal_date BETWEEN %s AND %s";
			$raw .= $group;
			$renewals = $wpdb->get_results( $wpdb->prepare( $raw, $start, $end ) );
		}

		$renewals = self::fill_gaps( self::translate_results( $renewals ), $start, $end, $grouping );

		$labels = self::get_labels( $renewals, $date_type );

		$chart = new Line( $labels, 600, 200, array(
			//'ibdShowLegend' => '#legend-' . $this->get_slug(),
			'responsive' => true
		) );

		$chart->add_data_set( array_values( $renewals ), __( "Renewals", Plugin::SLUG ), array(
			'fillColor'            => "rgba(151,187,205,0.2)",
			'strokeColor'          => "rgba(151,187,205,1)",
			'pointColor'           => "rgba(151,187,205,1)",
			'pointStrokeColor'     => "#fff",
			'pointHighlightFill'   => "#fff",
			'pointHighlightStroke' => "rgba(151,187,205,1)",
		) );

		return $chart;
	}
}