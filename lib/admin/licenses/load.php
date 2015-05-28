<?php
/**
 * Load the roster view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Licenses;

use ITELIC\Admin\Licenses\Controller\ListC;
use ITELIC\Admin\Licenses\Controller\Single;

Dispatch::register_view( 'list', new ListC() );
Dispatch::register_view( 'single', new Single() );