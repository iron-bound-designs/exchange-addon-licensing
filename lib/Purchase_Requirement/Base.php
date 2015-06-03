<?php
/**
 * Base Purchase Requirement Class
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Purchase_Requirement;

/**
 * Class Base
 * @package ITELIC\Purchase_Requirement
 */
class Base {

	/**
	 * @var string
	 */
	protected $slug;

	/**
	 * @var array
	 */
	protected $args;

	/**
	 * @var \Closure
	 */
	protected $check_if_complete;

	/**
	 * @var array
	 */
	protected $cache_data = array();

	/**
	 * @var bool
	 */
	protected $dirty = false;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param string   $slug
	 * @param array    $args
	 * @param \Closure $complete Becomes the requirement met function. ($this) is passed as a parameter.
	 */
	public function __construct( $slug, array $args, \Closure $complete ) {

		$args['slug']            = $slug;
		$args['requirement-met'] = array( $this, 'requirement_met' );

		$this->args              = $args;
		$this->slug              = $slug;
		$this->check_if_complete = $complete;

		$this->refresh();
	}

	/**
	 * Check if the requirement has been met.
	 *
	 * This will call the Closure given in the constructor with $this as an argument.
	 */
	public function requirement_met() {

		if ( isset( $this->cache_data['complete'] ) && $this->cache_data['complete'] ) {
			return true;
		}

		$func = $this->check_if_complete;

		return $func( $this );
	}

	/**
	 * Register the purchase requirement with Exchange.
	 */
	public function register() {
		it_exchange_register_purchase_requirement( $this->slug, $this->args );

		$slug = $this->slug;

		add_filter( 'it_exchange_super_widget_valid_states', function ( $valid_states ) use ( $slug ) {
			$valid_states[] = $slug;

			return $valid_states;
		} );

		add_action( 'shutdown', array( $this, 'persist' ) );
	}

	/**
	 * Retrieve the cached data.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_cache_data() {
		return $this->cache_data;
	}

	/**
	 * Update the cached data.
	 *
	 * This will override any properties already set with the properties given, without overriding other properties.
	 *
	 * @since 1.0
	 *
	 * @param array $data
	 */
	public function update_cache_data( array $data ) {
		$this->set_cache_data( wp_parse_args( $data, $this->cache_data ) );
	}

	/**
	 * Set the cached data.
	 *
	 * @since 1.0
	 *
	 * @param array $data
	 */
	public function set_cache_data( array $data ) {

		if ( isset( $data['complete'] ) ) {
			throw new \InvalidArgumentException( "complete is a reserved parameter." );
		}

		$this->cache_data = $data;
		$this->dirty = true;
	}

	/**
	 * Clear all the data from the cache.
	 *
	 * @since 1.0
	 */
	public function clear_cache_data() {
		$this->set_cache_data( array() );
	}

	/**
	 * Remove an item from the cached data.
	 *
	 * @since 1.0
	 *
	 * @param string $item
	 */
	public function remove_cache_data( $item ) {

		$data = $this->get_cache_data();
		unset( $data[ $item ] );

		$this->set_cache_data( $data );
	}

	/**
	 * Mark this purchase requirement as complete.
	 *
	 * @since 1.0
	 */
	public function mark_complete() {
		$this->cache_data['complete'] = true;
	}

	/**
	 * Mark this purchase requirement as incomplete.
	 *
	 * @since 1.0
	 */
	public function mark_incomplete() {
		$this->cache_data['complete'] = false;
	}

	/**
	 * Check if the local cache is dirty.
	 *
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function is_dirty() {
		return $this->dirty;
	}

	/**
	 * Refresh the cache data from the Exchange session manager.
	 *
	 * @since 1.0
	 */
	public function refresh() {
		$this->cache_data = it_exchange_get_session_data( "purchase_req_{$this->slug}" );
	}

	/**
	 * Persist the cached data to the Exchange session manager.
	 *
	 * Data is only persisted if the local cache is dirty.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function persist() {
		if ( $this->is_dirty() ) {
			it_exchange_add_session_data( "purchase_req_{$this->slug}", $this->cache_data );

			return true;
		}

		return false;
	}
}