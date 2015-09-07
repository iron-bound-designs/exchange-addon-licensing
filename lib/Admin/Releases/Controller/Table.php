<?php
/**
 * Table view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\Controller;

use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Plugin;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Table
 * @package ITELIC\Admin\Licenses\Controller
 */
class Table extends \WP_List_Table {

	/**
	 * Hold array of key data.
	 *
	 * @var array
	 */
	private $keys;

	/**
	 * @var int
	 */
	private $total;

	/**
	 * Set up data.
	 *
	 * Use parent constructor and populate custom fields.
	 *
	 * @param array $keys
	 * @param int   $total
	 */
	function __construct( $keys, $total ) {
		$this->keys  = $keys;
		$this->total = $total;

		//Set parent defaults
		parent::__construct( array(
				'singular' => 'release', //singular name of the listed records
				'plural'   => 'releases', //plural name of the listed records
				'ajax'     => false //does this table support ajax?
			)
		);
	}

	/**
	 * Override the text when no items are found.
	 */
	public function no_items() {
		_e( "No releases found", Plugin::SLUG );
	}

	/**
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column.
	 *
	 * @param array  $item        A singular item (one full row's worth of data)
	 * @param string $column_name The name/slug of the column to be processed
	 *
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	public function column_default( $item, $column_name ) {
		if ( isset( $item[ $column_name ] ) ) {
			return $item[ $column_name ];
		} else {
			return '';
		}
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="ID[]" value="%s" />', $item['ID']
		);
	}

	/**
	 * Render the ID column.
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_ID( $item ) {

		$view_link = Dispatch::get_tab_link( 'releases' );
		$view_link = add_query_arg( array(
			'view' => 'single',
			'ID'   => $item['ID']
		), $view_link );

		return "<a href=\"$view_link\">{$item['ID']}</a>";
	}

	/**
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function get_columns() {
		$columns = array();

		if ( empty( $this->keys ) ) {
			return $columns;
		}

		$columns['cb'] = '<input type="checkbox" />';

		foreach ( $this->keys[0] as $key => $value ) {
			$columns[ $key ] = ucwords( str_replace( "_", " ", $key ) );
		}

		return $columns;
	}

	/**
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting.
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 */
	public function get_sortable_columns() {
		$sortable_columns = array();

		foreach ( $this->get_columns() as $column => $title ) {
			$sortable_columns[ $column ] = array( $column, false );
		}

		unset( $sortable_columns['cb'] );

		return $sortable_columns;
	}

	/**
	 * Prepare data for display.
	 *
	 * Sets up pagination and sorting.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/**
		 * Now we can add our sorted data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $this->keys;

		/**
		 * We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
				'total_items' => $this->total,
				// we have to calculate the total number of items
				'per_page'    => $this->get_items_per_page( 'itelic_releases_list_table_per_page' ),
				// we have to determine how many items to show on a page
				'total_pages' => ceil( $this->total / $this->get_items_per_page( 'itelic_releases_list_table_per_page' ) )
				// we have to calculate the total number of pages
			)
		);
	}
}