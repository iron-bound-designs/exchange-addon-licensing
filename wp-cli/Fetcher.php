<?php
/**
 * CLI Model fetcher.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

/**
 * Class ITELIC_Fetcher
 */
class ITELIC_Fetcher extends \WP_CLI\Fetchers\Base {

	/**
	 * @var string
	 */
	protected $model_class;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param $model_class
	 */
	public function __construct( $model_class ) {
		if ( ! class_exists( $model_class ) ) {
			throw new InvalidArgumentException( "Invalid model class." );
		}

		$reflected = new ReflectionClass( $model_class );

		if ( ! $reflected->isSubclassOf( '\IronBound\DB\Model' ) ) {
			throw new InvalidArgumentException( "Invalid model class." );
		}

		$this->model_class = $model_class;

		$this->msg = "Could not find the {$reflected->getShortName()} with pk %s";
	}

	/**
	 * @param string $arg The raw CLI argument
	 *
	 * @return mixed|false The item if found; false otherwise
	 */
	public function get( $arg ) {
		return call_user_func( array( $this->model_class, 'get' ), $arg );
	}
}