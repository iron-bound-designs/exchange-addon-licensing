<?php
/**
 * Build an editor.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 *
 * @copyright   Copyright (c) 2015, Iron Bound Designs, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License v2 or later
 */

namespace IronBound\WP_Notifications\Template;

/**
 * Class Editor
 * @package IronBound\WP_Notifications\Template
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
	 * @var array
	 */
	private $translations = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Manager $manager
	 * @param array   $translations
	 */
	public function __construct( Manager $manager, array $translations ) {
		remove_action( 'media_buttons', 'media_buttons' );
		add_action( 'media_buttons', array( $this, 'display_template_tag_button' ), 15 );
		add_filter( 'mce_buttons', array( $this, 'modify_mce_buttons' ) );

		$this->manager      = $manager;
		$this->translations = $translations;
		self::$count += 1;
	}

	/**
	 * Render the thickbox.
	 *
	 * This outputs the HTML and JS.
	 *
	 * @since 1.0
	 */
	public function thickbox() {
		?>

		<script type="text/javascript">

			jQuery(document).ready(function ($) {

				$(document).on('click', '#ibd-wp-notifications-template-tags-<?php echo self::$count; ?>', function (e) {
					var tag = jQuery("#ibd-wp-notifications-add-tag-value-<?php echo self::$count; ?>").val();
					if (tag.length == 0 || tag == -1) {
						alert("<?php echo esc_js($this->translations['mustSelectItem']); ?>");
						return;
					}
					window.send_to_editor(tag);
					tb_remove();
				});
			});
		</script>

		<div id="ibd-wp-notifications-select-tag-<?php echo self::$count; ?>" style="display: none">
			<div class="wrap">
				<div>
					<p><?php echo $this->translations['selectTemplateTag']; ?></p>

					<label for="ibd-wp-notifications-add-tag-value-<?php echo self::$count; ?>"><?php echo $this->translations['templateTag']; ?></label><br>

					<select id="ibd-wp-notifications-add-tag-value-<?php echo self::$count; ?>">
						<option value="-1"><?php echo $this->translations['selectATag']; ?></option>

						<?php foreach ( $this->manager->get_listeners() as $listener ): ?>
							<option value="<?php echo esc_attr( "{" . $listener->get_tag() . "}" ); ?>">
								<?php echo $listener; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="padding: 15px 15px 15px 0">
					<input type="button" class="button-primary" id="ibd-wp-notifications-template-tags-<?php echo self::$count; ?>" value="<?php echo esc_attr( $this->translations['insertTag'] ); ?>" />
					&nbsp;&nbsp;&nbsp;
					<a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;">
						<?php echo $this->translations['cancel']; ?>
					</a>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Display the template tag button button.
	 *
	 * @since 1.0
	 */
	public function display_template_tag_button() {
		add_thickbox();
		$id    = 'ibd-wp-notifications-select-tag-' . self::$count;
		$class = 'thickbox button ibd-wp-notifications-tags';
		$title = $this->translations['insertTemplateTag'];


		echo '<a href="#TB_inline?width=150height=250&inlineId=' . $id . '" class="' . $class . '" title="' . $title . '"> ' . $title . '</a>';
	}

	/**
	 * Modify the tinyMCE buttons.
	 *
	 * Remove the more tag.
	 *
	 * @param array $buttons
	 *
	 * @return array
	 */
	public function modify_mce_buttons( $buttons ) {
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
		remove_action( 'media_buttons', array( $this, 'display_template_tag_button' ), 15 );
		remove_filter( 'mce_buttons', array( $this, 'modify_mce_buttons' ) );
	}
}