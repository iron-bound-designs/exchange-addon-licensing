<?php
/**
 * Controller for add new release view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\Controller;

use ITELIC\Admin\Releases\Controller;
use ITELIC\Admin\Releases\View\Add_New as Add_New_View;
use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Plugin;
use ITELIC\Release;

/**
 * Class Add_New
 * @package ITELIC\Admin\Releases\Controller
 */
class Add_New extends Controller {

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'save_new_release' ) );
		add_action( 'admin_notices', array( $this, 'display_errors' ) );
	}

	/**
	 * Save post data to a new release.
	 *
	 * @since 1.0
	 */
	public function save_new_release() {

		if ( ! isset( $_POST['itelic-action'] ) || $_POST['itelic-action'] != 'add-new-release' ) {
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'itelic-add-new-release' ) ) {
			$this->errors[] = __( "Request expired. Please try again.", Plugin::SLUG );

			return;
		}

		if ( ! isset( $_POST['type-select'] ) || ! array_key_exists( $_POST['type-select'], Release::get_types() ) ) {
			$this->errors[] = __( "Invalid release type selected.", Plugin::SLUG );

			return;
		}

		$type = $_POST['type-select'];

		if ( empty( $_POST['product'] ) || ! it_exchange_product_has_feature( $_POST['product'], 'licensing' ) ) {
			$this->errors[] = __( "Product selected does not support licensing.", Plugin::SLUG );

			return;
		}

		$product = it_exchange_get_product( $_POST['product'] );

		if ( empty( $_POST['version'] ) ) {
			$this->errors[] = __( "Invalid version number entered.", Plugin::SLUG );

			return;
		}

		$version = sanitize_text_field( $_POST['version'] );

		if ( empty( $_POST['upload-file'] ) ) {
			$this->errors[] = __( "No software file selected.", Plugin::SLUG );

			return;
		}

		$attachment = get_post( $_POST['upload-file'] );
		$download   = Release::convert_attachment_to_download( $attachment, $product );

		$changelog = empty( $_POST['whats-changed'] ) ? '' : $_POST['whats-changed'];

		$security_message = empty( $_POST['security-message'] ) ? '' : $_POST['security-message'];

		$action = isset( $_POST['release'] ) && $_POST['release'] ? 'release' : 'draft';
		$status = 'release' == $action ? Release::STATUS_ACTIVE : Release::STATUS_DRAFT;

		try {

			$release = Release::create( $product, $download->ID, $version, $type, $status, $changelog );

			if ( $release ) {
				$url = add_query_arg( array(
					'ID'   => $release->get_ID(),
					'view' => 'single',
					'new'  => true
				), Dispatch::get_tab_link( 'releases' ) );

				wp_redirect( $url );
				die();
			}
		}
		catch ( \Exception $e ) {
			$this->errors[] = $e->getMessage();
		}
	}

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {

		$this->enqueue();

		$view = new Add_New_View();

		$view->begin();
		$view->title();

		$view->tabs( 'releases' );

		$view->render();

		$view->end();
	}

	/**
	 * Display the errors encountered while saving.
	 *
	 * @since 1.0
	 */
	public function display_errors() {

		if ( count( $this->errors ) == 0 ) {
			return;
		}


		?>

		<div class="notice notice-error">
			<p><?php implode( ', ', $this->errors ); ?>
			</p>
		</div>

		<?php
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0
	 */
	private function enqueue() {
		wp_enqueue_style( 'itelic-admin-releases' );
		wp_enqueue_script( 'itelic-admin-releases' );
		wp_localize_script( 'itelic-admin-releases', 'ITELIC', array(
			'prevVersion'  => __( "Previous version: %s", Plugin::SLUG ),
			'uploadTitle'  => __( "Choose Software File", Plugin::SLUG ),
			'uploadButton' => __( "Select File", Plugin::SLUG ),
			'uploadLabel'  => __( "Upload File", Plugin::SLUG )
		) );

		wp_enqueue_media();
	}
}