<?php
/**
 * Licenses report
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports\Types;

use IronBound\DB\Manager;
use ITELIC\Admin\Chart\Base as Chart;
use ITELIC\Admin\Chart\Line;
use ITELIC\Admin\Reports\Report;
use ITELIC\Key;
use ITELIC\Plugin;

/**
 * Class Licenses
 * @package ITELIC\Admin\Reports\Types
 */
class Licenses extends Report {

	/**
	 * Get the title of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( "Licenses", Plugin::SLUG );
	}

	/**
	 * Get the slug of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'licenses';
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
			"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec euismod pharetra felis sit amet faucibus. ",
			Plugin::SLUG
		);
	}

	/**
	 * Get the chart for this report.
	 *
	 * @since 1.0
	 *
	 * @param string $date_type
	 *
	 * @return Chart
	 */
	public function get_chart( $date_type = 'this_year' ) {

		$start = date( 'Y-m-d H:i:s', $this->convert_date( $date_type ) );
		$end   = date( 'Y-m-d H:i:s', $this->convert_date( $date_type, true ) );

		$grouping = self::get_grouping_for_date_type( $date_type );

		$sql   = self::get_group_by( $grouping, 'p.post_date' );
		$group = $sql['group'];
		$per   = $sql['per'];

		if ( $per ) {
			$per .= ' AS p, ';
		}

		if ( $group ) {
			$group = "GROUP BY $group";
		}

		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$ktn = Manager::get( 'itelic-keys' )->get_table_name( $wpdb );
		$ptn = $wpdb->posts;

		$raw = "SELECT {$per}COUNT(1) as c FROM $ktn k JOIN $ptn p ON (k.transaction_id = p.ID and p.post_date BETWEEN %s and %s) WHERE k.status = %s $group";

		$active   = $wpdb->get_results( $wpdb->prepare( $raw, $start, $end, Key::ACTIVE ) );
		$expired  = $wpdb->get_results( $wpdb->prepare( $raw, $start, $end, Key::EXPIRED ) );
		$disabled = $wpdb->get_results( $wpdb->prepare( $raw, $start, $end, Key::DISABLED ) );

		$active   = self::fill_gaps( self::translate_results( $active ), $start, $end, $grouping );
		$expired  = self::fill_gaps( self::translate_results( $expired ), $start, $end, $grouping );
		$disabled = self::fill_gaps( self::translate_results( $disabled ), $start, $end, $grouping );

		$labels = self::get_labels( $active, $date_type );

		$chart = new Line( $labels, 600, 200, array(
			'ibdShowLegend' => '#legend-' . $this->get_slug(),
			'responsive'    => true
		) );

		$statuses = Key::get_statuses();

		$chart->add_data_set( array_values( $active ), $statuses[ Key::ACTIVE ], array(
			'fillColor'            => "rgba(140,197,62,0.2)",
			'strokeColor'          => "rgba(140,197,62,1)",
			'pointColor'           => "rgba(140,197,62,1)",
			'pointStrokeColor'     => "#fff",
			'pointHighlightFill'   => "#fff",
			'pointHighlightStroke' => "rgba(140,197,62,1)",
		) );

		$chart->add_data_set( array_values( $expired ), $statuses[ Key::EXPIRED ], array(
			'fillColor'            => "rgba(255,186,0,0.2)",
			'strokeColor'          => "rgba(255,186,0,1)",
			'pointColor'           => "rgba(255,186,0,1)",
			'pointStrokeColor'     => "#fff",
			'pointHighlightFill'   => "#fff",
			'pointHighlightStroke' => "rgba(255,186,0,1)",
		) );

		$chart->add_data_set( array_values( $disabled ), $statuses[ Key::DISABLED ], array(
			'fillColor'            => "rgba(221,61,54,0.2)",
			'strokeColor'          => "rgba(221,61,54,1)",
			'pointColor'           => "rgba(221,61,54,1)",
			'pointStrokeColor'     => "#fff",
			'pointHighlightFill'   => "#fff",
			'pointHighlightStroke' => "rgba(221,61,54,1)",
		) );

		return $chart;
	}

}

