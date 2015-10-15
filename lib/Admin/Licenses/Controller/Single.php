<?php
/**
 * Single license controller.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Licenses\Controller;

use ITELIC\Activation;
use ITELIC\Admin\Licenses\Controller;
use ITELIC\Admin\Licenses\Dispatch;
use ITELIC\Admin\Licenses\View\Single as Single_View;
use ITELIC\Key;
use ITELIC\Plugin;
use ITELIC\Renewal;
use ITELIC\Query\Renewals;

/**
 * Class Single
 * @package ITELIC\Admin\Licenses\Controller
 */
class Single extends Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {

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

		$key   = $_POST['key'];
		$prop  = $_POST['prop'];
		$val   = sanitize_text_field( $_POST['val'] );
		$nonce = $_POST['nonce'];

		try {
			$out = $this->do_update( itelic_get_key( $key ), $prop, $val, $nonce );
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success();
	}

	/**
	 * Perform the update on the key.
	 *
	 * @since 1.0
	 *
	 * @param Key    $key
	 * @param string $prop
	 * @param string $val
	 * @param string $nonce
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException
	 */
	public function do_update( Key $key, $prop, $val, $nonce ) {

		if ( ! wp_verify_nonce( $nonce, "itelic-update-key-{$key->get_key()}" ) ) {
			throw new \InvalidArgumentException( __( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \InvalidArgumentException( __( "Sorry, you don't have permission to do this.", Plugin::SLUG ) );
		}

		switch ( $prop ) {
			case 'status':
				$key->set_status( $val );
				break;
			case 'max':
				$key->set_max( $val );
				break;
			case 'expires':
				$date = \ITELIC\make_local_time( $val );
				$date = \ITELIC\convert_local_to_gmt( $date );
				$key->set_expires( $date );
				break;
			default:
				throw new \InvalidArgumentException( __( "Invalid request format.", Plugin::SLUG ) );
		}

		return true;
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
		$key      = $_POST['key'];
		$nonce    = $_POST['nonce'];

		try {
			$activation = $this->do_activation( itelic_get_key( $key ), $location, $nonce );
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success( array(
			'html' => $this->get_view()->get_activation_row_html( $activation )
		) );
	}

	/**
	 * Perform the activation.
	 *
	 * @since 1.0
	 *
	 * @param Key    $key
	 * @param string $location
	 * @param string $nonce
	 *
	 * @return Activation
	 *
	 * @throws \InvalidArgumentException|\UnexpectedValueException on error.
	 */
	public function do_activation( Key $key, $location, $nonce ) {

		if ( ! wp_verify_nonce( $nonce, "itelic-remote-activate-key-{$key->get_key()}" ) ) {
			throw new \InvalidArgumentException( __( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \InvalidArgumentException( __( "Sorry, you don't have permission to do this.", Plugin::SLUG ) );
		}

		$record = itelic_activate_license_key( $key, $location );

		if ( ! $record instanceof Activation ) {
			throw new \UnexpectedValueException( __( "Something went wrong. Please refresh and try again.", Plugin::SLUG ) );
		}

		return $record;
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
		$nonce = $_POST['nonce'];

		try {
			$activation = $this->do_deactivation( itelic_get_activation( $id ), $nonce );
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success( array(
			'html' => $this->get_view()->get_activation_row_html( $activation )
		) );
	}

	/**
	 * Do the deactivation.
	 *
	 * @param Activation $activation
	 * @param string     $nonce
	 *
	 * @return Activation
	 *
	 * @throws \InvalidArgumentException on error.
	 */
	public function do_deactivation( Activation $activation, $nonce ) {

		if ( ! wp_verify_nonce( $nonce, "itelic-remote-deactivate-{$activation->get_pk()}" ) ) {
			throw new \InvalidArgumentException( __( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \InvalidArgumentException( __( "Sorry, you don't have permission to do this.", Plugin::SLUG ) );
		}

		$activation->deactivate();

		return $activation;
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
		$nonce = $_POST['nonce'];

		try {
			$this->do_delete( itelic_get_activation( $id ), $nonce );
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success();
	}

	/**
	 * Delete the activation.
	 *
	 * @param Activation $activation
	 * @param string     $nonce
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException
	 */
	public function do_delete( Activation $activation, $nonce ) {

		if ( ! wp_verify_nonce( $nonce, "itelic-remote-delete-{$activation->get_pk()}" ) ) {
			throw new \InvalidArgumentException( __( "Sorry, this page has expired. Please refresh and try again.", Plugin::SLUG ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \InvalidArgumentException( __( "Sorry, you don't have permission to do this.", Plugin::SLUG ) );
		}

		$activation->delete();

		return true;
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

		return itelic_get_key( $key );
	}
}