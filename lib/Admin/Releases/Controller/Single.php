<?php
/**
 * Releases single view.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Releases\Controller;

use IronBound\DB\Manager;
use IronBound\WP_Notifications\Notification;
use IronBound\WP_Notifications\Template\Factory;
use IronBound\WP_Notifications\Template\Listener;
use IronBound\WP_Notifications\Template\Manager as Template_Manager;
use ITELIC\Activation;
use ITELIC\Admin\Chart;
use ITELIC\Admin\Releases\Controller;
use ITELIC\Admin\Releases\View\Single as Single_View;
use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;
use ITELIC\Release;
use ITELIC\Query\Activations;

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
		$release = itelic_get_release( $_GET['ID'] );

		if ( ! $release ) {
			wp_redirect( Dispatch::get_tab_link( 'releases' ) );

			return;
		}

		$this->enqueue();

		$view = new Single_View( $release, $this->get_progress_chart( $release ), $this->get_version_chart( $release ) );

		$view->begin();
		$view->title();

		$current = $release->get_product()->get_feature( 'licensing', array( 'field' => 'version' ) );
		$new     = $release->get_version();

		$version_statuses = array(
			Release::STATUS_DRAFT,
			Release::STATUS_PAUSED
		);

		if ( in_array( $release->get_status(), $version_statuses ) && version_compare( $new, $current, '<=' ) ) {

			$msg = __( "The version number of this release is less than the current version.", Plugin::SLUG ) . '&nbsp;';

			if ( $release->get_status() == Release::STATUS_DRAFT ) {
				$msg .= sprintf( __( "This release's version must be greater than %s in order to activate this release.",
					Plugin::SLUG ), $current );
			}

			if ( $release->get_status() == Release::STATUS_PAUSED ) {

				$new_url = add_query_arg( 'view', 'add-new', Dispatch::get_tab_link( 'releases' ) );

				$msg .= sprintf( __( 'You should %1$screate a new release%2$s instead.', Plugin::SLUG ),
					"<a href=\"$new_url\">", '</a>' );
			}

			$view->notice( $msg, View::NOTICE_ERROR );
		}

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
		$prop    = $_POST['prop'];
		$val     = $_POST['val'];
		$nonce   = $_POST['nonce'];

		try {
			$this->do_update( itelic_get_release( $release ), $prop, $val, $nonce );
		}
		catch ( \InvalidArgumentException $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success();
	}

	/**
	 * Update a release's property.
	 *
	 * @since 1.0
	 *
	 * @param Release $release
	 * @param string  $prop
	 * @param string  $val
	 * @param string  $nonce
	 *
	 * @return Release
	 */
	public function do_update( Release $release, $prop, $val, $nonce ) {

		if ( ! wp_verify_nonce( $nonce, "itelic-update-release-{$release->get_pk()}" ) ) {
			throw new \InvalidArgumentException(
				__( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \InvalidArgumentException(
				__( "Sorry, you don't have permission to do this.", Plugin::SLUG ) );
		}

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
				throw new \InvalidArgumentException( "Invalid prop." );
		}

		return $release;
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
				'release__not_in' => array( $release->get_pk() )
			) );

			$activations = array_filter( $query->get_results(), function ( Activation $activation ) use ( $release ) {

				if ( ! $activation->get_release() ) {
					return true;
				}

				return version_compare( $activation->get_release()->get_version(), $release->get_version(), '<' );
			} );

			$html = '<ul>';

			/** @var Activation $activation */
			foreach ( $activations as $activation ) {
				$html .= '<li>' . "{$activation->get_location()} – v{$activation->get_release()->get_version()}" . '</li>';
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

		if ( empty( $subject ) ) {
			wp_send_json_error( array(
				'message' => __(
					"A subject is required. You want people to open this email, right?",
					Plugin::SLUG
				)
			) );
		}

		if ( empty( $message ) ) {
			wp_send_json_error( array(
				'message' => __(
					"A message is required. What's an email without a body?",
					Plugin::SLUG
				)
			) );
		}

		if ( ! wp_verify_nonce( $nonce, "itelic-update-release-$release" ) ) {
			wp_send_json_error( array(
				'message' => __( "Request expired. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( "You don't have permission to do this.", Plugin::SLUG )
			) );
		}

		$release = itelic_get_release( $release );

		$notifications = $this->get_notifications( $release, $message, $subject );

		if ( empty( $notifications ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					__( "All customers have already updated to version %s or later.", Plugin::SLUG ),
					$release->get_version()
				)
			) );
		}

		try {
			$queue = \ITELIC\get_queue_processor( 'itelic-outdated-customers' );
			$queue->process( $notifications, \ITELIC\get_notification_strategy() );
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success( array(
			'message' => sprintf( __( "Notifications to %d customers have been queued for sending", Plugin::SLUG ), count( $notifications ) )
		) );
	}

	/**
	 * Get notifications.
	 *
	 * @since 1.0
	 *
	 * @param Release $release
	 * @param string  $message
	 * @param string  $subject
	 *
	 * @return Notification[]
	 */
	public function get_notifications( Release $release, $message, $subject ) {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$atn = Manager::get( 'itelic-activations' )->get_table_name( $wpdb );
		$ktn = Manager::get( 'itelic-keys' )->get_table_name( $wpdb );
		$rtn = Manager::get( 'itelic-releases' )->get_table_name( $wpdb );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT DISTINCT k.customer FROM $atn a JOIN $ktn k ON ( a.lkey = k.lkey AND k.`product` = %d )
			 WHERE a.status = %s AND a.release_id IN (
			 SELECT r.ID FROM $rtn r WHERE r.product = %d AND r.`start_date` < %s )",
			$release->get_product()->ID, Activation::ACTIVE,
			$release->get_product()->ID, $release->get_start_date()->format( 'Y-m-d H:i:s' )
		) );

		if ( empty( $results ) ) {
			return array();
		}

		$notifications = array();

		foreach ( $results as $result ) {

			$to = get_user_by( 'id', $result->customer );

			if ( ! $to instanceof \WP_User ) {
				continue;
			}

			$notification = new Notification( $to, Factory::make( 'itelic-outdated-customers' ), $message, $subject );
			$notification->add_data_source( $release );

			$notifications[] = $notification;
		}

		return $notifications;
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0
	 */
	private function enqueue() {

		$release = itelic_get_release( $_GET['ID'] );

		wp_enqueue_style( 'itelic-admin-releases-edit' );
		wp_enqueue_script( 'itelic-admin-releases-edit' );
		wp_localize_script( 'itelic-admin-releases-edit', 'ITELIC', array(
			'prevVersion'    => __( "Previous version: %s", Plugin::SLUG ),
			'uploadTitle'    => __( "Choose Software File", Plugin::SLUG ),
			'uploadButton'   => __( "Replace File", Plugin::SLUG ),
			'uploadLabel'    => __( "Upload File", Plugin::SLUG ),
			'lessUpgrade'    => __( "Less", Plugin::SLUG ),
			'moreUpgrade'    => __( "More", Plugin::SLUG ),
			'saving'         => __( "Saving", Plugin::SLUG ),
			'ibdLoadOn'      => 'loadCharts',
			'statuses'       => Release::get_statuses(),
			'types'          => Release::get_types( true ),
			'release'        => $_GET['ID'],
			'update_nonce'   => wp_create_nonce( 'itelic-update-release-' . $_GET['ID'] ),
			'ok'             => __( "Ok", Plugin::SLUG ),
			'cancel'         => __( "Cancel", Plugin::SLUG ),
			'currentVersion' => $release->get_product()->get_feature(
				'licensing', array( 'field' => 'version' ) )
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

		$raw = $release->get_first_14_days_of_upgrades();

		$now = new \DateTime();

		$diff = $release->get_start_date()->diff( $now );

		$days = min( 14, max( $diff->days + 1, 1 ) );

		$data = array();

		$day = \ITELIC\convert_gmt_to_local( clone $release->get_start_date() );

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
			'ibdLoadOn'         => 'loadProgressChart',
			'responsive'        => true
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

		$results = $release->get_top_5_previous_versions();

		$chart = new Chart\Pie( 698, 200, array(
			'ibdLoadOn'       => 'loadVersionsChart',
			'ibdShowLegend'   => '#pie-chart-legend',
			'tooltipTemplate' => '<%= value %> install<%if (value != 1){%>s<%}%>',
			'responsive'      => true
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

		$i = 0;

		foreach ( $results as $version => $count ) {

			$label = empty( $version ) ? __( "Unknown", Plugin::SLUG ) : "v{$version}";

			$chart->add_data_set( $count, $label, $colors[ $i ] );

			$i ++;
		}

		return $chart;
	}
}