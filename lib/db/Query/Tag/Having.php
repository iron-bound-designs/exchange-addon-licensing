<?php
/**
 * Having Clause
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB\Query\Tag;

/**
 * Class Having
 * @package ITELIC\DB\Query\Tag
 */
class Having extends Generic {

	/**
	 * Constructor.
	 *
	 * @param Where $where
	 */
	public function __construct( Where $where ) {
		parent::__construct( 'HAVING', $where->get_value() );
	}
}