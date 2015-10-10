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
 *
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
		add_action( 'wp_ajax_itelic_handle_release_file_upload', array( $this, 'process_file_upload' ) );
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

		try {
			$product = itelic_get_product( $_POST['product'] );
		}
		catch ( \Exception $e ) {
			$this->errors[] = $e->getMessage();

			return;
		}

		if ( ! $product->has_feature( 'licensing' ) ) {
			$this->errors[] = __( "Product selected does not support licensing.", Plugin::SLUG );

			return;
		}

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

		$changelog = empty( $_POST['whats-changed'] ) ? '' : $_POST['whats-changed'];

		$security_message = empty( $_POST['security-message'] ) ? '' : $_POST['security-message'];

		$action = isset( $_POST['release'] ) && $_POST['release'] ? 'release' : 'draft';
		$status = 'release' == $action ? Release::STATUS_ACTIVE : Release::STATUS_DRAFT;

		try {

			$release = Release::create( $product, $attachment, $version, $type, $status, $changelog );

			if ( $security_message ) {
				$release->add_meta( 'security-message', $security_message, true );
			}

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
			<p><?php echo implode( ', ', $this->errors ); ?>
			</p>
		</div>

		<?php
	}

	public function process_file_upload() {

		if ( ! current_user_can( 'upload_files' ) ) {
			die( 0 );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'itelic-new-release-file' ) ) {
			status_header( 403 );

			echo __( "Request expired. Please refresh and try again.", Plugin::SLUG );

			die();
		}

		$ID = media_handle_upload( 'file', 0 );

		echo $ID;

		die();
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0
	 */
	private function enqueue() {
		wp_enqueue_style( 'itelic-admin-releases-new' );
		wp_enqueue_script( 'itelic-admin-releases-new' );
		wp_localize_script( 'itelic-admin-releases-new', 'ITELIC', array(
			'prevVersion'  => __( "Current version: %s", Plugin::SLUG ),
			'uploadTitle'  => __( "Choose Software File", Plugin::SLUG ),
			'uploadButton' => __( "Select File", Plugin::SLUG ),
			'uploadLabel'  => __( "Upload File", Plugin::SLUG ),
			'nonce'        => wp_create_nonce( 'itelic-new-release-file' )
		) );

		wp_enqueue_media();
	}
}