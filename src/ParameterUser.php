<?php

namespace WPS\Plugins\GravityForms;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WPS\Plugins\GravityForms\ParameterUser' ) ) {
	/**
	 * Class GravityFormsParameterUser
	 *
	 * @package WPS\Plugins
	 */
	class ParameterUser {

		/**
		 * \WP_User key.
		 *
		 * @var string
		 */
		private $user_field;

		/**
		 * User meta field key.
		 *
		 * @var string
		 */
		private $user_meta_field;

		/**
		 * GravityFormsParameterUser constructor.
		 *
		 * @param string $parameter       Parameter name.
		 * @param string $user_field      \WP_User key.
		 * @param string $user_meta_field User meta field key.
		 */
		public function __construct( $parameter, $user_field, $user_meta_field = '' ) {

			if ( ! is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
				return;
			}

			$this->user_field      = $user_field;
			$this->user_meta_field = $user_meta_field;

			add_action( "gform_field_value_$parameter", array( $this, 'gform_field_value' ), 10 );

		}

		/**
		 * Sets the form value dynamically.
		 *
		 * @param string $value Previous value.
		 *
		 * @return string
		 */
		public function gform_field_value( $value ) {

			if ( ! is_user_logged_in() ) {
				return $value;
			}

			$user = wp_get_current_user();

			if ( '' !== $this->user_field ) {
				return $user->{$this->user_field};
			}

			if ( '' !== $this->user_meta_field ) {
				return get_user_meta( $user->ID, $this->user_meta_field, true );
			}

			return $value;

		}

	}
}
