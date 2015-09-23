<?php
/**
 * Abstract report class.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace Admin\Reports;

use ITELIC\Admin\Chart\Base as Chart;
use ITELIC\Plugin;

/**
 * Class Base
 * @package Admin\Reports
 */
abstract class Report {

	/**
	 * @var array
	 */
	private $date_types = array();

	/**
	 * @var string
	 */
	protected $date_type;

	/**
	 * @var array
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param string $date_type
	 */
	public function __construct( $date_type ) {
		$predefined = array(
			'today'        => __( 'Today', Plugin::SLUG ),
			'yesterday'    => __( 'Yesterday', Plugin::SLUG ),
			'this_week'    => __( 'This Week', Plugin::SLUG ),
			'last_week'    => __( 'Last Week', Plugin::SLUG ),
			'this_month'   => __( 'This Month', Plugin::SLUG ),
			'last_month'   => __( 'Last Month', Plugin::SLUG ),
			'this_quarter' => __( 'This Quarter', Plugin::SLUG ),
			'last_quarter' => __( 'Last Quarter', Plugin::SLUG ),
			'this_year'    => __( 'This Year', Plugin::SLUG ),
			'last_year'    => __( 'Last Year', Plugin::SLUG ),
			'fiscal_year'  => __( 'Fiscal Year', Plugin::SLUG )
		);

		/**
		 * Filter the defined date types for reporting.
		 *
		 * @since 1.0
		 *
		 * @param array  $predefined
		 * @param Report $this
		 */
		$all = apply_filters( 'itelic_report_defined_date_types', $predefined, $this );

		if ( ! is_array( $all ) ) {
			$all = $predefined;
		}

		$this->date_types = $all;
	}

	/**
	 * Retrieve the labels for the chart.
	 *
	 * @return array
	 */
	public function get_labels() {
		$labels = array_keys( $this->data );

		switch ( self::date_type_to_interval( $this->date_type ) ) {
			case 'hour':
				$labels = array_map( array( $this, 'format_hour' ), $labels );
				break;
			case 'weekday':
				$labels = array_map( array( $GLOBALS['wp_locale'], 'get_weekday' ), $labels );
				break;
			case 'month':
				$labels = array_map( array( $this, 'month_to_abbrev' ), $labels );
				break;
		}

		return $labels;
	}

	/**
	 * Get the data for the chart.
	 *
	 * @return array
	 */
	public function get_data() {
		return array_values( $this->data );
	}

	/**
	 * Format hour values.
	 *
	 * @param int $hour
	 *
	 * @return string
	 */
	private function format_hour( $hour ) {
		$format = get_option( 'time_format' );

		$format = str_replace( array( 's', 'i', ':', '.' ), '', $format );

		return date( $format, mktime( $hour ) );
	}

	/**
	 * Convert month numbers to abbreviations.
	 *
	 * @param int $month
	 *
	 * @return string
	 */
	private function month_to_abbrev( $month ) {

		/**
		 * @var $wp_locale \WP_Locale
		 */
		global $wp_locale;

		return $wp_locale->get_month_abbrev( $wp_locale->get_month( $month ) );
	}

	/**
	 * Translate a date type, such as last week to a group by.
	 *
	 * Eg, this_year would return
	 *
	 * @since 1.0
	 *
	 * @param string $date_type
	 *
	 * @return string
	 */
	public static function date_type_to_interval( $date_type ) {

		switch ( $date_type ) {
			case 'today':
			case 'yesterday':
				return 'hour';
			case 'this_week':
			case 'last_week':
				return 'weekday';
			case 'this_month':
			case 'last_month':
				return 'day';
			case 'this_quarter':
			case 'last_quarter':
			case 'this_year':
			case 'last_year':
				return 'month';
			case 'fiscal_year':
				return 'quarter';
		}

		return '';
	}

	/**
	 * Converts a date to a timestamp.
	 *
	 * Credit for this method goes to Pippin Williamson,
	 * as used in Easy Digital Downloads.
	 *
	 * @since 1.0
	 *
	 * @param string  $date
	 * @param boolean $end_date
	 *
	 * @return int Timestamp when successful, otherwise a WP_Error instance.
	 * @throws \Exception
	 */
	protected function convert_date( $date, $end_date = false ) {
		$timestamp = false;
		$second    = 0;
		$minute    = 0;
		$hour      = 0;
		$day       = 1;
		$month     = date( 'n', current_time( 'timestamp' ) );
		$year      = date( 'Y', current_time( 'timestamp' ) );

		if ( array_key_exists( $date, $this->date_types ) ) {

			switch ( $date ) {
				case 'this_month' :
					if ( $end_date ) {
						$day = cal_days_in_month( CAL_GREGORIAN, $month, $year );
					}
					break;
				case 'last_month' :
					if ( $month == 1 ) {
						$month = 12;
						$year --;

					} else {
						$month --;
					}
					if ( $end_date ) {
						$day = cal_days_in_month( CAL_GREGORIAN, $month, $year );
					}
					break;

				case 'today' :
					$day = date( 'd', current_time( 'timestamp' ) );
					if ( $end_date ) {
						$hour   = 23;
						$minute = 59;
						$second = 59;
					}
					break;

				case 'yesterday' :
					$day = date( 'd', current_time( 'timestamp' ) ) - 1;
					// Check if Today is the first day of the month (meaning subtracting one will get us 0)
					if ( $day < 1 ) {
						// If current month is 1
						if ( 1 == $month ) {
							$year -= 1; // Today is January 1, so skip back to last day of December
							$month = 12;
							$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );
						} else {
							// Go back one month and get the last day of the month
							$month -= 1;
							$day = cal_days_in_month( CAL_GREGORIAN, $month, $year );
						}
					}

					if ( $end_date ) {
						$hour   = 23;
						$minute = 59;
						$second = 59;
					}

					break;

				case 'this_week' :

					if ( $month == 1 ) {
						$year --;
					}

					$days_to_week_start = ( date( 'w', current_time( 'timestamp' ) ) - 1 ) * 60 * 60 * 24;
					$today              = date( 'd', current_time( 'timestamp' ) ) * 60 * 60 * 24;
					if ( $today < $days_to_week_start ) {
						if ( $month > 1 ) {
							$month -= 1;
						} else {
							$month = 12;
						}
					}
					if ( ! $end_date ) {
						// Getting the start day
						$day = date( 'd', current_time( 'timestamp' ) - $days_to_week_start ) - 1;
						$day += get_option( 'start_of_week' );
					} else {
						// Getting the end day
						$day = date( 'd', current_time( 'timestamp' ) - $days_to_week_start ) - 1;
						$day += get_option( 'start_of_week' ) + 6;
					}
					break;

				case 'last_week' :

					if ( $month == 1 ) {
						$year --;
					}

					$days_to_week_start = ( date( 'w', current_time( 'timestamp' ) ) - 1 ) * 60 * 60 * 24;
					$today              = date( 'd', current_time( 'timestamp' ) ) * 60 * 60 * 24;
					if ( $today < $days_to_week_start ) {
						if ( $month > 1 ) {
							$month -= 1;
						} else {
							$month = 12;
						}
					}
					if ( ! $end_date ) {
						// Getting the start day
						$day = date( 'd', current_time( 'timestamp' ) - $days_to_week_start ) - 8;
						$day += get_option( 'start_of_week' );
					} else {
						// Getting the end day
						$day = date( 'd', current_time( 'timestamp' ) - $days_to_week_start ) - 8;
						$day += get_option( 'start_of_week' ) + 6;
					}
					break;

				case 'this_quarter' :
					$month_now = date( 'n', current_time( 'timestamp' ) );
					if ( $month_now <= 3 ) {
						if ( ! $end_date ) {
							$month = 1;
						} else {
							$month  = 3;
							$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 11;
							$minute = 59;
							$second = 59;
						}
					} else if ( $month_now <= 6 ) {
						if ( ! $end_date ) {
							$month = 4;
						} else {
							$month  = 6;
							$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 11;
							$minute = 59;
							$second = 59;
						}
					} else if ( $month_now <= 9 ) {
						if ( ! $end_date ) {
							$month = 7;
						} else {
							$month  = 9;
							$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 11;
							$minute = 59;
							$second = 59;
						}
					} else {
						if ( ! $end_date ) {
							$month = 10;
						} else {
							$month  = 12;
							$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 11;
							$minute = 59;
							$second = 59;
						}
					}
					break;

				case 'last_quarter' :
					$month_now = date( 'n', current_time( 'timestamp' ) );

					if ( $month_now == 1 && ! $end_date ) {
						$year --;
					}

					if ( $month_now <= 3 ) {
						if ( ! $end_date ) {
							$month = 10;
						} else {
							$year -= 1;
							$month  = 12;
							$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 11;
							$minute = 59;
							$second = 59;
						}
					} else if ( $month_now <= 6 ) {
						if ( ! $end_date ) {
							$month = 1;
						} else {
							$month  = 3;
							$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 11;
							$minute = 59;
							$second = 59;
						}
					} else if ( $month_now <= 9 ) {
						if ( ! $end_date ) {
							$month = 4;
						} else {
							$month  = 6;
							$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 11;
							$minute = 59;
							$second = 59;
						}
					} else {
						if ( ! $end_date ) {
							$month = 7;
						} else {
							$month  = 9;
							$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 11;
							$minute = 59;
							$second = 59;
						}
					}
					break;

				case 'fiscal_year':
				case 'this_year' :
					if ( ! $end_date ) {
						$month = 1;
					} else {
						$month  = 12;
						$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
						$hour   = 11;
						$minute = 59;
						$second = 59;
					}
					break;

				case 'last_year' :
					$year -= 1;
					if ( ! $end_date ) {
						$month = 1;
					} else {
						$month  = 12;
						$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );
						$hour   = 11;
						$minute = 59;
						$second = 59;
					}
					break;

			}
		} else if ( is_numeric( $date ) ) {
			$timestamp = true;
		} else if ( false !== strtotime( $date ) ) {
			$timestamp = true;
			$date      = strtotime( $date, current_time( 'timestamp' ) );
		} else {
			throw new \Exception( __( 'Improper date provided.', Plugin::SLUG ) );
		}

		if ( ! is_wp_error( $date ) && ! $timestamp ) {
			// Create an exact timestamp
			$date = mktime( $hour, $minute, $second, $month, $day, $year );
		}

		return apply_filters( 'itelic_reports_base_date', $date, $end_date, $this );
	}

	/**
	 * Fill the gaps in a data set with zeroes.
	 *
	 * @since 1.0
	 *
	 * @param array $data
	 * @param string $start
	 * @param string $end
	 * @param string $group
	 *
	 * @return array
	 */
	protected static function fill_gaps( $data, $start, $end, $group ) {

		switch ( $group ) {
			case 'hour':
				$start_num = date( 'G', strtotime( $start ) );
				$end_num   = date( 'G', strtotime( $end ) );
				break;
			case 'weekday':
				$start_num = date( 'w', strtotime( $start ) );
				$end_num   = date( 'w', strtotime( $end ) );
				break;
			case 'day':
				$start_num = date( 'j', strtotime( $start ) );
				$end_num   = date( 'j', strtotime( $end ) );
				break;
			case 'month':
				$start_num = date( 'n', strtotime( $start ) );
				$end_num   = date( 'n', strtotime( $end ) );
				break;
			case 'quarter':
				$month   = date( 'm', strtotime( $start ) );
				$quarter = ceil( $month / 3 );

				$start_num = $quarter;
				$end_num   = $quarter + 3;
				break;
			default:
				return $data;
		}

		$out = array();

		for ( $i = $start_num; $i <= $end_num; $i ++ ) {
			if ( isset( $data[ $i ] ) ) {
				$out[ $i ] = $data[ $i ];
			} else {
				$out[ $i ] = 0;
			}
		}

		return $out;
	}

	/**
	 * Retrieve the possible data types for a report.
	 *
	 * This returns an array of type slug to localized name.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_date_types() {
		return $this->date_types;
	}

	/**
	 * Get the title of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	abstract public function get_title();

	/**
	 * Get the slug of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	abstract public function get_slug();

	/**
	 * Get the description of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Get the chart for this report.
	 *
	 * @since 1.0
	 *
	 * @param string $date_type
	 *
	 * @return Chart
	 */
	abstract public function get_chart( $date_type = 'this_year' );
}