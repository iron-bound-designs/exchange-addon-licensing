<?php
/**
 * Controller for rendering the classes section.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Licenses\Controller;

use ITELIC\Admin\Licenses\Controller;
use ITELIC\Admin\Licenses\Dispatch;
use ITELIC\Admin\Licenses\View\ListV;
use ITELIC\Plugin;
use ITELIC\Admin\Tab\View;
use ITELIC\Key;
use ITELIC\Admin\Tab\Dispatch as Tab_Dispatch;
use ITELIC_API\Query\Keys;

/**
 * Class ListC
 *
 * @package ITELIC\Admin\Licenses\Controller
 */
class ListC extends Controller {

	/**
	 * @var \WP_List_Table
	 */
	protected $table;

	/**
	 * @var array
	 */
	private $message = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'load-exchange_page_it-exchange-licensing', array( $this, 'add_screen_options' ) );
		add_action( 'load-exchange_page_it-exchange-licensing', array( $this, 'setup_table' ) );

		add_action( 'admin_init', array( $this, 'process_delete_row_action' ) );

		add_action( 'wp_ajax_itelic_admin_licenses_list_extend', array( $this, 'handle_ajax_extend' ) );
		add_action( 'wp_ajax_itelic_admin_licenses_list_max', array( $this, 'handle_ajax_max' ) );

		if ( ! empty( $_GET['msg'] ) && $_GET['msg'] == 'deleted' ) {
			$this->message[ View::NOTICE_SUCCESS ] = __( "Key successfully deleted.", Plugin::SLUG );
		}
	}

	/**
	 * Register screen options for manage members page.
	 */
	public function add_screen_options() {
		if ( Dispatch::is_current_view( 'list' ) ) {
			add_screen_option( 'per_page', array(
				'label'   => __( "Keys", Plugin::SLUG ),
				'default' => 20,
				'option'  => 'itelic_licenses_list_table_per_page'
			) );
		}
	}

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {

		wp_enqueue_style( 'itelic-admin-licenses-list' );
		wp_enqueue_script( 'itelic-admin-licenses-list' );
		wp_localize_script( 'itelic-admin-licenses-list', 'ITELIC', array(
			'ajax' => admin_url( 'admin-ajax.php' )
		) );

		$view = new ListV( $this->get_table() );

		$view->begin();
		$view->title();

		if ( ! empty( $this->message ) ) {
			$view->notice( reset( $this->message ), key( $this->message ) );
		}

		$view->tabs( 'licenses' );

		$view->render();

		$view->end();
	}

	/**
	 * Process the bulk actions.
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function process_bulk_actions() {
		$action = $this->current_action();

		if ( empty( $action ) ) {
			return '';
		}

		if ( empty( $_GET['key'] ) ) {
			throw new \Exception( sprintf( __( "You must select keys to %s", Plugin::SLUG ), $action ) );
		}

		/**
		 * @var Key[] $keys
		 */
		$keys = array_map( 'itelic_get_key', $_GET['key'] );

		switch ( $action ) {

			case 'extend':
				foreach ( $keys as $key ) {
					$key->extend();
				}

				return sprintf( __( "Extended %d keys", Plugin::SLUG ), count( $keys ) );

			case 'delete':
				foreach ( $keys as $key ) {
					$key->delete();
				}

				return sprintf( __( "Deleted %d keys", Plugin::SLUG ), count( $keys ) );
		}

		return '';
	}

	/**
	 * Get the current action.
	 *
	 * @since 1.0
	 *
	 * @return bool|string
	 */
	protected function current_action() {

		if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) ) {
			return false;
		}

		if ( isset( $_REQUEST['action'] ) && - 1 != $_REQUEST['action'] ) {
			return $_REQUEST['action'];
		}

		if ( isset( $_REQUEST['action2'] ) && - 1 != $_REQUEST['action2'] ) {
			return $_REQUEST['action2'];
		}

		return false;
	}

	/**
	 * Process a request to delete a key as a row action.
	 *
	 * @since 1.0
	 */
	public function process_delete_row_action() {

		if ( ! Dispatch::is_current_view( 'list' ) ) {
			return;
		}

		if ( ! isset( $_GET['itelic_action'] ) || $_GET['itelic_action'] != 'delete' ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$key = $_GET['key'];

		if ( ! wp_verify_nonce( $_GET['nonce'], 'itelic-delete-license-' . $key ) ) {
			return;
		}

		try {
			itelic_get_key( $key )->delete();

			wp_redirect( add_query_arg( 'msg', 'deleted', Tab_Dispatch::get_tab_link( 'licenses' ) ) );
			exit;
		}
		catch ( \Exception $e ) {
			return;
		}
	}

	/**
	 * Handle the AJAX request to extend a license expiration date.
	 *
	 * @since 1.0
	 */
	public function handle_ajax_extend() {

		if ( ! isset( $_POST['key'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", Plugin::SLUG )
			) );
		}

		$key   = $_POST['key'];
		$nonce = $_POST['nonce'];

		if ( ! wp_verify_nonce( $nonce, "itelic-extend-key-$key" ) ) {
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

		if ( ! $key instanceof Key ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, we couldn't find that key. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		try {
			$key->extend();
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success( array(
			'expires' => $key->get_expires() === null ? __( "Forever", Plugin::SLUG ) : $key->get_expires()->format( get_option( 'date_format' ) ),
			'status'  => $key->get_status( true )
		) );
	}

	/**
	 * Handle the AJAX request for altering the max number of activations.
	 */
	public function handle_ajax_max() {

		if ( ! isset( $_POST['key'] ) || ! isset( $_POST['nonce'] ) || ! isset( $_POST['dir'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", Plugin::SLUG )
			) );
		}

		$key   = $_POST['key'];
		$nonce = $_POST['nonce'];
		$dir   = strtolower( sanitize_text_field( $_POST['dir'] ) );

		if ( $dir == 'up' ) {
			$alter = 1;
		} elseif ( $dir == 'down' ) {
			$alter = - 1;
		} else {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", Plugin::SLUG )
			) );
		}

		if ( ! wp_verify_nonce( $nonce, "itelic-max-key-$key" ) ) {
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

		if ( ! $key instanceof Key ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, we couldn't find that key. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		try {
			$key->set_max( $key->get_max() + $alter );
		}
		catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success( array(
			'max' => $key->get_max()
		) );
	}

	/**
	 * Setup the table.
	 */
	public function setup_table() {

		if ( ! Dispatch::is_current_view( 'list' ) ) {
			return;
		}

		if ( ! isset( $_GET['orderby'] ) ) {
			$_GET['orderby'] = 'transaction';
		}

		try {

			$msg = $this->process_bulk_actions();

			if ( $msg ) {
				$this->message[ View::NOTICE_SUCCESS ] = $msg;
			}
		}
		catch ( \Exception $e ) {
			$this->message[ View::NOTICE_ERROR ] = $e->getMessage();
		}

		$query    = new Keys( $this->generate_get_key_args() );
		$keys     = $query->get_results();
		$total    = $query->get_total_items();
		$products = itelic_get_products_with_licensing_enabled();

		$this->table = new Table( $this->prepare_data( $keys ), $total, $products );

		if ( isset( $_GET['s'] ) ) {
			$key = itelic_get_key( $_GET['s'] );

			if ( $key ) {
				wp_redirect( itelic_get_admin_edit_key_link( $key->get_key() ) );
				die();
			}
		}
	}

	/**
	 * Get the list table for the licenses list.
	 *
	 * @since 1.0
	 *
	 * @return \WP_List_Table
	 */
	protected function get_table() {
		return $this->table;
	}

	/**
	 * Prepare data for display.
	 *
	 * @since 1.0
	 *
	 * @param Key[] $data
	 *
	 * @return array
	 */
	protected function prepare_data( $data ) {
		$prepared = array();

		foreach ( $data as $key ) {
			$prepared[] = $this->prepare_key( $key );
		}

		return $prepared;
	}

	/**
	 * Prepare an individual key view.
	 *
	 * @since 1.0
	 *
	 * @param Key $key
	 *
	 * @return array
	 */
	protected function prepare_key( Key $key ) {
		$data = array(
			'key'             => $key->get_key(),
			'status'          => $key->get_status( true ),
			'product'         => '<a href="' . get_edit_post_link( $key->get_product()->ID ) . '">' . $key->get_product()->post_title . '</a>',
			'customer'        => $key->get_customer()->wp_user->display_name,
			'expires'         => $key->get_expires() === null ? __( "Forever", Plugin::SLUG ) : $key->get_expires()->format( get_option( 'date_format' ) ),
			'active_installs' => $key->get_active_count(),
			'max_active'      => $key->get_max(),
			'transaction'     => '<a href="' . get_edit_post_link( $key->get_transaction()->ID ) . '">'
			                     . it_exchange_get_transaction_order_number( $key->get_transaction() ) . '</a>'
		);

		return $data;
	}

	/**
	 * Get the args passed to itelic_get_keys()
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function generate_get_key_args() {

		$args = array(
			'items_per_page' => $this->get_items_per_page( 'itelic_licenses_list_table_per_page' ),
			'page'           => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
		);

		if ( isset( $_GET['orderby'] ) ) {
			$args['order'] = array(
				$_GET['orderby'] => isset( $_GET['order'] ) ? $_GET['order'] : 'ASC'
			);
		}

		if ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
			$args['offset'] = $args['items_per_page'] * ( absint( $_GET['paged'] ) - 1 );
		}

		if ( isset( $_GET['s'] ) ) {
			$args['customer_search'] = "%{$_GET['s']}%";
		}

		if ( ! empty( $_GET['prod'] ) ) {
			$args['product'] = absint( $_GET['prod'] );
		}

		if ( isset( $_GET['status'] ) ) {
			$args['status'] = $_GET['status'];
		}

		return $args;
	}

	/**
	 * Get number of items to display on a single page
	 *
	 * @since  3.1.0
	 * @access protected
	 *
	 * @param string $option
	 * @param int    $default
	 *
	 * @return int
	 */
	protected function get_items_per_page( $option, $default = 20 ) {
		$per_page = (int) get_user_option( $option );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $default;
		}

		/**
		 * Filter the number of items to be displayed on each page of the list table.
		 *
		 * The dynamic hook name, $option, refers to the `per_page` option depending
		 * on the type of list table in use. Possible values include: 'edit_comments_per_page',
		 * 'sites_network_per_page', 'site_themes_network_per_page', 'themes_network_per_page',
		 * 'users_network_per_page', 'edit_post_per_page', 'edit_page_per_page',
		 * 'edit_{$post_type}_per_page', etc.
		 *
		 * @since 2.9.0
		 *
		 * @param int $per_page Number of items to be displayed. Default 20.
		 */

		return (int) apply_filters( $option, $per_page );
	}
}