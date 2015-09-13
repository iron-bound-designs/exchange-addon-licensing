<?php
/**
 * Load the upgrades module.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Upgrades;

add_action( 'init', function () {
	new CPT();
} );