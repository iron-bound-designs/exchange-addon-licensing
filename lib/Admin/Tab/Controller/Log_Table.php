<?php
/**
 * Log table.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Tab\Controller;

use IronBound\DBLogger\AbstractLog;
use IronBound\DBLogger\ListTable;
use ITELIC\Plugin;

/**
 * Class Log_Table
 * @package ITELIC\Admin\Tab\Controller
 */
class Log_Table extends ListTable {

	/**
	 * Render the column message.
	 *
	 * @since 1.0
	 *
	 * @param AbstractLog $log
	 *
	 * @return string
	 */
	public function column_message( AbstractLog $log ) {

		$link = '#TB_inline?width=150height=250&inlineId=log_details_' . $log->get_pk();

		//Build row actions
		$actions = array(
			'view' => sprintf( '<a href="%1$s" class="thickbox" title="%3$s">%2$s</a>',
				$link,
				__( "View Details", Plugin::SLUG ),
				__( "Log Details" ) ),
		);

		//Return the title contents
		return sprintf( '%1$s %2$s',
			/*$1%s*/
			$log->get_message(),
			/*$2%s*/
			$this->row_actions( $actions )
		);
	}


}