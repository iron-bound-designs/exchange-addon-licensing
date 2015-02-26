<?php
/**
 * Load the roster view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

ITELIC_Admin_Licenses_Dispatch::register_view( 'list', new ITELIC_Admin_Licenses_Controller_List() );
ITELIC_Admin_Licenses_Dispatch::register_view( 'single', new ITELIC_Admin_Licenses_Controller_Single() );