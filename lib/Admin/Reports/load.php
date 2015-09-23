<?php
/**
 * Load the reports view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports;

use ITELIC\Admin\Reports\Controller\ListC;

Dispatch::register_view( 'list', new ListC() );