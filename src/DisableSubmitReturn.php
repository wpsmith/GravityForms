<?php


namespace WPS\WP\Plugins\GravityForms;


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\DisableSubmitReturn' ) ) {
	/**
	 * Disable Submit Button Until Required Fields are Field Out
	 *
	 * Disable submit buttones until all required fields have been filled out.
	 *
	 * @version     1.0.0
	 * @author      Travis Smith <t@wpsmith.net> & David Smith <david@gravitywiz.com>
	 * @license     GPL-2.0+
	 * @link        https://wpsmith.net
	 * @copyright   2013-2018 WP Smith, Gravity Wiz
	 */
	class DisableSubmitReturn {

		/**
		 * Whether to output script.
		 *
		 * @var bool
		 */
		public static $script_output = false;

		/**
		 * DisableSubmit constructor.
		 *
		 * @param int $form_id Form ID.
		 */
		public function __construct( $form_id ) {

			add_action( "gform_pre_render_{$form_id}", array( $this, 'maybe_output_script' ) );

		}

		/**
		 * Conditionally output the script.
		 *
		 * @param array $form Gravity Forms form object.
		 *
		 * @return array
		 */
		public function maybe_output_script( $form ) {

			if ( ! self::$script_output ) {
				$this->script();
				self::$script_output = true;
			}

			return $form;
		}

		/**
		 * Outputs the inline script.
		 */
		public function script() {
			?>

			<script type="text/javascript">

                (function ($) {

                    $(document).on( 'keypress', '.gform_wrapper', function (e) {
                        var code = e.keyCode || e.which;
                        if ( 13 === code && ! jQuery( e.target ).is( 'textarea,input[type="submit"],input[type="button"]' ) ) {
                            e.preventDefault();
                            return false;
                        }
                    } );

                })(jQuery);

			</script>

			<?php
		}

	}
}
