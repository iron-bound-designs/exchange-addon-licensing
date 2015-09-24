<?php
/**
 * Installed versions report
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports\Types;

use IronBound\DB\Manager;
use ITELIC\Activation;
use ITELIC\Admin\Chart\Base as Chart;
use ITELIC\Admin\Chart\Pie;
use ITELIC\Admin\Reports\Report;
use ITELIC\Plugin;

/**
 * Class Installed_Versions
 * @package ITELIC\Admin\Reports\Types
 */
class Installed_Versions extends Report {

	/**
	 * Get the title of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( "Installed versions", Plugin::SLUG );
	}

	/**
	 * Get the slug of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'installed-versions';
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
			"View the five most popular versions of an installed product.",
			Plugin::SLUG
		);
	}

	/**
	 * Get the chart for this report.
	 *
	 * @since 1.0
	 *
	 * @param string $date_type
	 * @param int    $product
	 *
	 * @return Chart
	 */
	public function get_chart( $date_type = 'this_year', $product = 0 ) {

		if ( ! $product ) {
			return null;
		}

		$start = date( 'Y-m-d H:i:s', $this->convert_date( $date_type ) );
		$end   = date( 'Y-m-d H:i:s', $this->convert_date( $date_type, true ) );

		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$atn = Manager::get( 'itelic-activations' )->get_table_name( $wpdb );
		$ktn = Manager::get( 'itelic-keys' )->get_table_name( $wpdb );

		$raw = "SELECT COUNT(1) as c, version as d FROM $atn a JOIN $ktn k ON (k.lkey = a.lkey AND k.product = %d)
				WHERE a.activation BETWEEN %s AND %s AND a.status = %s GROUP BY version LIMIT 5";

		$results = $wpdb->get_results( $wpdb->prepare( $raw, $product, $start, $end, Activation::ACTIVE ) );

		$translated = self::translate_results( $results );

		if ( isset( $translated[''] ) ) {
			$unknown = $translated[''];

			$translated[ __( 'Unknown', Plugin::SLUG ) ] = $unknown;

			unset( $translated[''] );
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
			'tooltipTemplate' => '<%= value %> install<%if (value != 1){%>s<%}%>',
		) );

		$i = 0;

		foreach ( $translated as $label => $value ) {

			if ( $label != __( 'Unknown', Plugin::SLUG ) ) {
				$label = "v$label";
			}

			$chart->add_data_set( $value, $label, $colors[ $i ] );

			$i ++;
		}

		return $chart;
	}
}

