<?php
/**
 * Load the upgrades module.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Upgrades;

add_action( 'init', function () {
	new CPT();
} );