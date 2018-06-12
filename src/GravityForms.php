<?php
/**
 * Gravity Forms Class
 *
 * Extends Gravity Forms.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\Core
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2018 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\Plugins;

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS\Plugins\GravityForms' ) ) {
	/**
	 * Class Gravity_Forms
	 *
	 * @package WPS\Plugins
	 */
	class GravityForms extends Singleton {

		/**
		 * GravityForms constructor.
		 */
		public function __construct() {
			if ( ! is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
				return;
			}

			add_filter( 'gform_enable_field_label_visibility_settings', '__return_true' );
		}

	}
}
