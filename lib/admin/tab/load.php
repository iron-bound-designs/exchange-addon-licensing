<?php
/**
 * Load the admin tabs code.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Tab;

use ITELIC\Admin\Tab\Controller\Licenses;
use ITELIC\Admin\Tab\Controller\Reminders;
use ITELIC\Plugin;

Dispatch::register_tab( 'licenses', __( "Licenses", Plugin::SLUG ), new Licenses() );
Dispatch::register_tab( 'reminders', __( "Renewal Reminders", Plugin::SLUG ), new Reminders() );