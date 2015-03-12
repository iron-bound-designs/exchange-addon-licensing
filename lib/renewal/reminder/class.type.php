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

		add_action( 'current_screen', array( $this, 'configure_shortcode_popup' ) );
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
			wp_localize_script( 'itelic-renewal-reminder-edit', 'ITELIC', array(
				'must_select' => __( "You must select an item.", ITELIC::SLUG )
			) );
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

	/**
	 * Get all renewal reminders.
	 *
	 * @since 1.0
	 *
	 * @return ITELIC_Renewal_Reminder[]
	 */
	public static function get_reminders() {

		$query = new WP_Query( array(
			'post_type' => ITELIC_Renewal_Reminder_Type::TYPE
		) );

		$reminders = array();

		foreach ( $query->get_posts() as $post ) {
			$reminders[] = new ITELIC_Renewal_Reminder( $post );
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

		$columns['title'] = __( "Subject", ITELIC::SLUG );

		$date = $columns['date'];
		unset( $columns['date'] );

		$columns['schedule'] = __( "Schedule", ITELIC::SLUG );
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

	/**
	 * Configure the shortcode popup.
	 *
	 * @since 1.0
	 */
	public function configure_shortcode_popup() {

		if ( get_current_screen()->post_type == self::TYPE ) {
			remove_action( 'media_buttons', 'media_buttons' );
			remove_action( 'media_buttons', 'it_exchange_membership_addon_media_form_button', 15 );

			add_action( 'media_buttons', array( $this, 'display_shortcode_button' ), 15 );
			add_filter( 'mce_buttons', array( $this, 'modify_mce_buttons' ) );
			add_action( 'admin_footer', array( $this, 'shortcode_popup' ) );

			self::register_shortcodes();
		}
	}

	/**
	 * Register email shortcodes.
	 *
	 * @since 1.0
	 */
	public static function register_shortcodes() {
		IBD_Shortcode_Listener_Manager::listen( self::SHORTCODE, new IBD_Shortcode_Listener( 'customer', 'first_name',
			function ( IT_Exchange_Customer $customer ) {
				return get_user_meta( $customer->wp_user->ID, 'first_name', true );
			} ) );

		IBD_Shortcode_Listener_Manager::listen( self::SHORTCODE, new IBD_Shortcode_Listener( 'customer', 'last_name',
			function ( IT_Exchange_Customer $customer ) {
				return get_user_meta( $customer->wp_user->ID, 'last_name', true );
			} ) );

		IBD_Shortcode_Listener_Manager::listen( self::SHORTCODE, new IBD_Shortcode_Listener( 'key', 'key',
			function ( ITELIC_Key $key ) {
				return $key->get_key();
			} ) );

		IBD_Shortcode_Listener_Manager::listen( self::SHORTCODE, new IBD_Shortcode_Listener( 'key', 'expiry_date',
			function ( ITELIC_Key $key ) {
				return $key->get_expires()->format( get_option( 'date_format' ) );
			} ) );

		IBD_Shortcode_Listener_Manager::listen( self::SHORTCODE, new IBD_Shortcode_Listener( 'key', 'days_from_expiry',
			function ( ITELIC_Key $key ) {

				$diff = $key->get_expires()->diff( new DateTime(), true );

				return $diff->days;
			} ) );

		IBD_Shortcode_Listener_Manager::listen( self::SHORTCODE, new IBD_Shortcode_Listener( 'product', 'name',
			function ( IT_Exchange_Product $product ) {
				return $product->post_title;
			} ) );

		IBD_Shortcode_Listener_Manager::listen( self::SHORTCODE, new IBD_Shortcode_Listener( 'transaction', 'order_number',
			function ( IT_Exchange_Transaction $transaction ) {
				return it_exchange_get_transaction_order_number( $transaction );
			} ) );

		IBD_Shortcode_Listener_Manager::listen( self::SHORTCODE, new IBD_Shortcode_Listener( 'discount', 'amount',
			function ( ITELIC_Renewal_Discount $discount ) {
				return $discount->get_amount( true );
			} ) );
	}

	/**
	 * Display the shortcode popup.
	 */
	public function shortcode_popup() {
		$shortcodes = IBD_Shortcode_Listener_Manager::get_listeners( self::SHORTCODE );
		?>

		<div id="itelic-select-shortcode" style="display: none">
			<div class="wrap">
				<div>
					<p><?php _e( "Select a piece of data to insert" ); ?></p>

					<label for="add-shortcode-value"><?php _e( "Data" ); ?></label><br>

					<select id="add-shortcode-value">
						<option value="-1"><?php _e( "Select an item..." ); ?></option>

						<?php foreach ( $shortcodes as $shortcode ): ?>
							<option value="<?php echo esc_attr( IBD_Shortcode_Listener_Manager::get_shortcode( self::SHORTCODE, $shortcode ) ); ?>">
								<?php echo $shortcode; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="padding: 15px 15px 15px 0">
					<input type="button" class="button-primary insert-shortcode" value="<?php _e( 'Insert Shortcode' ); ?>" />
					&nbsp;&nbsp;&nbsp;
					<a class="button cancel-shortcode-insert" style="color:#bbb;" href="javascript:">
						<?php _e( 'Cancel' ); ?>
					</a>
				</div>
			</div>
		</div>

	<?php
	}

	/**
	 * Display the shortcode button.
	 *
	 * @since 1.0
	 */
	public function display_shortcode_button() {
		add_thickbox();
		echo '<a href="#TB_inline?width=150height=250&inlineId=itelic-select-shortcode" class="thickbox button itelic_emails" id="add_itelic_email" title="' . __( 'Insert Email Shortcode' ) . '"> ' . __( 'Insert Email Shortcode' ) . '</a>';
	}

	/**
	 * Modify the tinyMCE buttons.
	 *
	 * @param array $buttons
	 *
	 * @return array
	 */
	public function modify_mce_buttons( $buttons ) {
		unset( $buttons[ array_search( 'wp_more', $buttons ) ] );

		return $buttons;
	}
}