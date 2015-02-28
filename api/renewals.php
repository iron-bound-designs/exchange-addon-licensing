<?php
/**
 * API Renewal Functions
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Get a renewal record.
 *
 * @since 1.0
 *
 * @param int $id
 *
 * @return ITELIC_Renewal
 */
function itelic_get_renewal_record( $id ) {
	return ITELIC_Renewal::from_id( $id );
}