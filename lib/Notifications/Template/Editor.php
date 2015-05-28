<?php
/**
 * Build an editor.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Notifications\Template;
use ITELIC\Plugin;

/**
 * Class Editor
 * @package ITELIC\Notifications\Template
 */
class Editor {

	/**
	 * @var Manager
	 */
	private $manager;

	/**
	 * @var int
	 */
	private static $count = 0;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Manager $manager
	 */
	public function __construct( Manager $manager ) {
		remove_action( 'media_buttons', 'media_buttons' );
		add_action( 'media_buttons', array( __CLASS__, 'display_shortcode_button' ), 15 );
		add_filter( 'mce_buttons', array( __CLASS__, 'modify_mce_buttons' ) );

		$this->manager = $manager;
		self::$count += 1;
	}

	/**
	 * Display the shortcode popup.
	 *
	 * @since 1.0
	 */
	public function shortcode_popup() {
		?>

		<script type="text/javascript">

			jQuery(document).ready(function ($) {

				$(document).on('click', '#itelic-template-tags-<?php echo self::$count; ?>', function (e) {
					var tag = jQuery("#add-tag-value-<?php echo self::$count; ?>").val();
					if (tag.length == 0 || tag == -1) {
						alert("<?php _e("You must select an item."); ?>");
						return;
					}
					window.send_to_editor(tag);
					tb_remove();
				});
			});
		</script>

		<div id="itelic-select-tag-<?php echo self::$count; ?>" style="display: none">
			<div class="wrap">
				<div>
					<p><?php _e( "Select a template tag to insert" ); ?></p>

					<label for="add-tag-value-<?php echo self::$count; ?>"><?php _e( "Template Tag" ); ?></label><br>

					<select id="add-tag-value-<?php echo self::$count; ?>">
						<option value="-1"><?php _e( "Select a tag..." ); ?></option>

						<?php foreach ( $this->manager->get_listeners() as $listener ): ?>
							<option value="<?php echo esc_attr( "{" . $listener->get_tag() . "}" ); ?>">
								<?php echo $listener; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="padding: 15px 15px 15px 0">
					<input type="button" class="button-primary" id="itelic-template-tags-<?php echo self::$count; ?>" value="<?php _e( 'Insert Tag' ); ?>" />
					&nbsp;&nbsp;&nbsp;
					<a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;">
						<?php _e( 'Cancel' ); ?>
					</a>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Display the shortcode button.
	 *
	 * @since 1.0
	 */
	public static function display_shortcode_button() {
		add_thickbox();
		$id = 'itelic-select-tag-' . self::$count;

		echo '<a href="#TB_inline?width=150height=250&inlineId=' . $id .'" class="thickbox button itelic_tags" id="itelic_add_tag" title="' . __( 'Insert Template Tag', Plugin::SLUG ) . '"> ' . __( 'Insert Template Tag', Plugin::SLUG ) . '</a>';
	}

	/**
	 * Modify the tinyMCE buttons.
	 *
	 * @param array $buttons
	 *
	 * @return array
	 */
	public static function modify_mce_buttons( $buttons ) {
		unset( $buttons[ array_search( 'wp_more', $buttons ) ] );

		return $buttons;
	}

	/**
	 * Destructor.
	 *
	 * @since 1.0
	 */
	public function __destruct() {
		add_action( 'media_buttons', 'media_buttons' );
		remove_action( 'media_buttons', array( __CLASS__, 'display_shortcode_button' ), 15 );
		remove_filter( 'mce_buttons', array( __CLASS__, 'modify_mce_buttons' ) );
	}
}