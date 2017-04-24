<?php
/**
 * List table for logs.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   MIT
 * @copyright Iron Bound Designs, 2015.
 */

namespace IronBound\DBLogger;

use Psr\Log\LogLevel;

/**
 * Class ListTable
 * @package IronBound\DBLogger
 */
class ListTable extends \WP_List_Table {

	/**
	 * @var array
	 */
	private $translations;

	/**
	 * @var AbstractTable
	 */
	private $table;

	/**
	 * @var string
	 */
	private $model_class;

	/**
	 * ListTable constructor.
	 *
	 * @param array|string  $args
	 * @param array         $translations
	 * @param AbstractTable $table
	 * @param string        $model_class
	 */
	public function __construct( $args, $translations, AbstractTable $table, $model_class ) {
		parent::__construct( $args );

		$this->table       = $table;
		$this->model_class = $model_class;

		$this->translations = wp_parse_args( $translations, array(
			'message'          => 'Message',
			'level'            => 'Level',
			'time'             => 'Time',
			'ip'               => 'IP',
			'user'             => 'User',
			'group'            => 'Group',
			'levelFilterLabel' => 'Filter by Level',
			'allLevels'        => 'All Levels',
			'filter'           => 'Filter'
		) );
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_columns() {

		return array(
			'message' => $this->translations['message'],
			'level'   => $this->translations['level'],
			'time'    => $this->translations['time'],
			'ip'      => $this->translations['ip'],
			'user'    => $this->translations['user'],
			'group'   => $this->translations['group'],
		);
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @since  3.1.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'time' => array( 'time', false ),
			'user' => array( 'user', false )
		);
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @return string The name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'message';
	}

	/**
	 * Render the default column.
	 *
	 * @param AbstractLog $item
	 * @param string      $column_name
	 */
	protected function column_default( $item, $column_name ) {

		if ( method_exists( $item, "get_{$column_name}" ) ) {
			echo call_user_func( array( $item, "get_{$column_name}" ) );
		} else {
			echo '-';
		}
	}

	/**
	 * Render the user column.
	 *
	 * @since 1.0
	 *
	 * @param AbstractLog $item
	 */
	public function column_user( AbstractLog $item ) {

		$user = $item->get_user();

		if ( empty( $user ) ) {
			echo '-';
		} else {
			echo $user->display_name;
		}
	}

	/**
	 * Render the time column.
	 *
	 * @since 1.0
	 *
	 * @param AbstractLog $item
	 */
	public function column_time( AbstractLog $item ) {

		$time = $item->get_time();

		if ( empty( $time ) ) {
			echo '-';
		} else {
			echo $time->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		}
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

		$this->months_dropdown( '' );

		$selected = isset( $_GET['level'] ) ? $_GET['level'] : '';
		?>

		<label for="filter-by-level" class="screen-reader-text">
			<?php echo $this->translations['levelFilterLabel']; ?>
		</label>

		<select name="level" id="filter-by-level">

			<option value=""><?php echo $this->translations['allLevels']; ?></option>

			<?php foreach ( $this->get_levels() as $level => $label ): ?>

				<option value="<?php echo esc_attr( $level ); ?>" <?php selected( $selected, $level ); ?>>
					<?php echo $label; ?>
				</option>

			<?php endforeach; ?>

		</select>

		<?php submit_button( $this->translations['filter'], 'button', 'filter_action', false );
	}

	/**
	 * Display a monthly dropdown for filtering items
	 *
	 * @since  3.1.0
	 * @access protected
	 *
	 * @global \wpdb      $wpdb
	 * @global \WP_Locale $wp_locale
	 *
	 * @param string      $post_type
	 */
	protected function months_dropdown( $post_type ) {
		global $wpdb, $wp_locale;

		$tn = $this->table->get_table_name( $wpdb );

		$months = $wpdb->get_results( "
			SELECT DISTINCT YEAR( time ) AS year, MONTH( time ) AS month
			FROM $tn
			ORDER BY time DESC
		" );

		$month_count = count( $months );

		if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
		<label for="filter-by-date" class="screen-reader-text"><?php _e( 'Filter by date' ); ?></label>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php _e( 'All dates' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 == $arc_row->year ) {
					continue;
				}

				$month = zeroise( $arc_row->month, 2 );
				$year  = $arc_row->year;

				printf( "<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row->year . $month ),
					/* translators: 1: month name, 2: 4-digit year */
					sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
				);
			}
			?>
		</select>
		<?php
	}

	/**
	 * Get all the possible levels.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_levels() {
		return array(
			LogLevel::EMERGENCY => 'Emergency',
			LogLevel::ALERT     => 'Alert',
			LogLevel::CRITICAL  => 'Critical',
			LogLevel::ERROR     => 'Error',
			LogLevel::WARNING   => 'Warning',
			LogLevel::NOTICE    => 'Notice',
			LogLevel::INFO      => 'Info',
			LogLevel::DEBUG     => 'Debug'
		);
	}

	/**
	 * Prepares the list of items for displaying.
	 * @uses   WP_List_Table::set_pagination_args()
	 *
	 * @since  1.0
	 * @access public
	 */
	public function prepare_items() {

		$page     = $this->get_pagenum();
		$per_page = $this->get_items_per_page( get_current_screen()->id . '_per_page' );

		if ( ! isset( $_GET['orderby'] ) ) {
			$_GET['orderby'] = 'time';
			$_GET['order']   = 'desc';
		}

		$args = array(
			'page'           => $page,
			'items_per_page' => $per_page
		);

		if ( isset( $_GET['orderby'] ) ) {
			$args['order'] = array(
				$_GET['orderby'] => isset( $_GET['order'] ) ? $_GET['order'] : 'ASC'
			);
		}

		if ( ! empty( $_GET['s'] ) ) {
			$args['message'] = $_GET['s'];
		}

		if ( ! empty( $_GET['level'] ) && array_key_exists( $_GET['level'], $this->get_levels() ) ) {
			$args['level'] = $_GET['level'];
		}

		if ( ! empty( $_GET['m'] ) ) {

			$args['time'] = array(
				'month' => substr( $_GET['m'], 4, 2 ),
				'year'  => substr( $_GET['m'], 0, 4 )
			);
		}

		$query = new LogQuery( $this->table, $this->model_class, $args );

		$total = $query->get_total_items();

		$this->items = $query->get_results();

		$this->set_pagination_args( array(
				'total_items' => $total,
				// we have to calculate the total number of items
				'per_page'    => $per_page,
				// we have to determine how many items to show on a page
				'total_pages' => ceil( $total / $per_page )
				// we have to calculate the total number of pages
			)
		);
	}

}