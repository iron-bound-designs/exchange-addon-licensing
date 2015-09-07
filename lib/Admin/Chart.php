<?php
/**
 * General Charting Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin;

/**
 * Class Chart
 * @package ITELIC\Admin
 */
class Chart {

	/**
	 * @var array
	 */
	private $data_sets = array();

	/**
	 * @var array
	 */
	private $labels = array();

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
	 * @param array  $labels
	 * @param int    $width
	 * @param int    $height
	 * @param string $type
	 * @param array  $options
	 */
	public function __construct( $labels, $width, $height, $type = 'Line', $options = array() ) {
		$this->labels = $labels;
		$this->id     = md5( rand() );

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

		$this->options = $options;
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
	 * Render the Graph to the page.
	 */
	public function graph() {
		$this->load_scripts();

		$data = (object) array(
			'labels'   => $this->labels,
			'datasets' => $this->data_sets
		);

		$options = $this->options;

		if ( self::$globals_loaded ) {
			$globals = array();
		} else {
			$globals = self::$global_options;
		}

		?>

		<canvas id="<?php echo esc_attr( $this->id ); ?>" width="<?php echo esc_attr( $this->width ); ?>"
		        height="<?php echo esc_attr( $this->height ); ?>"></canvas>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {

				var ctx = $("#<?php echo ($this->id); ?>").get(0).getContext("2d");

				var globals = <?php echo wp_json_encode($globals); ?>

					$.each(globals, function (index, value) {
						Chart.defaults.global[index] = value;
					});

				var data =
				<?php echo wp_json_encode($data); ?>

				var options =
				<?php echo wp_json_encode($options); ?>

				<?php if ( $this->load_on ): ?>

					$(document).bind('<?php echo esc_js($this->load_on); ?>', function() {

						var chart = new Chart(ctx).<?php echo $this->type; ?>(data, options);
					});

				<?php else: ?>

					var chart = new Chart(ctx).<?php echo $this->type; ?>(data, options);
				<?php endif; ?>

			});
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
	 * @return bool
	 */
	public function is_empty() {
		return count( $this->labels ) == 0;
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