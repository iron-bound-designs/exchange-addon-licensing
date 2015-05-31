<?php
/**
 * Load the releases subview.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases;

use ITELIC\Admin\Releases\Controller\ListC;

Dispatch::register_view( 'list', new ListC() );