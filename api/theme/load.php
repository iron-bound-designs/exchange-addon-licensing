<?php
/**
 * Load the front-end theme API.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

if ( is_admin() ) {
	return;
}

require_once \ITELIC\Plugin::$dir . 'api/theme/class.activation.php';
require_once \ITELIC\Plugin::$dir . 'api/theme/class.license.php';
require_once \ITELIC\Plugin::$dir . 'api/theme/class.licenses.php';