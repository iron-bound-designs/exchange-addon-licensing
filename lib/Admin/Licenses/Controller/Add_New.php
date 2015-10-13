<?php
/**
 * Add New License Controller.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Licenses\Controller;

use ITELIC\Admin\Licenses\Controller;
use ITELIC\Admin\Licenses\View\Add_New as Add_New_View;
use ITELIC\Admin\Tab\View;
use ITELIC\Key;
use ITELIC\Plugin;

/**
 * Class Add_New
 *
 * @package ITELIC\Admin\Licenses\Controller
 */
class Add_New extends Controller {

	/**
	 * @var array
	 */
	protected $message = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'load-exchange_page_it-exchange-licensing', array(
			$this,
			'create_key'
		) );
	}

	/**
	 * Create the key.
	 */
	public function create_key() {

		if ( ! isset( $_POST['itelic-add-new-key'] ) || ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'itelic-add-new-key' ) ) {
			$this->message[ View::NOTICE_ERROR ] = __( "Request expired. Please try again.", Plugin::SLUG );

			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->message[ View::NOTICE_ERROR ] = __( "You don't have permission to do this.", Plugin::SLUG );

			return;
		}

		if ( empty( $_POST['product'] ) ) {
			$this->message[ View::NOTICE_ERROR ] = __( "You must select a product.", Plugin::SLUG );

			return;
		}

		$product = absint( $_POST['product'] );

		if ( empty( $_POST['username'] ) ) {
			$customer = absint( $_POST['customer'] );
		} else {
			$customer = wp_insert_user( array(
				'user_login' => $_POST['username'],
				'user_email' => $_POST['email'],
				'first_name' => $_POST['first'],
				'last_name'  => $_POST['last'],
				'user_pass'  => wp_generate_password( 24, true, true )
			) );

			if ( is_wp_error( $customer ) ) {
				$this->message[ View::NOTICE_ERROR ] = $customer->get_error_message();

				return;
			}
		}

		$activations = intval( $_POST['activations'] );
		$expiration  = \ITELIC\make_local_time( $_POST['expiration'] );
		$key         = $_POST['license'];
		$paid        = $_POST['paid'];

		$key = itelic_create_key( array(
			'key'      => $key,
			'product'  => $product,
			'customer' => $customer,
			'paid'     => $paid,
			'limit'    => $activations,
			'expires'  => \ITELIC\convert_local_to_gmt( $expiration )
		) );

		if ( is_wp_error( $key ) ) {
			$this->message[ View::NOTICE_ERROR ] = $key->get_error_message();
		} else {

			/**
			 * Determine whether or not to send WP's new user notification
			 * when a key is manually created from the add new license key page.
			 *
			 * @since 1.0
			 *
			 * @param bool $send
			 * @param Key  $key
			 */
			$send_notification = apply_filters( 'itelic_send_new_user_notification_on_create_key', true, $key );

			if ( $send_notification ) {
				wp_new_user_notification( $customer, null, 'both' );
			}

			wp_redirect( itelic_get_admin_edit_key_link( $key->get_key() ) );
			exit;
		}
	}

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {

		wp_enqueue_script( 'itelic-admin-licenses-new' );
		wp_enqueue_style( 'itelic-admin-licenses-new' );

		$view = new Add_New_View( itelic_get_products_with_licensing_enabled() );

		$view->begin();
		$view->title();

		if ( ! empty( $this->message ) ) {
			$view->notice( reset( $this->message ), key( $this->message ) );
		}

		$view->tabs( 'licenses' );

		$view->render();

		$view->end();
	}
}