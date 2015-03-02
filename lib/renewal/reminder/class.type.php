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
			'labels'               => $labels,
			'label'                => __( "Renewal Reminders", ITELIC::SLUG ),
			'public'               => false,
			'show_ui'              => true,
			'show_in_nav_menus'    => false,
			'show_in_menu'         => false,
			'show_in_admin_bar'    => false,
			'capabilities'         => array(
				'edit_posts'        => 'edit_posts',
				'create_posts'      => 'edit_posts',
				'edit_others_posts' => 'edit_others_posts',
				'publish_posts'     => 'publish_posts',
			),
			'supports'             => false,
			'map_meta_cap'         => true,
			'capability_type'      => 'post'
		);

		register_post_type( self::TYPE, $args );
	}

}