<?php
/**
 * Register the upgrade path CPT.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Upgrades;

/**
 * Class CPT
 * @package ITELIC\Upgrades
 */
class CPT {

	const SLUG = 'itelic-upgrade-path';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * Register the post type with WordPress.
	 *
	 * @since 1.0
	 */
	protected function register() {
		register_post_type( self::SLUG, array(
			'public'       => false,
			'hierarchical' => true,
			'supports'     => false
		) );
	}
}