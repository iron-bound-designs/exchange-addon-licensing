<?php
/**
 * Template manager.
 *
 * @author Iron Bound Designs
 * @since  1.0
 *
 * @copyright   Copyright (c) 2015, Iron Bound Designs, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License v2 or later
 */

namespace IronBound\WP_Notifications\Template;

/**
 * Class Manager
 * @package IronBound\WP_Notifications\Template
 */
class Manager {

	/**
	 * @var Listener[]
	 */
	private $listeners = array();

	/**
	 * @var string
	 */
	private $type;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param $type
	 */
	public function __construct( $type ) {
		$this->type = $type;
	}

	/**
	 * Listen for a template tag.
	 *
	 * @since 1.0
	 *
	 * @param Listener $listener
	 *
	 * @return bool
	 */
	public function listen( Listener $listener ) {

		if ( isset( $this->listeners[ $listener->get_tag() ] ) ) {
			return false;
		}

		$this->listeners[ $listener->get_tag() ] = $listener;

		return true;
	}

	/**
	 * Get a listener for a certain tag.
	 *
	 * @param string $tag
	 *
	 * @return Listener|Null_Listener Null Listener is returned if listener for given tag does not exist.
	 */
	public function get_listener( $tag ) {
		return isset( $this->listeners[ $tag ] ) ? $this->listeners[ $tag ] : new Null_Listener();
	}

	/**
	 * Get all registered listeners.
	 *
	 * @since 1.0
	 *
	 * @return Listener[]
	 */
	public function get_listeners() {
		return $this->listeners;
	}

	/**
	 * Retrieve the type of this manager.
	 *
	 * Essentially a namespace for its listeners.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Return an array of the rendered tags.
	 *
	 * @since 1.0
	 *
	 * @param array $data_sources
	 *
	 * @return array
	 *
	 * @throws \BadMethodCallException
	 */
	public function render_tags( array $data_sources ) {

		$replaced = array();

		foreach ( $this->get_listeners() as $tag => $listener ) {

			$params = $listener->get_callback_reflection()->getParameters();

			$args = array();

			foreach ( $params as $param ) {

				$found = false;

				foreach ( $data_sources as $name => $data_source ) {

					if ( is_object( $data_source ) && ( $class = $param->getClass() ) !== null && $class->isInstance( $data_source ) ) {
						$args[] = $data_source;
						$found  = true;
					} elseif ( $param->getName() === $name ) {

						if ( isset( $class ) ) {
							throw new \BadMethodCallException(
								"Data source '$name' does not match type of required parameter '{$param->getName()}'. Required type: '{$class->getName()}''",
								1
							);
						}

						$args[] = $data_source;
						$found  = true;
					}

					if ( $found ) {
						break;
					}
				}

				if ( $found ) {
					continue;
				}

				if ( $param->isDefaultValueAvailable() ) {
					$args[] = $param->getDefaultValue();
				} else {
					throw new \BadMethodCallException( "Not all required parameters were provided. Required: '{$param->getName()}'.'", 2 );
				}
			}

			$replaced[ $tag ] = $listener->render( $args );
		}

		return $replaced;
	}
}