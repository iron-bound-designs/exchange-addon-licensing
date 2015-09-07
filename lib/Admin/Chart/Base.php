<?php
/**
 * General Charting Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Chart;

/**
 * Class Chart
 * @package ITELIC\Admin
 */
abstract class Base {

	/**
	 * @var array
	 */
	protected $data_sets = array();

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var int
	 */
	private $width;

	/**
	 * @var int
	 */
	private $height;

	/**
	 * @var string
	 */
	private $type = 'Line';

	/**
	 * @var array
	 */
	private $options = array();

	/**
	 * @var string
	 */
	private $load_on = '';

	/**
	 * @var string
	 */
	private $show_legend = '';

	/**
	 * @var int
	 */
	private static $count = 0;

	/**
	 * @var bool
	 */
	private static $scripts_loaded = false;

	/**
	 * @var bool
	 */
	private static $globals_loaded = false;

	/**
	 * Constructor.
	 *
	 * @param int    $width
	 * @param int    $height
	 * @param string $type
	 * @param array  $options
	 */
	protected function __construct( $width, $height, $type = 'Line', $options = array() ) {
		self::$count++;

		$types = array( 'Line', 'Bar', 'Radar', 'PolarArea', 'Pie', 'Doughnut' );

		if ( in_array( $type, $types ) ) {
			$this->type = $type;
		}

		$this->width  = absint( $width );
		$this->height = absint( $height );

		if ( isset( $options['ibdLoadOn'] ) ) {
			$this->load_on = $options['ibdLoadOn'];

			unset( $options['ibdLoadOn'] );
		}

		if ( isset( $options['ibdShowLegend'] ) ) {
			$this->show_legend = $options['ibdShowLegend'];

			unset( $options['ibdShowLegend'] );
		}

		$this->options = $options;
		$this->id = 'itelic-chart-' . self::$count;
	}

	/**
	 * Get the type of this graph.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get the total items displayed in the chart.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public abstract function get_total_items();

	/**
	 * Add a line of data to this graph.
	 *
	 * @param array  $points
	 * @param string $label
	 * @param array  $options
	 *
	 * @throws \UnexpectedValueException
	 */
	public abstract function add_data_set( $points, $label = '', $options = array() );

	/**
	 * Build the data to be passed to the Chart.
	 *
	 * @since 1.0
	 *
	 * @return object
	 */
	protected abstract function build_data();

	/**
	 * Render the Graph to the page.
	 */
	public function graph() {
		$this->load_scripts();

		$data = $this->build_data();

		$options = $this->options;

		if ( self::$globals_loaded ) {
			$globals = array();
		} else {
			$globals = self::$global_options;
		}

		$id = $this->id;
		?>

		<canvas id="<?php echo esc_attr( $id ); ?>" width="<?php echo esc_attr( $this->width ); ?>"
		        height="<?php echo esc_attr( $this->height ); ?>"></canvas>

		<script type="text/javascript">
			(function ($) {
				'use strict';

				var globals = <?php echo wp_json_encode($globals); ?>

					$.each(globals, function (index, value) {
						Chart.defaults.global[index] = value;
					});

				var data = <?php echo wp_json_encode($data); ?>

				var options = <?php echo wp_json_encode($options); ?>

				<?php if ( $this->load_on ): ?>

					$('body').bind('<?php echo esc_js($this->load_on); ?>', function () {
						var ctx = $("#<?php echo ($id); ?>").get(0).getContext("2d");
						var chart = new Chart(ctx).<?php echo $this->type; ?>(data, options);

						<?php if ( $this->show_legend ): ?>
						$("<?php echo $this->show_legend; ?>").html( chart.generateLegend() );
						<?php endif; ?>
					});

				<?php else: ?>
					$(document).ready(function() {

						var ctx = $("#<?php echo ($id); ?>").get(0).getContext("2d");
						var chart = new Chart(ctx).<?php echo $this->type; ?>(data, options);

						<?php if ( $this->show_legend ): ?>
						$("<?php echo $this->show_legend; ?>").innerHTML = chart.generateLegend();
						<?php endif; ?>
					});
				<?php endif; ?>

			})(jQuery);
		</script>

		<?php

	}

	/**
	 * Load required scripts.
	 */
	protected function load_scripts() {

		if ( ! self::$scripts_loaded ) {
			wp_enqueue_script( 'ithemes-chartjs' );

			self::$scripts_loaded = true;
		}
	}

	/**
	 * Get an option for this chart.
	 *
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function get_option( $key ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : null;
	}

	/**
	 * Set an option for this chart.
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function set_option( $key, $value ) {
		$this->options[ $key ] = $value;
	}

	/**
	 * @var array
	 */
	private static $global_options = array();

	/**
	 * Get a global option.
	 *
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public static function get_global_option( $key ) {
		return isset( self::$global_options[ $key ] ) ? self::$global_options[ $key ] : null;
	}

	/**
	 * Set a global option.
	 *
	 * @param string $key
	 * @param string $value
	 */
	public static function set_global_option( $key, $value ) {
		self::$global_options[ $key ] = $value;
	}
}