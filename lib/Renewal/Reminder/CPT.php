<?php
/**
 * Manages post type for renewal reminders.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Renewal\Reminder;

use ITELIC\Notifications\Template\Editor;
use ITELIC\Notifications\Template\Factory;
use ITELIC\Plugin;
use ITELIC\Renewal\Reminder;

/**
 * Class Type
 * @package ITELIC\Renewal\Reminder
 */
class CPT {

	/**
	 * @var string
	 */
	const TYPE = 'it_exchange_licrenew';

	/**
	 * @var string
	 */
	const SHORTCODE = 'itelic_renewal';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'parent_file', array( $this, 'set_exchange_to_parent' ) );
		add_action( 'add_meta_boxes_' . self::TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
		add_action( 'save_post_' . self::TYPE, array( $this, 'save_meta_box' ) );
		add_filter( 'manage_' . self::TYPE . '_posts_columns', array( $this, 'add_custom_columns' ) );
		add_action( 'manage_' . self::TYPE . '_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
	}

	/**
	 * Register the post type.
	 *
	 * @since 1.0
	 */
	public function register() {

		$labels = array(
			'name'               => __( 'Renewal Reminders', Plugin::SLUG ),
			'singular_name'      => __( 'Renewal Reminder', Plugin::SLUG ),
			'edit_item'          => __( 'Edit Reminder', Plugin::SLUG ),
			'search_items'       => __( 'Search Renewal Reminders', Plugin::SLUG ),
			'add_new_item'       => __( "Add New Reminder", Plugin::SLUG ),
			'view_item'          => __( "View Reminder", Plugin::SLUG ),
			'not_found'          => __( "No renewal reminders found", Plugin::SLUG ),
			'not_found_in_trash' => __( "No reminders found in trash", Plugin::SLUG )
		);

		$args = array(
			'labels'            => $labels,
			'label'             => __( "Renewal Reminders", Plugin::SLUG ),
			'public'            => false,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_in_menu'      => false,
			'show_in_admin_bar' => false,
			'capabilities'      => array(
				'edit_posts'        => 'edit_posts',
				'create_posts'      => 'edit_posts',
				'edit_others_posts' => 'edit_others_posts',
				'publish_posts'     => 'publish_posts',
			),
			'supports'          => array( 'title', 'editor' ),
			'map_meta_cap'      => true,
			'capability_type'   => 'post'
		);

		register_post_type( self::TYPE, $args );
	}

	/**
	 * Set exchange to the parent file when the renewal reminders post type is being viewed.
	 *
	 * @since 1.0
	 *
	 * @param string $parent_file
	 *
	 * @return string
	 */
	public function set_exchange_to_parent( $parent_file ) {
		global $submenu_file;

		$screen = get_current_screen();

		if ( $screen->post_type !== self::TYPE ) {
			return $parent_file;
		}

		$submenu_file = 'it-exchange-licensing';

		// Return it-exchange as the parent (open) menu when on post-new.php and post.php for it_exchange_prod post_types
		return 'it-exchange';
	}

	/**
	 * Add the meta box for controlling when a renewal reminder should be sent.
	 *
	 * @since 1.0
	 */
	public function add_meta_box() {
		add_meta_box( 'itelic-renewal-reminder-scheduling', __( "Scheduling", Plugin::SLUG ), array(
			$this,
			'render_meta_box'
		), self::TYPE, 'side' );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0
	 */
	public function scripts_and_styles() {
		if ( get_current_screen()->post_type == self::TYPE ) {
			wp_enqueue_style( 'itelic-renewal-reminder-edit' );

			wp_enqueue_script( 'itelic-renewal-reminder-edit' );
			wp_localize_script( 'itelic-renewal-reminder-edit', 'ITELIC', array(
				'must_select' => __( "You must select an item.", Plugin::SLUG )
			) );
		}
	}

	/**
	 * Render the renewal reminder scheduling metabox.
	 *
	 * @since 1.0
	 *
	 * @param \WP_Post $post
	 */
	public function render_meta_box( $post ) {

		if ( isset( $post->ID ) ) {
			$post_id = $post->ID;
		} else {
			$post_id = 0;
		}

		$days = get_post_meta( $post_id, '_itelic_renewal_reminder_days', true );
		$boa  = get_post_meta( $post_id, '_itelic_renewal_reminder_boa', true );
		?>

		<p><?php _e( "Control when this renewal reminder should be sent.", Plugin::SLUG ); ?></p>

		<p>
			<label for="itelic-reminder-days"><?php _e( "Number of Days", Plugin::SLUG ); ?></label>
			<input type="number" id="itelic-reminder-days" min="0" name="itelic_reminder[days]" value="<?php echo esc_attr( $days ); ?>">
		</p>

		<p>
			<label for="itelic-reminder-before-or-after"><?php _e( "Before or After Expiration", Plugin::SLUG ); ?></label>
			<select id="itelic-reminder-before-or-after" name="itelic_reminder[boa]">
				<option value="before" <?php selected( $boa, 'before' ); ?>><?php _e( "Before", Plugin::SLUG ); ?></option>
				<option value="after" <?php selected( $boa, 'after' ); ?>><?php _e( "After", Plugin::SLUG ); ?></option>
			</select>
		</p>

		<?php wp_nonce_field( 'itelic-renewal-reminders-metabox', 'itelic_reminder_nonce' ) ?>

		<?php
	}

	/**
	 * When the post is saved, saves our renewal data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	function save_meta_box( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['itelic_reminder_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['itelic_reminder_nonce'], 'itelic-renewal-reminders-metabox' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$days = absint( $_POST['itelic_reminder']['days'] );

		if ( ! in_array( $_POST['itelic_reminder']['boa'], array( 'before', 'after' ) ) ) {
			$boa = 'before';
		} else {
			$boa = $_POST['itelic_reminder']['boa'];
		}

		update_post_meta( $post_id, '_itelic_renewal_reminder_days', $days );
		update_post_meta( $post_id, '_itelic_renewal_reminder_boa', $boa );
	}

	/**
	 * Get all renewal reminders.
	 *
	 * @since 1.0
	 *
	 * @return Reminder[]
	 */
	public static function get_reminders() {

		$query = new \WP_Query( array(
			'post_type' => self::TYPE
		) );

		$reminders = array();

		foreach ( $query->get_posts() as $post ) {
			$reminders[] = new Reminder( $post );
		}

		return $reminders;
	}

	/**
	 * Add custom columns.
	 *
	 * @since 1.0
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_custom_columns( $columns ) {

		$columns['title'] = __( "Subject", Plugin::SLUG );

		$date = $columns['date'];
		unset( $columns['date'] );

		$columns['schedule'] = __( "Schedule", Plugin::SLUG );
		$columns['date']     = $date;

		return $columns;
	}

	/**
	 * Render the custom columns.
	 *
	 * @since 1.0
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function render_custom_columns( $column, $post_id ) {

		if ( $column == 'schedule' ) {

			$days = get_post_meta( $post_id, '_itelic_renewal_reminder_days', true );
			$boa  = get_post_meta( $post_id, '_itelic_renewal_reminder_boa', true );

			printf( "%d days %s expiration", $days, $boa );
		}
	}
}