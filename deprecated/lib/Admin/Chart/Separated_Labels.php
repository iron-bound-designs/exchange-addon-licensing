<?php
/**
 * Charting class for charts with separated labels.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Chart;

/**
 * Class Separated_Labels
 * @package ITELIC\Admin\Chart
 */
abstract class Separated_Labels extends Base {

	/**
	 * @var array
	 */
	protected $labels;

	/**
	 * Constructor.
	 *
	 * @param array  $labels
	 * @param int    $width
	 * @param int    $height
	 * @param string $type
	 * @param array  $options
	 */
	protected function __construct( $labels, $width, $height, $type = 'Line', $options = array() ) {

		$this->labels = $labels;

		parent::__construct( $width, $height, $type, $options );
	}

	/**
	 * Get the total items displayed in the chart.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_total_items() {
		return count( $this->labels );
	}

	/**
	 * Add a line of data to this graph.
	 *
	 * @param array  $points
	 * @param string $label
	 * @param array  $options
	 *
	 * @throws \UnexpectedValueException
	 */
	public function add_data_set( $points, $label = '', $options = array() ) {

		if ( count( $points ) !== count( $this->labels ) ) {
			throw new \UnexpectedValueException( 'usage: count($points) == count($labels)' );
		}

		$defaults = array(
			'data'  => $points,
			'label' => $label
		);

		$this->data_sets[] = \ITUtility::merge_defaults( $options, $defaults );
	}

	/**
	 * Build the data to be passed to the Chart.
	 *
	 * @since 1.0
	 *
	 * @return object
	 */
	protected function build_data() {
		return (object) array(
			'labels'   => $this->labels,
			'datasets' => $this->data_sets
		);
	}
}