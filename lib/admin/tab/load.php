<?php
/**
 * Load the admin tabs code.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

ITELIC_Admin_Tab_Dispatch::register_tab( 'licenses', __( "Licenses", ITELIC::SLUG ), new ITELIC_Admin_Tab_Controller_Licenses() );