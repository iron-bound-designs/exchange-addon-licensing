<?php
/**
 * Single license view.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Licenses\View;

use ITELIC\Activation;
use ITELIC\Admin\Tab\View;
use ITELIC\Key;
use ITELIC\Plugin;
use ITELIC\Renewal;

/**
 * Class Single
 * @package ITELIC\Admin\Licenses\View
 */
class Single extends View {

	/**
	 * @var Key
	 */
	protected $key;

	/**
	 * @var Renewal[]
	 */
	protected $renewals = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Key       $key
	 * @param Renewal[] $renewals
	 */
	public function __construct( Key $key = null, array $renewals = array() ) {
		$this->key      = $key;
		$this->renewals = $renewals;
	}

	/**
	 * Render the view.
	 */
	public function render() {

		if ( ! $this->key ) {
			return;
		}

		wp_enqueue_style( 'itelic-admin-license-detail' );
		wp_enqueue_script( 'itelic-admin-license-detail' );
		wp_localize_script( 'itelic-admin-license-detail', 'ITELIC', array(
			'ajax'         => admin_url( 'admin-ajax.php' ),
			'key'          => $this->key->get_key(),
			'disabling'    => __( "Deactivating", Plugin::SLUG ),
			'df'           => it_exchange_php_date_format_to_jquery_datepicker_format( $this->get_short_df() ),
			'update_nonce' => wp_create_nonce( 'itelic-update-key-' . $this->key->get_key() ),
			'statuses'     => json_encode( Key::get_statuses() )
		) );

		$jdf = it_exchange_php_date_format_to_jquery_datepicker_format( $this->get_short_df() );

		$online = $this->key->is_online_product();

		$disable_activate = $this->key->get_status() != Key::ACTIVE;
		$disable_tip = __( "Disabled or expired licenses cannot be activated.", Plugin::SLUG );
		$disable_title = $disable_activate ? "title=\"$disable_tip\"" : '';
		$disable_class = $disable_activate ? 'button-disabled' : '';
		$disabled_input = $disable_activate ? ' disabled' : '';
		?>

		<div id="it-exchange-license-details">
			<div class="spacing-wrapper bottom-border header-block">

				<div class="status status-<?php echo esc_attr( $this->key->get_status() ); ?>">
					<span data-value="<?php echo esc_attr( $this->key->get_status() ); ?>" title="<?php esc_attr_e( "Click to edit", Plugin::SLUG ); ?>">
						<?php echo $this->key->get_status( true ); ?>
					</span>
				</div>

				<div class="name-block">
					<h2 class="customer-name"><?php echo $this->key->get_customer()->wp_user->display_name; ?></h2>

					<h2 class="product-name"><?php echo $this->key->get_product()->post_title; ?></h2>
				</div>
				<div class="key-block">
					<p>
						<label for="license-key" class="screen-reader-text"><?php _e( "License Key", Plugin::SLUG ); ?></label>
						<input type="text" id="license-key" size="<?php echo strlen( $this->key->get_key() ); ?>"
						       readonly value="<?php echo esc_attr( $this->key->get_key() ); ?>">
					</p>
				</div>
			</div>

			<div class="spacing-wrapper bottom-border third-row misc-block">
				<div class="third expires">
					<h4><?php _e( "Expires", Plugin::SLUG ); ?></h4>

					<h3 title="<?php esc_attr_e( "Click to edit", Plugin::SLUG ); ?>" data-df="<?php echo $jdf; ?>">
						<?php if ( null === ( $d = $this->key->get_expires() ) ) : ?>
							<?php _e( "Never", Plugin::SLUG ); ?>
						<?php else: ?>
							<?php echo \ITELIC\convert_gmt_to_local( $d )->format( $this->get_short_df() ); ?>
						<?php endif; ?>
					</h3>
				</div>
				<div class="third transaction">
					<h4><?php _e( "Transaction", Plugin::SLUG ); ?></h4>

					<h3>
						<a href="<?php echo esc_url( get_edit_post_link( $this->key->get_transaction()->ID ) ); ?>">
							<?php echo it_exchange_get_transaction_order_number( $this->key->get_transaction() ); ?>
						</a>
					</h3>
				</div>
				<div class="third max-activations">
					<h4><?php _e( "Max Activations", Plugin::SLUG ); ?></h4>

					<h3 title="<?php esc_attr_e( "Click to edit", Plugin::SLUG ); ?>">
						<?php echo $this->key->get_max() ? $this->key->get_max() : __( 'Unlimited', Plugin::SLUG ); ?>
					</h3>
				</div>
			</div>

			<div class="spacing-wrapper activations<?php echo count( $this->renewals ) ? ' bottom-border' : ''; ?>">
				<h3><?php _e( "Activations", Plugin::SLUG ); ?></h3>

				<table id="activations-table" class="widefat">
					<thead>
					<tr>
						<th class="location-col"><?php _e( "Location", Plugin::SLUG ); ?></th>
						<th class="status-col"><?php _e( "Status", Plugin::SLUG ); ?></th>
						<th class="activation-col"><?php _e( "Activation", Plugin::SLUG ); ?></th>
						<th class="deactivation-col"><?php _e( "Deactivation", Plugin::SLUG ); ?></th>
						<th class="version-col"><?php _e( "Version", Plugin::SLUG ); ?></th>
						<th class="delete-col">
							<span class="screen-reader-text"><?php _e( "Delete", Plugin::SLUG ); ?></span></th>
					</tr>
					</thead>

					<tbody>
					<?php foreach ( $this->key->get_activations() as $activation ): ?>

						<?php echo $this->get_activation_row_html( $activation ); ?>

					<?php endforeach; ?>
					</tbody>
				</table>

				<h4><?php _e( "Remote Activate", Plugin::SLUG ); ?></h4>

				<label for="remote-activate-location" class="screen-reader-text"><?php _e( "Install Location", Plugin::SLUG ); ?></label>
				<input type="<?php echo $online ? 'url' : 'text'; ?>" id="remote-activate-location"
				       placeholder="<?php _e( "Install Location", Plugin::SLUG ); ?>"<?php echo $disabled_input; ?>>
				<input type="submit" id="remote-activate-submit" class="it-exchange-button <?php echo $disable_class; ?>"
				       value="<?php esc_attr_e( "Activate", Plugin::SLUG ); ?>" data-tip="<?php echo $disable_tip; ?>"<?php echo $disable_title; ?>>
				<input type="hidden" id="remote-activate-key" value="<?php echo esc_attr( $this->key->get_key() ); ?>">
				<?php wp_nonce_field( 'itelic-remote-activate-key-' . $this->key->get_key() ) ?>
			</div>

			<?php if ( count( $this->renewals ) ): ?>

				<div class="spacing-wrapper renewals">

					<h3><?php _e( "Renewal History", Plugin::SLUG ); ?></h3>

					<ul>
						<?php foreach ( $this->renewals as $renewal ): ?>

							<li>
								<?php echo $renewal->get_renewal_date()->format( get_option( 'date_format' ) ); ?>
								&nbsp;&mdash;&nbsp;

								<?php if ( $renewal->get_transaction() ): ?>
									<a href="<?php echo get_edit_post_link( $renewal->get_transaction()->ID ); ?>">
										<?php echo it_exchange_get_transaction_order_number( $renewal->get_transaction() ); ?>
									</a>
								<?php else: ?>
									<?php _e( "Manual Renewal", Plugin::SLUG ); ?>
								<?php endif; ?>
							</li>

						<?php endforeach; ?>
					</ul>

				</div>

			<?php endif; ?>

			<?php
			/**
			 * Fires at the end of the single license screen.
			 *
			 * @since 1.0
			 *
			 * @param Key $key
			 */
			do_action( 'itelic_single_license_screen_end', $this->key ); ?>

		</div>

		<?php

		/**
		 * Fires after the main single license screen.
		 *
		 * @since 1.0
		 *
		 * @param Key $key
		 */
		do_action( 'itelic_single_license_screen_after', $this->key );
	}

	/**
	 * Get the activation row HTML.
	 *
	 * @since 1.0
	 *
	 * @param Activation $activation
	 *
	 * @return string
	 */
	public function get_activation_row_html( Activation $activation ) {
		$n_deactivate = wp_create_nonce( 'itelic-remote-deactivate-' . $activation->get_id() );
		$n_delete     = wp_create_nonce( 'itelic-remote-delete-' . $activation->get_id() );
		ob_start();
		?>

		<tr>
			<td data-title="<?php _e( "Location", Plugin::SLUG ); ?>">
				<?php echo $activation->get_location(); ?>
			</td>
			<td data-title="<?php _e( "Status", Plugin::SLUG ); ?>">
				<?php echo $activation->get_status( true ); ?>
			</td>
			<td data-title="<?php _e( "Activation", Plugin::SLUG ); ?>">
				<?php echo \ITELIC\convert_gmt_to_local( $activation->get_activation() )->format( $this->get_short_df() ); ?>
			</td>
			<td data-title="<?php _e( "Deactivation", Plugin::SLUG ); ?>">
				<?php if ( null === ( $d = $activation->get_deactivation() ) ): ?>
					<a href="javascript:" data-id="<?php echo esc_attr( $activation->get_id() ); ?>" data-nonce="<?php echo $n_deactivate; ?>" class="deactivate">
						<?php _e( "Deactivate", Plugin::SLUG ); ?>
					</a>
				<?php else: ?>
					<?php echo \ITELIC\convert_gmt_to_local( $d )->format( $this->get_short_df() ); ?>
				<?php endif; ?>
			</td>
			<td data-title="<?php _e( "Version", Plugin::SLUG ); ?>">
				<?php if ( null === ( $r = $activation->get_release() ) ): ?>
					<?php _e( "Unknown", Plugin::SLUG ); ?>
				<?php else: ?>
					<?php printf( 'v%s', $r->get_version() ); ?>
				<?php endif; ?>
			</td>
			<td data-title="<?php _e( "Delete", Plugin::SLUG ); ?>">
				<button data-id="<?php echo esc_attr( $activation->get_id() ); ?>" class="remove-item" data-nonce="<?php echo $n_delete; ?>">
					&times;
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
		return __( "Manage License", Plugin::SLUG );
	}
}