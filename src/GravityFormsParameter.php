<?php


namespace WPS\Plugins;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WPS\Plugins\GravityFormsParameter' ) ) {
	/**
	 * Class GravityFormsParameter
	 *
	 * @package WPS\Plugins
	 */
	class GravityFormsParameter {

		/**
		 * Paramter slug.
		 *
		 * @var string
		 */
		private $parameter;

		/**
		 * Value or Callback.
		 *
		 * @var string|callable
		 */
		private $value_or_callback;

		/**
		 * GravityFormsParameter constructor.
		 *
		 * @param string          $parameter         Parameter.
		 * @param string|callable $value_or_callback Callback to set the value or value.
		 */
		public function __construct( $parameter, $value_or_callback ) {

			$this->parameter         = $parameter;
			$this->value_or_callback = $value_or_callback;

			if ( is_callable( $value_or_callback ) ) {
				add_action( 'gform_field_value_' . $this->parameter, $value_or_callback, 10 );
			} else {
				add_action( 'gform_field_value_' . $this->parameter, array( $this, 'gform_field_value' ), 10 );
			}

		}

		/**
		 * Gravity form field value.
		 *
		 * @param mixed $value Value.
		 *
		 * @return callable|string
		 */
		public function gform_field_value( $value ) {

			return $this->value_or_callback;

		}

	}
}
