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
	}

	/**
	 * Register the post type.
	 *
	 * @since 1.0
	 */
	public function register() {

		$labels = array(
			'name'          => __( 'Renewal Reminders', ITELIC::SLUG ),
			'singular_name' => __( 'Renewal Reminder', ITELIC::SLUG ),
			'edit_item'     => __( 'Edit Reminder', ITELIC::SLUG ),
			'search_items'  => __( 'Search Renewal Reminders', ITELIC::SLUG )
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
			'supports'          => array('title', 'editor'),
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

}