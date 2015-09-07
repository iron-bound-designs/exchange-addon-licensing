<?php
/**
 * Releases single view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\Controller;

use IronBound\DB\Manager;
use ITELIC\Admin\Chart;
use ITELIC\Admin\Releases\Controller;
use ITELIC\Admin\Releases\View\Single as Single_View;
use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Plugin;
use ITELIC\Release;

/**
 * Class Single
 * @package ITELIC\Admin\Releases\Controller
 */
class Single extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$release = Release::with_id( $_GET['ID'] );

		if ( ! $release ) {
			wp_redirect( Dispatch::get_tab_link( 'releases' ) );

			return;
		}

		$this->enqueue();

		$view = new Single_View( $release, $this->get_progress_chart( $release ), $this->get_version_chart( $release ) );

		$view->begin();
		$view->title();

		$view->tabs( 'releases' );

		$view->render();

		$view->end();
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0
	 */
	private function enqueue() {
		wp_enqueue_style( 'itelic-admin-releases-edit' );
		wp_enqueue_script( 'itelic-admin-releases-edit' );
		wp_localize_script( 'itelic-admin-releases-edit', 'ITELIC', array(
			'prevVersion'  => __( "Previous version: %s", Plugin::SLUG ),
			'uploadTitle'  => __( "Choose Software File", Plugin::SLUG ),
			'uploadButton' => __( "Replace File", Plugin::SLUG ),
			'uploadLabel'  => __( "Upload File", Plugin::SLUG ),
			'lessUpgrade'  => __( "Less", Plugin::SLUG ),
			'moreUpgrade'  => __( "More", Plugin::SLUG ),
			'ibdLoadOn'    => 'loadCharts'
		) );

		wp_enqueue_media();
	}

	/**
	 * Get the progress line chart.
	 *
	 * @since 1.0
	 *
	 * @param Release $release
	 *
	 * @return Chart\Base
	 */
	private function get_progress_chart( Release $release ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$tn = Manager::get( 'itelic-upgrades' )->get_table_name( $wpdb );

		$id       = $release->get_ID();
		$end_date = $release->get_start_date()->add( new \DateInterval( 'P14D' ) );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT Date(upgrade_date) AS d, COUNT(ID) AS c FROM $tn WHERE release_id = %d AND upgrade_date < %s
			GROUP BY Day(d) ORDER BY upgrade_date ASC",
			$id, $end_date->format( 'Y-m-d H:i:s' ) ) );

		$raw = array();

		foreach ( $results as $result ) {
			$raw[ $result->d ] = (int) $result->c;
		}

		$now = new \DateTime();

		$diff = $release->get_start_date()->diff( $now );

		if ( $diff->days <= 14 ) {
			$days = $diff->days;
		} else {
			$days = 14;
		}

		$data = array();

		$day = clone $release->get_start_date();

		$sql_df = 'Y-m-d';

		for ( $i = 0; $i < $days; $i ++ ) {

			$key = $day->format( $sql_df );

			if ( isset( $raw[ $key ] ) ) {
				$data[ $key ] = $raw[ $key ];
			} else {
				$data[ $key ] = 0;
			}

			$day = $day->add( new \DateInterval( 'P1D' ) );
		}

		$df = 'M j';

		$labels = array_map( function ( $day ) use ( $df ) {

			$day = new \DateTime( $day );

			return $day->format( $df );
		}, array_keys( $data ) );

		$chart = new Chart\Line( $labels, 698, 200, array(
			'scaleIntegersOnly' => true,
			'ibdLoadOn'         => 'loadProgressChart'
		) );
		$chart->add_data_set( array_values( $data ), '', array(
			'fillColor'            => "rgba(151,187,205,0.2)",
			'strokeColor'          => "rgba(151,187,205,1)",
			'pointColor'           => "rgba(151,187,205,1)",
			'pointStrokeColor'     => "#fff",
			'pointHighlightFill'   => "#fff",
			'pointHighlightStroke' => "rgba(151,187,205,1)",
		) );

		return $chart;
	}

	/**
	 * Get the chart for displaying the previous version being upgrade from.
	 *
	 * @since 1.0
	 *
	 * @param Release $release
	 *
	 * @return Chart\Base
	 */
	private function get_version_chart( Release $release ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$tn = Manager::get( 'itelic-upgrades' )->get_table_name( $wpdb );

		$id = $release->get_ID();

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT previous_version AS v, COUNT(ID) AS c FROM $tn WHERE release_id = %d
			GROUP BY previous_version ORDER BY c DESC LIMIT 5",
			$id ) );

		$chart = new Chart\Pie( 698, 200, array(
			'ibdLoadOn'       => 'loadVersionsChart',
			'ibdShowLegend'   => '#pie-chart-legend',
			'tooltipTemplate' => '<%= value %> install<%if (value != 1){%>s<%}%>',
		) );

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

		foreach ( $results as $i => $result ) {
			$chart->add_data_set( $result->c, "v{$result->v}", $colors[ $i ] );
		}

		return $chart;
	}
}