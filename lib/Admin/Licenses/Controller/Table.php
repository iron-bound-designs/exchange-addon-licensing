<?php
/**
 * Table view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Licenses\Controller;

use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Key;
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
	 * @var \IT_Exchange_Product[]
	 */
	private $products = array();

	/**
	 * Set up data.
	 *
	 * Use parent constructor and populate custom fields.
	 *
	 * @param array $keys
	 * @param int   $total
	 * @param array $products
	 */
	function __construct( $keys, $total, $products ) {
		$this->keys     = $keys;
		$this->total    = $total;
		$this->products = $products;

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
		_e( "No license keys found", Plugin::SLUG );
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

		$del_link = add_query_arg( array(
			'itelic_action' => 'delete',
			'key'           => $item['key'],
			'nonce'         => wp_create_nonce( 'itelic-delete-license-' . $item['key'] )
		), Dispatch::get_tab_link( 'licenses' )
		);

		//Build row actions
		$actions = array(
			'view'   => sprintf( '<a href="%1$s">%2$s</a>', itelic_get_admin_edit_key_link( $item['key'] ), __( "View", Plugin::SLUG ) ),
			'delete' => sprintf( '<a href="%1$s" class="submitdelete">%2$s</a>', $del_link, __( "Delete", Plugin::SLUG ) )
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
				wp_create_nonce( 'itelic-extend-key-' . $item['key'] ), __( "Extend", Plugin::SLUG ) )
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
				__( "Decrease", Plugin::SLUG ) ),
			'increase' => sprintf( '<a href="javascript:" data-dir="up" data-key="%1$s" data-nonce="%2$s">%3$s</a>', $item['key'],
				wp_create_nonce( "itelic-max-key-" . $item['key'] ),
				__( "Increase", Plugin::SLUG ) )
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
			'extend' => __( "Extend", Plugin::SLUG ),
			'delete' => __( "Delete", Plugin::SLUG )
		);

		return $actions;
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_views() {

		$statuses = Key::get_statuses();

		$statuses['any'] = __( "All", Plugin::SLUG );

		$links = array(
			'any'         =>
				sprintf( '<a href="%1$s">%2$s</a>', $this->get_view_link( 'any' ), $statuses['any'] ),
			Key::ACTIVE   =>
				sprintf( '<a href="%1$s">%2$s</a>', $this->get_view_link( Key::ACTIVE ), $statuses[ Key::ACTIVE ] ),
			Key::DISABLED =>
				sprintf( '<a href="%1$s">%2$s</a>', $this->get_view_link( Key::DISABLED ), $statuses[ Key::DISABLED ] ),
			Key::EXPIRED  =>
				sprintf( '<a href="%1$s">%2$s</a>', $this->get_view_link( Key::EXPIRED ), $statuses[ Key::EXPIRED ] )
		);

		$selected = isset( $_GET['status'] ) ? $_GET['status'] : 'any';

		$links[ $selected ] = "<strong>{$statuses[$selected]}</strong>";

		return $links;
	}

	/**
	 * Get the view link.
	 *
	 * @since 1.0
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	protected function get_view_link( $status ) {

		$link = Dispatch::get_tab_link( 'licenses' );

		$white_list = array( 'prod', 's' );

		foreach ( $white_list as $var ) {

			if ( isset( $_GET[ $var ] ) ) {
				$link = add_query_arg( $var, $_GET[ $var ], $link );
			}
		}

		return add_query_arg( 'status', $status, $link );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {

		if ( $which !== 'top' ) {
			return;
		}

		$selected_product = isset( $_GET['prod'] ) ? absint( $_GET['prod'] ) : 0;
		?>

		<label for="filter-by-product" class="screen-reader-text">
			<?php _e( "Filter by product", Plugin::SLUG ); ?>
		</label>

		<select name="prod" id="filter-by-product" style="width: 150px;">

			<option value="-1"><?php _e( "All products", Plugin::SLUG ); ?></option>

			<?php foreach ( $this->products as $product ): ?>

				<option value="<?php echo esc_attr( $product->ID ); ?>" <?php selected( $selected_product, $product->ID ); ?>>
					<?php echo $product->post_title; ?>
				</option>

			<?php endforeach; ?>

		</select>

		<?php submit_button( __( 'Filter' ), 'button', 'filter_action', false );
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