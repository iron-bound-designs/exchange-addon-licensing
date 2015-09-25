<?php
/**
 * Releases api functions.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Get a release record.
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return \ITELIC\Release
 */
function itelic_get_release( $id ) {

	return \ITELIC\Release::get( $id );
}