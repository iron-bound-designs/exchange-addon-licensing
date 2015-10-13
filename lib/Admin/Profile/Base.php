<?php
/**
 * Profile Base class.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Profile;

/**
 * Class Base
 * @package ITELIC\Admin
 */
abstract class Base {

	/**
	 * @var string
	 */
	private $tab_slug;

	/**
	 * @var string
	 */
	private $tab_name;

	/**
	 * @var \WP_User
	 */
	protected $user;

	/**
	 * Set up an admin page.
	 *
	 * @param $tab_slug string
	 * @param $tab_name string
	 */
	public function __construct( $tab_slug, $tab_name ) {
		$this->tab_slug = $tab_slug;
		$this->tab_name = $tab_name;

		if ( empty( $_REQUEST['user_id'] ) ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = $_REQUEST['user_id'];
		}

		$this->user = get_user_by( 'id', $user_id );

		add_action( 'it_exchange_print_user_edit_page_tab_links', array( $this, 'render_tab' ), 15 );
		add_action( 'it_exchange_print_user_edit_page_content', array( $this, 'maybe_render_page' ) );
	}

	/**
	 * Print out the tab on the top of the user profile page.
	 *
	 * @param $current_tab string
	 *
	 * @return void
	 */
	public function render_tab( $current_tab ) {

		$active = ( $this->get_tab_slug() == $current_tab ) ? 'nav-tab-active' : '';
		?>
		<a class="nav-tab <?php echo $active; ?>" href="<?php echo add_query_arg( 'tab', $this->get_tab_slug() ); ?>#it-exchange-member-options"><?php echo $this->get_tab_name(); ?></a><?php
	}

	/**
	 * Determine if we should render the page.
	 *
	 * @param $tab string
	 */
	public function maybe_render_page( $tab ) {
		if ( $this->get_tab_slug() == $tab ) {
			$this->render_page();
		}
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	protected abstract function render_page();

	/**
	 * Return the URL to this tab.
	 *
	 * @return string
	 */
	public function get_url() {
		$base     = get_edit_user_link( $this->user->ID );
		$exchange = add_query_arg( 'it_exchange_customer_data', '1', $base );

		return add_query_arg( 'tab', $this->get_tab_slug(), $exchange );
	}

	/**
	 * Return the tab's name.
	 *
	 * @return string
	 */
	public function get_tab_name() {
		return $this->tab_name;
	}

	/**
	 * Return the tab slug.
	 *
	 * @return string
	 */
	public function get_tab_slug() {
		return $this->tab_slug;
	}
}