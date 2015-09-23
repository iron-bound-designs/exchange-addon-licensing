<?php
/**
 * Single license controller.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Licenses\Controller;

use ITELIC\Activation;
use ITELIC\Admin\Licenses\Controller;
use ITELIC\Admin\Licenses\Dispatch;
use ITELIC\Admin\Licenses\View\Single as Single_View;
use ITELIC\Key;
use ITELIC\Plugin;
use ITELIC\Renewal;
use ITELIC_API\Query\Renewals;

/**
 * Class Single
 * @package ITELIC\Admin\Licenses\Controller
 */
class Single extends Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'load-exchange_page_it-exchange-licensing', array(
			$this,
			'add_screen_options'
		) );

		add_action( 'wp_ajax_itelic_admin_licenses_single_update', array(
			$this,
			'handle_ajax_update'
		) );
		add_action( 'wp_ajax_itelic_admin_licenses_single_activate', array(
			$this,
			'handle_ajax_activate'
		) );
		add_action( 'wp_ajax_itelic_admin_licenses_single_deactivate', array(
			$this,
			'handle_ajax_deactivate'
		) );
		add_action( 'wp_ajax_itelic_admin_licenses_single_delete', array(
			$this,
			'handle_ajax_delete'
		) );
	}

	/**
	 * Add help tabs.
	 *
	 * @since 1.0
	 */
	public function add_screen_options() {
		if ( Dispatch::is_current_view( 'single' ) ) {
			$screen = get_current_screen();
			// todo render help tabs
		}
	}

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$view = $this->get_view();

		$view->begin();
		$view->title();

		$view->tabs( 'licenses' );

		$view->render();

		$view->end();
	}

	/**
	 * Handle the AJAX request for updating information about this license key.
	 */
	public function handle_ajax_update() {

		if ( ! isset( $_POST['key'] ) || ! isset( $_POST['prop'] ) || ! isset( $_POST['val'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", Plugin::SLUG )
			) );
		}

		$key   = sanitize_text_field( $_POST['key'] );
		$prop  = sanitize_text_field( $_POST['prop'] );
		$val   = sanitize_text_field( $_POST['val'] );
		$nonce = sanitize_text_field( $_POST['nonce'] );

		if ( ! wp_verify_nonce( $nonce, "itelic-update-key-$key" ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, you don't have permission to do this.", Plugin::SLUG )
			) );
		}

		$key = itelic_get_key( $key );

		try {
			switch ( $prop ) {
				case 'status':
					$key->set_status( $val );
					break;
				case 'max':
					$key->set_max( $val );
					break;
				case 'expires':
					$date = new \DateTime( $val, new \DateTimeZone( get_option( 'timezone_string' ) ) );
					$key->set_expires( $date );
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
	 * Handle the AJAX request for remotely activating an install.
	 *
	 * @since 1.0
	 */
	public function handle_ajax_activate() {
		if ( ! isset( $_POST['location'] ) || ! isset( $_POST['key'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", Plugin::SLUG )
			) );
		}

		$location = sanitize_text_field( $_POST['location'] );
		$key      = sanitize_text_field( $_POST['key'] );
		$nonce    = sanitize_text_field( $_POST['nonce'] );

		if ( ! wp_verify_nonce( $nonce, "itelic-remote-activate-key-$key" ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, you don't have permission to do this.", Plugin::SLUG )
			) );
		}

		try {
			$record = itelic_activate_license_key( itelic_get_key( $key ), $location );
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		if ( ! $record instanceof Activation ) {
			wp_send_json_error( array(
				'message' => __( "Something went wrong. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		wp_send_json_success( array(
			'html' => $this->get_view()->get_activation_row_html( $record )
		) );
	}

	/**
	 * Handle deactivating a location installation.
	 *
	 * @since 1.0
	 */
	public function handle_ajax_deactivate() {
		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", Plugin::SLUG )
			) );
		}

		$id    = abs( $_POST['id'] );
		$nonce = sanitize_text_field( $_POST['nonce'] );

		if ( ! wp_verify_nonce( $nonce, "itelic-remote-deactivate-$id" ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, you don't have permission to do this.", Plugin::SLUG )
			) );
		}

		$record = itelic_get_activation( $id );

		if ( ! $record ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, we couldn't find that activation record. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		try {
			$record->deactivate();
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success( array(
			'html' => $this->get_view()->get_activation_row_html( $record )
		) );
	}

	/**
	 * Handle deactivating a location installation.
	 *
	 * @since 1.0
	 */
	public function handle_ajax_delete() {
		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", Plugin::SLUG )
			) );
		}

		$id    = abs( $_POST['id'] );
		$nonce = sanitize_text_field( $_POST['nonce'] );

		if ( ! wp_verify_nonce( $nonce, "itelic-remote-delete-$id" ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, you don't have permission to do this.", Plugin::SLUG )
			) );
		}

		$record = itelic_get_activation( $id );

		if ( ! $record ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, we couldn't find that activation record. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		try {
			$record->delete();
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success( array(
			'html' => $this->get_view()->get_activation_row_html( $record )
		) );
	}

	/**
	 * Get the view object.
	 *
	 * @since 1.0
	 *
	 * @return Single_View
	 */
	protected function get_view() {
		return new Single_View( $this->get_current_key(), $this->get_renewals() );
	}

	/**
	 * Get all the renewals for this key.
	 *
	 * @since 1.0
	 *
	 * @return Renewal[]
	 */
	protected function get_renewals() {
		$query = new Renewals( array(
			'key'   => $this->get_current_key()->get_key(),
			'order' => array(
				'renewal_date' => 'ASC'
			)
		) );

		return $query->get_results();
	}

	/**
	 * Get the currently displayed key.
	 *
	 * @since 1.0
	 *
	 * @return Key
	 */
	protected function get_current_key() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$key = $_POST['key'];
		} elseif ( isset( $_GET['key'] ) ) {
			$key = $_GET['key'];
		} else {
			return null;
		}

		return Key::with_key( $key );
	}
}