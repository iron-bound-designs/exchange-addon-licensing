<?php
/**
 * Single license view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Admin_Licenses_View_Single
 */
class ITELIC_Admin_Licenses_View_Single extends ITELIC_Admin_Tab_View {

	/**
	 * @var ITELIC_Key
	 */
	protected $key;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_Key $key
	 */
	public function __construct( ITELIC_Key $key ) {
		$this->key = $key;
	}

	/**
	 * Render the view.
	 */
	public function render() {

		wp_enqueue_style( 'itelic-admin-license-detail' );
		wp_enqueue_script( 'itelic-admin-license-detail' );
		wp_localize_script( 'itelic-admin-license-detail', 'ITELIC', array(
			'ajax'         => admin_url( 'admin-ajax.php' ),
			'key'          => $this->key->get_key(),
			'disabling'    => __( "Deactivating", ITELIC::SLUG ),
			'df'           => it_exchange_php_date_format_to_jquery_datepicker_format( $this->get_short_df() ),
			'update_nonce' => wp_create_nonce( 'itelic-update-key-' . $this->key->get_key() ),
			'statuses'     => json_encode( array(
				ITELIC_Key::ACTIVE   => ITELIC_Key::get_status_label( ITELIC_Key::ACTIVE ),
				ITELIC_Key::EXPIRED  => ITELIC_Key::get_status_label( ITELIC_Key::EXPIRED ),
				ITELIC_Key::DISABLED => ITELIC_Key::get_status_label( ITELIC_Key::DISABLED )
			) )
		) );
		?>

		<div id="it-exchange-license-details">
			<div class="spacing-wrapper bottom-border header-block">

				<div class="status status-<?php echo esc_attr( $this->key->get_status() ); ?>">
					<span data-value="<?php echo esc_attr( $this->key->get_status() ); ?>"><?php echo $this->key->get_status( true ); ?></span>
				</div>

				<div class="name-block">
					<h2 class="customer-name"><?php echo $this->key->get_customer()->wp_user->display_name; ?></h2>

					<h2 class="product-name"><?php echo $this->key->get_product()->post_title; ?></h2>
				</div>
				<div class="key-block">
					<p>
						<label for="license-key" class="screen-reader-text"><?php _e( "License Key", ITELIC::SLUG ); ?></label>
						<input type="text" id="license-key" size="<?php echo esc_attr( strlen( $this->key->get_key() ) ); ?>"
						       readonly value="<?php echo $this->key->get_key(); ?>">
					</p>
				</div>
			</div>

			<div class="spacing-wrapper bottom-border third-row misc-block">
				<div class="third expires">
					<h4><?php _e( "Expires", ITELIC::SLUG ); ?></h4>

					<h3>
						<?php if ( null === ( $d = $this->key->get_expires() ) ) : ?>
							<?php _e( "Forever", ITELIC::SLUG ); ?>
						<?php else: ?>
							<?php echo $d->format( $this->get_short_df() ); ?>
						<?php endif; ?>
					</h3>
				</div>
				<div class="third transaction">
					<h4><?php _e( "Transaction", ITELIC::SLUG ); ?></h4>

					<h3>
						<a href="<?php echo esc_url( get_edit_post_link( $this->key->get_transaction()->ID ) ); ?>">
							<?php echo it_exchange_get_transaction_order_number( $this->key->get_transaction() ); ?>
						</a>
					</h3>
				</div>
				<div class="third max-activations">
					<h4><?php _e( "Max Activations", ITELIC::SLUG ); ?></h4>

					<h3><?php echo $this->key->get_max(); ?></h3>
				</div>
			</div>

			<div class="spacing-wrapper activations">
				<h3><?php _e( "Activations", ITELIC::SLUG ); ?></h3>

				<table id="activations-table" class="widefat">
					<thead>
					<tr>
						<th><?php _e( "ID", ITELIC::SLUG ); ?></th>
						<th><?php _e( "Location", ITELIC::SLUG ); ?></th>
						<th><?php _e( "Status", ITELIC::SLUG ); ?></th>
						<th><?php _e( "Activation", ITELIC::SLUG ); ?></th>
						<th><?php _e( "Deactivation", ITELIC::SLUG ); ?></th>
						<th><span class="screen-reader-text"><?php _e( "Delete", ITELIC::SLUG ); ?></span></th>
					</tr>
					</thead>

					<tbody>
					<?php foreach ( $this->key->get_activations() as $activation ): ?>

						<?php echo $this->get_activation_row_html( $activation ); ?>

					<?php endforeach; ?>
					</tbody>
				</table>

				<h4><?php _e( "Remote Activate", ITELIC::SLUG ); ?></h4>

				<label for="remote-activate-location"><?php _e( "Install Location", ITELIC::SLUG ); ?></label>
				<input type="text" id="remote-activate-location" placeholder="<?php _e( "www.store.com", ITELIC::SLUG ); ?>">
				<input type="submit" id="remote-activate-submit" class="it-exchange-button" value="<?php esc_attr_e( "Activate", ITELIC::SLUG ); ?>">
				<input type="hidden" id="remote-activate-key" value="<?php echo esc_attr( $this->key->get_key() ); ?>">
				<?php wp_nonce_field( 'itelic-remote-activate-key-' . $this->key->get_key() ) ?>
			</div>
		</div>

	<?php
	}

	/**
	 * Get the activation row HTML.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_Activation $activation
	 *
	 * @return string
	 */
	public function get_activation_row_html( ITELIC_Activation $activation ) {
		$n_deactivate = wp_create_nonce( 'itelic-remote-deactivate-' . $activation->get_id() );
		$n_delete     = wp_create_nonce( 'itelic-remote-delete-' . $activation->get_id() );
		ob_start();
		?>

		<tr>
			<td><?php echo $activation->get_id(); ?></td>
			<td><?php echo $activation->get_location(); ?></td>
			<td><?php echo $activation->get_status( true ); ?></td>
			<td><?php echo $activation->get_activation()->format( $this->get_short_df() ); ?></td>
			<td>
				<?php if ( null === ( $d = $activation->get_deactivation() ) ): ?>
					<a href="javascript:" data-id="<?php echo esc_attr( $activation->get_id() ); ?>" data-nonce="<?php echo $n_deactivate; ?>" class="deactivate">
						<?php _e( "Deactivate", ITELIC::SLUG ); ?>
					</a>
				<?php else: ?>
					<?php echo $d->format( $this->get_short_df() ); ?>
				<?php endif; ?>
			</td>
			<td>
				<button data-id="<?php echo esc_attr( $activation->get_id() ); ?>" class="remove-item" data-nonce="<?php echo $n_delete; ?>">
					x
				</button>
			</td>
		</tr>

		<?php

		return ob_get_clean();
	}

	/**
	 * Get the date format. Replace full month name, with short month name if possible.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected function get_short_df() {
		return str_replace( 'F', 'M', get_option( 'date_format' ) );
	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Manage License", ITELIC::SLUG );
	}
}