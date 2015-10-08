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
use ITELIC\Admin\Reports\Date_Filterable;
use ITELIC\Admin\Reports\Product_Filterable;
use ITELIC\Admin\Reports\Report;
use ITELIC\Plugin;

/**
 * Class Installed_Versions
 * @package ITELIC\Admin\Reports\Types
 */
class Installed_Versions extends Report implements Product_Filterable {

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
	 * Return boolean true if a product is required to view this report.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_product_required() {
		return true;
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

		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$atn = Manager::get( 'itelic-activations' )->get_table_name( $wpdb );
		$ktn = Manager::get( 'itelic-keys' )->get_table_name( $wpdb );

		$raw = "SELECT COUNT(1) as c, `release_id` as d FROM $atn a JOIN $ktn k ON (k.lkey = a.lkey AND k.product = %d)
				WHERE a.status = %s GROUP BY `release_id` LIMIT 5";

		$results = $wpdb->get_results( $wpdb->prepare( $raw, $product, Activation::ACTIVE ) );

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

				$release = itelic_get_release( $label );
				$label = $release->get_version();

				$label = "v$label";
			}

			$chart->add_data_set( $value, $label, $colors[ $i ] );

			$i ++;
		}

		return $chart;
	}
}

