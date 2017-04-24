<?php
/**
 * Bar chart.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Chart;

/**
 * Class Bar
 * @package ITELIC\Admin\Chart
 */
class Bar extends Separated_Labels {

	/**
	 * Constructor.
	 *
	 * @param array  $labels
	 * @param int    $width
	 * @param int    $height
	 * @param array  $options
	 */
	public function __construct( $labels, $width, $height, $options = array() ) {
		parent::__construct( $labels, $width, $height, 'Bar', $options );
	}

}