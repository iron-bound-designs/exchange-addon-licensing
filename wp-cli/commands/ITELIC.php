<?php
/**
 * Base command for the plugin.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Command
 */
class ITELIC_Command extends WP_CLI_Command {

}

WP_CLI::add_command( 'itelic', 'ITELIC_Command' );