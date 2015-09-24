<?php
/**
 * Load the reports view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports;

use ITELIC\Admin\Reports\Controller\ListC;
use ITELIC\Admin\Reports\Controller\SingleC;
use ITELIC\Admin\Reports\Types\Installed_Versions;
use ITELIC\Admin\Reports\Types\Licenses;
use ITELIC\Admin\Reports\Types\Renewal_Rate;
use ITELIC\Admin\Reports\Types\Renewal_Revenue;
use ITELIC\Admin\Reports\Types\Renewals_Over_Time;

Dispatch::register_view( 'list', new ListC() );
Dispatch::register_view( 'single', new SingleC() );

Dispatch::register_report( new Licenses() );
Dispatch::register_report( new Installed_Versions() );
Dispatch::register_report( new Renewal_Rate() );
Dispatch::register_report( new Renewal_Revenue() );
Dispatch::register_report( new Renewals_Over_Time() );