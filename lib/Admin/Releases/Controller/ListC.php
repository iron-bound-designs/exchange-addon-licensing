<?php
/**
 * Controller for rendering the classes section.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Releases\Controller;

use ITELIC\Admin\Releases\Controller;
use ITELIC\Admin\Releases\Dispatch;
use ITELIC\Admin\Releases\View\ListV;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;
use ITELIC\Key;
use ITELIC\Release;
use ITELIC\Query\Releases;

/**
 * Class ListC
 * @package ITELIC\Admin\Releases\Controller
 */
class ListC extends Controller {

	/**
	 * @var \WP_List_Table
	 */
	protected $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'load-exchange_page_it-exchange-licensing', array( $this, 'add_screen_options' ) );
		add_action( 'load-exchange_page_it-exchange-licensing', array( $this, 'setup_table' ) );
	}

	/**
	 * Register screen options for manage members page.
	 */
	public function add_screen_options() {
		if ( Dispatch::is_current_view( 'list' ) ) {
			add_screen_option( 'per_page', array(
				'label'   => __( "Releases", Plugin::SLUG ),
				'default' => 20,
				'option'  => 'itelic_releases_list_table_per_page'
			) );
		}
	}

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {

		wp_enqueue_script( 'itelic-admin-releases-list' );
		wp_enqueue_style( 'itelic-admin-releases-list' );

		$view = new ListV( $this->get_table() );

		$view->begin();
		$view->title();

		$view->tabs( 'releases' );

		$view->render();

		$view->end();
	}

	/**
	 * Setup the table.
	 */
	public function setup_table() {

		if ( ! Dispatch::is_current_view( 'list' ) ) {
			return;
		}

		$query    = new Releases( $this->generate_query_args() );
		$releases = $query->get_results();
		$total    = $query->get_total_items();
		$products = itelic_get_products_with_licensing_enabled();

		$this->table = new Table( $this->prepare_data( $releases ), $total, $products );
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
	 * @param Release[] $data
	 *
	 * @return array
	 */
	protected function prepare_data( $data ) {
		$prepared = array();

		foreach ( $data as $key ) {
			$prepared[] = $this->prepare_record( $key );
		}

		return $prepared;
	}

	/**
	 * Prepare an individual key view.
	 *
	 * @since 1.0
	 *
	 * @param Release $release
	 *
	 * @return array
	 */
	protected function prepare_record( Release $release ) {

		if ( $release->get_start_date() ) {
			$start_date = $release->get_start_date()->format( get_option( 'date_format' ) );
		} else {
			$start_date = '-';
		}

		$updated           = $release->get_total_updated();
		$total_activations = $release->get_total_active_activations();
		$total_activations = max( 1, $total_activations );

		$percent = min( number_format( $updated / $total_activations * 100, 0 ), 100 );

		$data = array(
			'ID'         => $release->get_ID(),
			'release'    => (string) $release,
			'status'     => $release->get_status( true ),
			'type'       => $release->get_type( true ),
			'updated'    => "$percent%",
			'start_date' => $start_date
		);

		/**
		 * Filter the columns on the releases list table.
		 *
		 * @since 1.0
		 *
		 * @param array   $data
		 * @param Release $release
		 */
		$data = apply_filters( 'itelic_releases_list_table_columns', $data, $release );

		return $data;
	}

	/**
	 * Get the args passed to itelic_get_keys()
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function generate_query_args() {

		$args = array(
			'items_per_page' => $this->get_items_per_page( 'itelic_releases_list_table_per_page' ),
			'page'           => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
		);

		if ( isset( $_GET['orderby'] ) ) {
			$args['order'] = array(
				$_GET['orderby'] => isset( $_GET['order'] ) ? $_GET['order'] : 'ASC'
			);
		}

		if ( isset( $_GET['prod'] ) ) {
			$args['product'] = absint( $_GET['prod'] );
		}

		if ( isset( $_GET['status'] ) ) {
			$args['status'] = $_GET['status'];
		}

		if ( ! empty( $_GET['type'] ) ) {
			$args['type'] = $_GET['type'];
		}

		if ( isset( $_GET['s'] ) ) {

			$s = $_GET['s'];

			if ( strpos( $s, 'v' ) === 0 ) {
				$s = substr( $s, 1 );

				$args['version_search'] = "%{$s}%";
			} else {
				$args['changelog_search'] = "%{$s}%";
			}
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