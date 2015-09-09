<?php
/**
 * Releases single view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\Controller;

use IronBound\DB\Manager;
use IronBound\WP_Notifications\Notification;
use IronBound\WP_Notifications\Queue\Storage\Options;
use IronBound\WP_Notifications\Queue\WP_Cron;
use IronBound\WP_Notifications\Strategy\iThemes_Exchange;
use IronBound\WP_Notifications\Template\Factory;
use IronBound\WP_Notifications\Template\Listener;
use IronBound\WP_Notifications\Template\Manager as Template_Manager;
use ITELIC\Activation;
use ITELIC\Admin\Chart;
use ITELIC\Admin\Releases\Controller;
use ITELIC\Admin\Releases\View\Single as Single_View;
use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Plugin;
use ITELIC\Release;
use ITELIC_API\Query\Activations;

/**
 * Class Single
 *
 * @package ITELIC\Admin\Releases\Controller
 */
class Single extends Controller {

	/**
	 * Single constructor.
	 */
	public function __construct() {

		add_action( 'wp_ajax_itelic_admin_releases_single_update', array(
			$this,
			'handle_ajax_update'
		) );

		add_action( 'ibd_wp_notifications_template_manager_itelic-outdated-customers', array(
			$this,
			'outdated_customers_manager'
		) );

		add_action( 'wp_ajax_itelic_admin_releases_send_notification', array(
			$this,
			'send_notification'
		) );
	}

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
	 * Handle the AJAX request for updating information about this license key.
	 */
	public function handle_ajax_update() {

		if ( ! isset( $_POST['release'] ) || ! isset( $_POST['prop'] ) || ! isset( $_POST['val'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", Plugin::SLUG )
			) );
		}

		$release = intval( $_POST['release'] );
		$prop    = sanitize_text_field( $_POST['prop'] );
		$val     = $_POST['val'];
		$nonce   = sanitize_text_field( $_POST['nonce'] );

		if ( ! wp_verify_nonce( $nonce, "itelic-update-release-$release" ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, you don't have permission to do this.", Plugin::SLUG )
			) );
		}

		/** @var Release $release */
		$release = Release::get( $release );

		try {
			switch ( $prop ) {
				case 'status':
					$release->set_status( $val );
					break;

				case 'type':
					$release->set_type( $val );
					break;

				case 'version':
					$release->set_version( sanitize_text_field( $val ) );
					break;

				case 'download':
					$release->set_download( intval( $val ) );
					break;

				case 'changelog':
					$release->set_changelog( $val );
					break;

				case 'security-message':
					$release->update_meta( 'security-message', sanitize_text_field( $val ) );
					break;

				default:
					wp_send_json_error( array(
						'message' => __( "Invalid request format.", Plugin::SLUG )
					) );
			}
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success();
	}

	/**
	 * Setup the template manager for the notification sent to outdated
	 * customers.
	 *
	 * @since 1.0
	 *
	 * @param Template_Manager $manager
	 */
	public function outdated_customers_manager( Template_Manager $manager ) {

		$shared = \ITELIC\get_shared_tags();

		foreach ( $shared as $listener ) {
			$manager->listen( $listener );
		}

		$manager->listen( new Listener( 'product_name', function ( Release $release ) {
			return $release->get_product()->post_title;
		} ) );

		$manager->listen( new Listener( 'version', function ( Release $release ) {
			return $release->get_version();
		} ) );

		$manager->listen( new Listener( 'changelog', function ( Release $release ) {
			return $release->get_changelog();
		} ) );

		$manager->listen( new Listener( 'install_list', function ( Release $release, \WP_User $to ) {

			$query = new Activations( array(
				'status'          => Activation::ACTIVE,
				'product'         => $release->get_product()->ID,
				'customer'        => $to->ID,
				'version__not_in' => array( $release->get_version() )
			) );

			$activations = array_filter( $query->get_results(), function ( Activation $activation ) use ( $release ) {

				if ( ! $activation->get_version() ) {
					return true;
				}

				return version_compare( $activation->get_version(), $release->get_version(), '<' );
			} );

			$html = '<ul>';

			/** @var Activation $activation */
			foreach ( $activations as $activation ) {
				$html .= '<li>' . "{$activation->get_location()} – v{$activation->get_version()}" . '</li>';
			}

			$html .= '</ul>';

			return $html;
		} ) );
	}

	/**
	 * Handles the ajax callback for sending the notifications to outdated
	 * customers.
	 *
	 * @since 1.0
	 */
	public function send_notification() {

		if ( ! isset( $_POST['release'] ) || ! isset( $_POST['subject'] ) || ! isset( $_POST['message'] ) || ! isset( $_POST['nonce'] ) ) {
			return;
		}

		$release = intval( $_POST['release'] );
		$subject = sanitize_text_field( $_POST['subject'] );
		$message = wp_unslash( $_POST['message'] );
		$nonce   = wp_unslash( $_POST['nonce'] );

		if ( ! wp_verify_nonce( $nonce, "itelic-update-release-$release" ) ) {
			wp_send_json_error( array(
				'message' => __( "Request expired. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		/** @var Release $release */
		$release = Release::get( $release );

		/** @var $wpdb \wpdb */
		global $wpdb;

		$atn = Manager::get( 'itelic-activations' )->get_table_name( $wpdb );
		$ktn = Manager::get( 'itelic-keys' )->get_table_name( $wpdb );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT DISTINCT k.customer FROM $atn a
			JOIN $ktn k ON (a.lkey = k.lkey AND k.product = %d)
			WHERE a.status = %s AND a.version != %d",
			$release->get_product()->ID, Activation::ACTIVE, $release->get_version() ) );

		if ( empty( $results ) ) {
			wp_send_json_error( array(
				'message' => __( "All customers have upgraded.", Plugin::SLUG )
			) );
		}

		$notifications = array();

		foreach ( $results as $result ) {

			$to = get_user_by( 'id', $result->customer );

			$notification = new Notification( $to, Factory::make( 'itelic-outdated-customers' ), $message, $subject );
			$notification->add_data_source( $release );

			$notifications[] = $notification;
		}

		try {
			$cron = new WP_Cron( new Options( 'itelic-outdated-customers' ) );
			$cron->process( $notifications, new iThemes_Exchange() );
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success( array(
			'message' => sprintf( __( "Notifications to %d customers have been queued for sending", Plugin::SLUG ), count( $results ) )
		) );
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
			'saving'       => __( "Saving", Plugin::SLUG ),
			'ibdLoadOn'    => 'loadCharts',
			'statuses'     => Release::get_statuses(),
			'types'        => Release::get_types( true ),
			'release'      => $_GET['ID'],
			'update_nonce' => wp_create_nonce( 'itelic-update-release-' . $_GET['ID'] ),
			'ok'           => __( "Ok", Plugin::SLUG ),
			'cancel'       => __( "Cancel", Plugin::SLUG )
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

		if ( $release->get_status() == Release::STATUS_DRAFT ) {
			return null;
		}

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

		$days = min( 14, max( $diff->days + 1, 1 ) );

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
			'scaleBeginAtZero'  => true,
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

		if ( $release->get_status() == Release::STATUS_DRAFT ) {
			return null;
		}

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