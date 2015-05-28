<?php
/**
 * Limit tag.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\DB\Query\Tag;

/**
 * Class Limit
 * @package ITELIC\DB\Query\Tag
 */
class Limit extends Generic {

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param int      $count
	 * @param int|null $offset
	 */
	public function __construct( $count, $offset = null ) {

		$value = $count;

		if ( $offset !== null ) {
			$value = "$offset, $value";
		}

		parent::__construct( "LIMIT", $value );
	}

}