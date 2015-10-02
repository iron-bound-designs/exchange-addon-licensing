<?php
/**
 * Load the roster view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Licenses;

use ITELIC\Admin\Licenses\Controller\Add_New;
use ITELIC\Admin\Licenses\Controller\ListC;
use ITELIC\Admin\Licenses\Controller\Single;

Dispatch::register_view( 'list', new ListC() );
Dispatch::register_view( 'single', new Single() );
Dispatch::register_view( 'add-new', new Add_New() );