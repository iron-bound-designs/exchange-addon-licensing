<?php
/**
 * Customer licensing profile base.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Profile;

use ITELIC\Key;
use ITELIC\Plugin;
use ITELIC\Query\Keys;

/**
 * Class Licenses
 * @package ITELIC\Admin
 */
class Licenses extends Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'licensing', __( "Licensing", Plugin::SLUG ) );
	}

	/**
	 * Get the current customers keys.
	 *
	 * @since 1.0
	 *
	 * @return Key[]
	 */
	protected function get_keys() {
		return itelic_get_keys( array(
			'customer' => $this->user->ID
		) );
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	protected function render_page() {

		wp_enqueue_style( 'itelic-admin-licenses-profile' );
		?>

		<div class="user-edit-block <?php echo esc_attr( $this->get_tab_slug() ); ?>-user-edit-block">
			<div class="inner-wrap">

				<h3><?php _e( "Licenses", Plugin::SLUG ); ?></h3>

				<table>

					<thead>
					<tr>
						<th class="key"><?php _e( "Key", Plugin::SLUG ); ?></th>
						<th class="status"><?php _e( "Status", Plugin::SLUG ); ?></th>
						<th class="product"><?php _e( "Product", Plugin::SLUG ); ?></th>
					</tr>
					</thead>

					<tfoot>
					<tr>
						<th class="key"><?php _e( "Key", Plugin::SLUG ); ?></th>
						<th class="status"><?php _e( "Status", Plugin::SLUG ); ?></th>
						<th class="product"><?php _e( "Product", Plugin::SLUG ); ?></th>
					</tr>
					</tfoot>

					<tbody>

					<?php foreach ( $this->get_keys() as $key ): ?>
						<tr>
							<td class="key">
								<a href="<?php echo itelic_get_admin_edit_key_link( $key->get_key() ); ?>">
									<?php
									echo substr( $key->get_key(), 0, 30 );
									if ( strlen( $key->get_key() ) > 30 ) :
										echo '&hellip;';
									endif;
									?>
								</a>
							</td>
							<td class="status"><?php echo $key->get_status( true ); ?></td>
							<td class="product">
								<a href="<?php echo get_edit_post_link( $key->get_product()->ID ); ?>">
									<?php echo $key->get_product()->post_title; ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>

					</tbody>

				</table>

			</div>
		</div>

		<?php
	}
}