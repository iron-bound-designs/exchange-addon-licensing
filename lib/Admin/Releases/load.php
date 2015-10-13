<?php
/**
 * Load the releases subview.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Releases;

use ITELIC\Admin\Releases\Controller\Add_New;
use ITELIC\Admin\Releases\Controller\ListC;
use ITELIC\Admin\Releases\Controller\Single;

Dispatch::register_view( 'list', new ListC() );
Dispatch::register_view( 'single', new Single() );
Dispatch::register_view( 'add-new', new Add_New() );