<?php
/**
 * File Description
 *
 * @author Iron Bound Designs
 * @since
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class ITELIC_Admin_Licenses_Controller_Table
 */
class ITELIC_Admin_Licenses_Controller_Table extends WP_List_Table {

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
				'singular' => 'student', //singular name of the listed records
				'plural'   => 'students', //plural name of the listed records
				'ajax'     => false //does this table support ajax?
			)
		);
	}

	/**
	 * Override the text when no items are found.
	 */
	public function no_items() {
		_e( "No license keys found", ITELIC::SLUG );
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
			'<input type="checkbox" name="key[]" value="%s" />', $item['key']
		);
	}

	/**
	 * Add a "view" link to the key column.
	 *
	 * @param $item array
	 *
	 * @return string
	 */
	public function column_key( $item ) {
		//Build row actions
		$actions = array(
			'view' => sprintf( '<a href="%1$s">%2$s</a>', itelic_get_admin_edit_key_link( $item['key'] ), __( "View", ITELIC::SLUG ) )
		);

		//Return the title contents
		return sprintf( '%1$s %2$s',
			/*$1%s*/
			$item['key'],
			/*$2%s*/
			$this->row_actions( $actions )
		);
	}

	/**
	 * Add an extend link to the expires column.
	 *
	 * @since 1.0
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_expires( $item ) {
		//Build row actions
		$actions = array(
			'extend' => sprintf( '<a href="javascript:" data-key="%1$s" data-nonce="%2$s">%3$s</a>', $item['key'],
				wp_create_nonce( 'itelic-extend-key-' . $item['key'] ), __( "Extend", ITELIC::SLUG ) )
		);

		//Return the title contents
		return sprintf( '%1$s %2$s',
			/*$1%s*/
			'<span class="expires-date">' . $item['expires'] . '</span>',
			/*$2%s*/
			$this->row_actions( $actions )
		);
	}

	/**
	 * Add increase/decrease links to the max active column.
	 *
	 * @since 1.0
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_max_active( $item ) {
		//Build row actions
		$actions = array(
			'decrease' => sprintf( '<a href="javascript:" data-dir="down" data-key="%1$s" data-nonce="%2$s">%3$s</a>', $item['key'],
				wp_create_nonce( "itelic-max-key-" . $item['key'] ),
				__( "Decrease", ITELIC::SLUG ) ),
			'increase' => sprintf( '<a href="javascript:" data-dir="up" data-key="%1$s" data-nonce="%2$s">%3$s</a>', $item['key'],
				wp_create_nonce( "itelic-max-key-" . $item['key'] ),
				__( "Increase", ITELIC::SLUG ) )
		);

		//Return the title contents
		return sprintf( '%1$s %2$s',
			/*$1%s*/
			'<span class="max-active-count">' . $item['max_active'] . '</span>',
			/*$2%s*/
			$this->row_actions( $actions )
		);
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
		unset( $sortable_columns['active_installs'] );

		return $sortable_columns;
	}

	/**
	 * Retrieve bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'extend'       => __( "Extend", ITELIC::SLUG ),
			'send_renewal' => __( "Send Renewal Notice", ITELIC::SLUG ),
			'delete'       => __( "Delete", ITELIC::SLUG )
		);

		return $actions;
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
				'per_page'    => $this->get_items_per_page( 'itelic_licenses_list_table_per_page' ),
				// we have to determine how many items to show on a page
				'total_pages' => ceil( $this->total / $this->get_items_per_page( 'itelic_licenses_list_table_per_page' ) )
				// we have to calculate the total number of pages
			)
		);
	}
}