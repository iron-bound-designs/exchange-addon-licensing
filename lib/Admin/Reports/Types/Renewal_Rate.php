<?php
/**
 * Renewal rate report type.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports\Types;

use IronBound\DB\Manager;
use ITELIC\Admin\Chart\Base;
use ITELIC\Admin\Chart\Pie;
use ITELIC\Admin\Reports\Date_Filterable;
use ITELIC\Admin\Reports\Product_Filterable;
use ITELIC\Admin\Reports\Report;
use ITELIC\Plugin;

/**
 * Class Renewal_Rate
 * @package ITELIC\Admin\Reports\Types
 */
class Renewal_Rate extends Report implements Date_Filterable, Product_Filterable {

	/**
	 * Get the title of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( "Renewal rate", Plugin::SLUG );
	}

	/**
	 * Get the slug of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'renewal-rate';
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
			"View the renewal rate of different products in different time periods.",
			Plugin::SLUG
		);
	}

	/**
	 * Retrieve the possible data types for a report.
	 *
	 * This returns an array of type slug to localized name.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_date_types() {
		$types = parent::get_date_types();

		$types['all_time'] = __( "All Time", Plugin::SLUG );

		return $types;
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

		if ( $date_type != 'all_time' ) {
			$start = date( 'Y-m-d H:i:s', $this->convert_date( $date_type ) );
			$end   = date( 'Y-m-d H:i:s', $this->convert_date( $date_type, true ) );
		}

		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$rtn = Manager::get( 'itelic-renewals' )->get_table_name( $wpdb );
		$ktn = Manager::get( 'itelic-keys' )->get_table_name( $wpdb );

		if ( $product ) {

			$product = absint( $product );
			$product = " AND k.product = $product";
		} else {
			$product = '';
		}

		if ( $date_type != 'all_time' ) {
			$renew_where = " WHERE r.renewal_date BETWEEN '$start' AND '$end'";
			$all_where   = " WHERE k.expires BETWEEN '$start' AND '$end'";
		} else {
			$renew_where = '';
			$all_where   = '';
		}

		if ( $product ) {
			$all_where .= "{$all_where}{$product}";
		}

		$raw_renewed     = "SELECT COUNT(1) as c FROM $rtn r JOIN $ktn k ON (k.lkey = r.lkey$product)$renew_where";
		$renewed_results = $wpdb->get_results( $raw_renewed );
		$renewed         = (int) $renewed_results[0]->c;

		$raw_all     = "SELECT COUNT(1) as c FROM $ktn k$all_where";
		$all_results = $wpdb->get_results( $raw_all );
		$all         = (int) $all_results[0]->c;

		if ( $renewed > $all ) {
			$renewed = 100;
			$expired = 0;
		} else {
			$renewed = number_format( $renewed / $all * 100, 0 );
			$expired = 100 - $renewed;;
		}

		$colors = array(
			array(
				'color'     => '#E94F37',
				'highlight' => '#FF6951'
			),
			array(
				'color'     => '#393E41',
				'highlight' => '#53585B'
			),
			array(
				'color'     => '#3F88C5',
				'highlight' => '#59A2DF'
			),
			array(
				'color'     => '#44BBA4',
				'highlight' => '#5ED5BE'
			),
			array(
				'color'     => '#EDDDD4',
				'highlight' => '#D4C4BB'
			),
		);

		$chart = new Pie( 600, 200, array(
			'ibdShowLegend'   => '#legend-' . $this->get_slug(),
			'responsive'      => true,
			'tooltipTemplate' => '<%= value %>%',
		) );

		$chart->add_data_set( $renewed, __( "Renewed", Plugin::SLUG ), $colors[2] );

		if ( $expired > 0 ) {
			$chart->add_data_set( $expired, __( "Expired", Plugin::SLUG ), $colors[0] );
		}

		return $chart;
	}
}