<?php
/**
 * Custom Date Interval
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace IronBound;

/**
 * Class Date_Interval
 * @package IBD
 */
class Date_Interval extends \DateInterval {

	/**
	 * Returns the date_spec
	 *
	 * @link http://stackoverflow.com/a/25371691
	 *
	 * @return string
	 */
	public function __toString() {
		// Reading all non-zero date parts.
		$date = array_filter( array(
			'Y' => $this->y,
			'M' => $this->m,
			'D' => $this->d
		) );

		// Reading all non-zero time parts.
		$time = array_filter( array(
			'H' => $this->h,
			'M' => $this->i,
			'S' => $this->s
		) );

		$specString = 'P';

		// Adding each part to the spec-string.
		foreach ( $date as $key => $value ) {
			$specString .= $value . $key;
		}
		if ( count( $time ) > 0 ) {
			$specString .= 'T';
			foreach ( $time as $key => $value ) {
				$specString .= $value . $key;
			}
		}

		return $specString;
	}
}