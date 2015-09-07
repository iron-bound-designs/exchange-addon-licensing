<?php
/**
 * Charts with integrated labels.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Chart;

/**
 * Class Integrated_Labels
 * @package ITELIC\Admin\Chart
 */
abstract class Integrated_Labels extends Base {

	/**
	 * @var array
	 */
	protected $data_sets = array();

	/**
	 * Add a line of data to this graph.
	 *
	 * @param string $value
	 * @param string $label
	 * @param array  $options
	 *
	 * @throws \UnexpectedValueException
	 */
	public function add_data_set( $value, $label = '', $options = array() ) {

		$defaults = array(
			'value' => $value,
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
		return $this->data_sets;
	}
}