<?php
/**
 * Load the admin tabs code.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Tab;

use ITELIC\Admin\Tab\Controller\Licenses;
use ITELIC\Admin\Tab\Controller\Releases;
use ITELIC\Admin\Tab\Controller\Reminders;
use ITELIC\Admin\Tab\Controller\Reports;
use ITELIC\Plugin;

Dispatch::register_tab( 'licenses', __( "Licenses", Plugin::SLUG ), new Licenses() );
Dispatch::register_tab( 'releases', __( "Releases", Plugin::SLUG ), new Releases() );
Dispatch::register_tab( 'reminders', __( "Renewal Reminders", Plugin::SLUG ), new Reminders() );
Dispatch::register_tab( 'reports', __( "Reports", Plugin::SLUG ), new Reports() );