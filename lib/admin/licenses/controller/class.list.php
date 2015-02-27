<?php
/**
 * Controller for rendering the classes section.
 *
 * @author Iron Bound Designs
 * @since
 */

/**
 * Class ITELIC_Admin_Licenses_Controller_List
 */
class ITELIC_Admin_Licenses_Controller_List extends ITELIC_Admin_Licenses_Controller {

	/**
	 * @var WP_List_Table
	 */
	protected $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'load-exchange_page_it-exchange-licensing', array( $this, 'add_screen_options' ) );
		add_action( 'load-exchange_page_it-exchange-licensing', array( $this, 'setup_table' ) );

		add_action( 'wp_ajax_itelic_admin_licenses_list_extend', array( $this, 'handle_ajax_extend' ) );
		add_action( 'wp_ajax_itelic_admin_licenses_list_max', array( $this, 'handle_ajax_max' ) );
	}

	/**
	 * Register screen options for manage members page.
	 */
	public function add_screen_options() {
		if ( ITELIC_Admin_Licenses_Dispatch::is_current_view( 'list' ) ) {
			add_screen_option( 'per_page', array(
				'label'   => __( "Keys", ITELIC::SLUG ),
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

		$view = new ITELIC_Admin_Licenses_View_List( $this->get_table() );

		$view->begin();
		$view->title();

		$view->tabs( 'licenses' );

		$view->render();

		$view->end();
	}

	/**
	 * Handle the AJAX request to extend a license expiration date.
	 *
	 * @since 1.0
	 */
	public function handle_ajax_extend() {

		if ( ! isset( $_POST['key'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", ITELIC::SLUG )
			) );
		}

		$key   = sanitize_text_field( $_POST['key'] );
		$nonce = sanitize_text_field( $_POST['nonce'] );

		if ( ! wp_verify_nonce( $nonce, "itelic-extend-key-$key" ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, this page has expired. Please refresh and try again.", ITELIC::SLUG )
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, you don't have permission to do this.", ITELIC::SLUG )
			) );
		}

		$key = itelic_get_key( $key );

		if ( ! $key instanceof ITELIC_Key ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, we couldn't find that key. Please refresh and try again.", ITELIC::SLUG )
			) );
		}

		try {
			$key->extend();
		}
		catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => $e->getMessage()
			) );
		}

		wp_send_json_success( array(
			'expires' => $key->get_expires() === null ? __( "Forever", ITELIC::SLUG ) : $key->get_expires()->format( get_option( 'date_format' ) )
		) );
	}

	/**
	 * Handle the AJAX request for altering the max number of activations.
	 */
	public function handle_ajax_max() {

		if ( ! isset( $_POST['key'] ) || ! isset( $_POST['nonce'] ) || ! isset( $_POST['dir'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", ITELIC::SLUG )
			) );
		}

		$key   = sanitize_text_field( $_POST['key'] );
		$nonce = sanitize_text_field( $_POST['nonce'] );
		$dir   = strtolower( sanitize_text_field( $_POST['dir'] ) );

		if ( $dir == 'up' ) {
			$alter = 1;
		} elseif ( $dir == 'down' ) {
			$alter = - 1;
		} else {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", ITELIC::SLUG )
			) );
		}

		if ( ! wp_verify_nonce( $nonce, "itelic-max-key-$key" ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, this page has expired. Please refresh and try again.", ITELIC::SLUG )
			) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, you don't have permission to do this.", ITELIC::SLUG )
			) );
		}

		$key = itelic_get_key( $key );

		if ( ! $key instanceof ITELIC_Key ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, we couldn't find that key. Please refresh and try again.", ITELIC::SLUG )
			) );
		}

		try {
			$key->set_max( $key->get_max() + $alter );
		}
		catch ( Exception $e ) {
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

		if ( ! ITELIC_Admin_Licenses_Dispatch::is_current_view( 'list' ) ) {
			return;
		}

		if ( ! isset( $_GET['orderby'] ) ) {
			$_GET['orderby'] = 'transaction';
		}

		$this->table = new ITELIC_Admin_Licenses_Controller_Table( $this->prepare_data( $this->get_keys() ), $this->get_total_keys() );
	}

	/**
	 * Get the list table for the licenses list.
	 *
	 * @since 1.0
	 *
	 * @return WP_List_Table
	 */
	protected function get_table() {
		return $this->table;
	}

	/**
	 * Prepare data for display.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_Key[] $data
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
	 * @param ITELIC_Key $key
	 *
	 * @return array
	 */
	protected function prepare_key( ITELIC_Key $key ) {
		$data = array(
			'key'             => $key->get_key(),
			'status'          => $key->get_status( true ),
			'product'         => '<a href="' . get_edit_post_link( $key->get_product()->ID ) . '">' . $key->get_product()->post_title . '</a>',
			'customer'        => $key->get_customer()->wp_user->display_name,
			'expires'         => $key->get_expires() === null ? __( "Forever", ITELIC::SLUG ) : $key->get_expires()->format( get_option( 'date_format' ) ),
			'active_installs' => $key->get_count(),
			'max_active'      => $key->get_max(),
			'transaction'     => '<a href="' . get_edit_post_link( $key->get_transaction()->ID ) . '">'
			                     . it_exchange_get_transaction_order_number( $key->get_transaction() ) . '</a>'
		);

		return $data;
	}

	/**
	 * Get keys.
	 *
	 * @since 1.0
	 *
	 * @return ITELIC_Key[]
	 */
	protected function get_keys() {
		return itelic_get_keys( $this->generate_get_key_args() );
	}

	/**
	 * @return int|ITELIC_Key[]
	 */
	protected function get_total_keys() {
		$args = $this->generate_get_key_args();

		$args['get_row_count'] = true;

		return itelic_get_keys( $args );
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
			'count' => $this->get_items_per_page( 'itelic_licenses_list_table_per_page' )
		);

		if ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
			$args['offset'] = $args['count'] * ( absint( $_GET['paged'] ) - 1 );
		}

		if ( isset( $_GET['s'] ) ) {
			$search = sanitize_text_field( $_GET['s'] );

			if ( strpos( $search, '@' ) !== false ) {
				$args['customer_email'] = "$search";
			} else {
				$args['key_search'] = "$search";
			}
		}

		if ( isset( $_GET['orderby'] ) ) {
			$args['orderby'] = sanitize_text_field( $_GET['orderby'] );

			if ( $args['orderby'] == 'max_active' ) {
				$args['orderby'] = 'max';
			}
		}

		if ( isset( $_GET['order'] ) ) {
			$args['order'] = sanitize_text_field( $_GET['order'] );
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