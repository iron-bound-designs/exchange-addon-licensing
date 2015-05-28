<?php
/**
 * Perform simple joins.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB\Query\Tag;

/**
 * Class Join
 * @package ITELIC\DB\Query\Tag
 */
class Join extends Generic {

	/**
	 * Constructor.
	 *
	 * @param From  $on
	 * @param Where $where
	 */
	public function __construct( From $on, Where $where ) {

		$sql = $on->get_value() . ' ON (' . $where->get_value() . ')';

		parent::__construct( 'JOIN', $sql );
	}
}