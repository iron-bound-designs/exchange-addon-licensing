<?php
/**
 * Manages post type for renewal reminders.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Renewal_Reminder_Type
 */
class ITELIC_Renewal_Reminder_Type {

	/**
	 * @var string
	 */
	const TYPE = 'it_exchange_licrenew';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'parent_file', array( $this, 'set_exchange_to_parent' ) );
		add_action( 'add_meta_boxes_' . self::TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
		add_action( 'save_post_' . self::TYPE, array( $this, 'save_meta_box' ) );
	}

	/**
	 * Register the post type.
	 *
	 * @since 1.0
	 */
	public function register() {

		$labels = array(
			'name'               => __( 'Renewal Reminders', ITELIC::SLUG ),
			'singular_name'      => __( 'Renewal Reminder', ITELIC::SLUG ),
			'edit_item'          => __( 'Edit Reminder', ITELIC::SLUG ),
			'search_items'       => __( 'Search Renewal Reminders', ITELIC::SLUG ),
			'add_new_item'       => __( "Add New Reminder", ITELIC::SLUG ),
			'view_item'          => __( "View Reminder", ITELIC::SLUG ),
			'not_found'          => __( "No renewal reminders found", ITELIC::SLUG ),
			'not_found_in_trash' => __( "No reminders found in trash", ITELIC::SLUG )
		);

		$args = array(
			'labels'            => $labels,
			'label'             => __( "Renewal Reminders", ITELIC::SLUG ),
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
		add_meta_box( 'itelic-renewal-reminder-scheduling', __( "Scheduling", ITELIC::SLUG ), array(
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
		}
	}

	/**
	 * Render the renewal reminder scheduling metabox.
	 *
	 * @since 1.0
	 *
	 * @param WP_Post $post
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

		<p><?php _e( "Control when this renewal reminder should be sent.", ITELIC::SLUG ); ?></p>

		<p>
			<label for="itelic-reminder-days"><?php _e( "Number of Days", ITELIC::SLUG ); ?></label>
			<input type="number" id="itelic-reminder-days" min="0" name="itelic_reminder[days]" value="<?php echo esc_attr( $days ); ?>">
		</p>

		<p>
			<label for="itelic-reminder-before-or-after"><?php _e( "Before or After Expiration", ITELIC::SLUG ); ?></label>
			<select id="itelic-reminder-before-or-after" name="itelic_reminder[boa]">
				<option value="before" <?php selected( $boa, 'before' ); ?>><?php _e( "Before", ITELIC::SLUG ); ?></option>
				<option value="after" <?php selected( $boa, 'after' ); ?>><?php _e( "After", ITELIC::SLUG ); ?></option>
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
}