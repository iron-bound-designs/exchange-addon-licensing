<?php
/**
 * Updates API functions.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Get an update record.
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return \ITELIC\Update
 */
function itelic_get_update( $id ) {
	return \ITELIC\Update::get( $id );
}