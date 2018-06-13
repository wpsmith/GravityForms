<?php


namespace WPS\Plugins;

use WPS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WPS\Plugins\GravityFormsPersonalData' ) ) {
	/**
	 * Class GravityFormsPersonalData
	 *
	 * @package WPS\Plugins
	 */
	class GravityFormsPersonalData extends WPS\Core\Singleton {

		/**
		 * Array of Keys
		 *
		 * @var \WP_Post[]
		 */
		private $keys;

		/**
		 * Array of Gravity Forms Form Objects.
		 *
		 * @var array
		 */
		private $forms;

		/**
		 * Array of Form Inputs by form ID.
		 *
		 * @var array[]
		 */
		private $forminputs;

		/**
		 * GravityFormsPersonalData constructor.
		 */
		protected function __construct() {

			if ( ! is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
				return;
			}

			add_action( 'wp_privacy_personal_data_exporters', array( $this, 'register_gdpr_exporter' ), 100 );
			add_filter( 'wps_gravityforms_export_personal_data_value', array( $this, 'personal_data_value' ), 10, 4 );
			add_action( 'gform_entry_created', array( $this, 'gform_entry_created' ), 10, 2 );
			add_action( 'gform_entry_pre_update', array( $this, 'gform_entry_pre_update' ), 100 );

		}

		/**
		 * Modifies the personal data value.
		 *
		 * @param mixed                    $value Value of the entry input.
		 * @param string                   $key   Key of the entry input.
		 * @param GravityFormsPersonalData $self  Contextual.
		 * @param array                    $entry Entry Object array.
		 *
		 * @return string
		 */
		public function personal_data_value( $value, $key, $self, $entry ) {
			$field = $this->get_field_by_input_id( $entry['form_id'], $key );
			if ( false === $field ) {
				return $value;
			}

			if ( 'Key' !== $field->label ) {
				return $value;
			}

			if ( ! is_numeric( $value ) ) {
				return $value;
			}

			$k = $this->get_key_by_id( $value );
			if ( false !== $k ) {
				return $k->post_title;
			}

			return $value;
		}

		/**
		 * Returns the IP address is 0.0.0.0.
		 *
		 * @return string
		 */
		public function get_ip() {
			return '0.0.0.0';
		}

		/**
		 * Ensures the IP address is 0.0.0.0.
		 *
		 * @param array $entry          Entry object.
		 * @param array $original_entry Entry object.
		 *
		 * @return mixed
		 */
		public function gform_entry_pre_update( $entry, $original_entry ) {
			if ( isset( $entry['ip'] ) ) {
				$entry['ip'] = $this->get_ip();
			}

			return $entry;
		}

		/**
		 * Gets all the keys.
		 *
		 * @return \WP_Post[]
		 */
		private function get_keys() {
			if ( ! empty( $this->keys ) ) {
				return $this->keys;
			}
			$this->keys = get_posts( array( 'post_type' => 'key' ) );
		}

		/**
		 * Updates a Gravity Form entry.
		 *
		 * @param array $entry Entry Object.
		 * @param array $form  Form Object.
		 */
		public function gform_entry_created( $entry, $form ) {
			\GFAPI::update_entry_property( $entry['id'], 'ip', '' );
		}

		/**
		 * Gets a key by post ID.
		 *
		 * @param int $id Key Post ID.
		 *
		 * @return bool|\WP_Post
		 */
		private function get_key_by_id( $id ) {

			foreach ( $this->get_keys() as $key ) {
				if ( is_numeric( $id ) && (int) $id === (int) $key->ID ) {
					return $key;
				}
			}

			return false;
		}

		/**
		 * Gets a field by input ID.
		 *
		 * @param int        $form_id  Form ID.
		 * @param int|string $input_id Input ID.
		 *
		 * @return bool
		 */
		private function get_field_by_input_id( $form_id, $input_id ) {

			if ( in_array( $input_id, $this->get_excluded(), true ) ) {
				return false;
			}

			if ( in_array( $input_id, array_keys( $this->get_labels() ), true ) ) {
				$labels = $this->get_labels();
				$label  = $labels[ $input_id ];
			} else {
				$label = $this->forminputs[ $form_id ][ $input_id ];
			}

			foreach ( $this->forms as $form ) {
				foreach ( $form['fields'] as $field ) {
					if ( $field->id === $input_id || $label === $field->label ) {
						return $field;
					}

					$inputs = $field->get_entry_inputs();
					if ( ! empty( $inputs ) ) {
						foreach ( $inputs as $input ) {
							if ( $input['id'] === $input_id || $label === $input['label'] ) {
								return $field;
							}
//						$this->forminputs[ $form['id'] ][ $input['id'] ] = $input['label'];
						}
					}
				}
			}

			return false;
		}

		/**
		 * Sets up the forminputs
		 */
		private function setup_forminputs() {
			$this->forminputs = array();
			foreach ( $this->forms as $form ) {
				$this->forminputs[ $form['id'] ] = array();
				foreach ( $form['fields'] as $field ) {
					$inputs = $field->get_entry_inputs();
					if ( ! empty( $inputs ) ) {
						foreach ( $inputs as $input ) {
							$this->forminputs[ $form['id'] ][ $input['id'] ] = $input['label'];
						}
					} else {
						$this->forminputs[ $form['id'] ][ $field->id ] = $field->label;
					}
				}
			}
		}

		/**
		 * Gets entry properties to exclude from exporter.
		 *
		 * @return string[]
		 */
		private function get_excluded() {
			return array(
				'is_starred',
				'is_read',
				'is_fulfilled',
			);
		}

		/**
		 * Gets labels for exporter.
		 *
		 * @return array
		 */
		private function get_labels() {
			return array(
				'form_id'          => __( 'Form ID', 'wps' ),
				'date_updated'     => __( 'Date Updated', 'wps' ),
				'currency'         => __( 'Currency', 'wps' ),
				'payment_method'   => __( 'Payment Method', 'wps' ),
				'transaction_type' => __( 'Transaction Type', 'wps' ),
				'status'           => __( 'Status', 'wps' ),
			);
		}

		/**
		 * Get entries by email address.
		 *
		 * @param string $email Email address.
		 *
		 * @return array Array of entries.
		 */
		public function get_entries_by_email( $email ) {
			$entries     = array();
			$this->forms = array();

			// Get all forms that have email addresses associated.
			$forms = \GFAPI::get_forms();
			if ( ! class_exists( 'GFExport' ) ) {
				require_once( GFCommon::get_base_path() . '/export.php' );
			}

			foreach ( $forms as $form ) {
				$fields = \GFAPI::get_fields_by_type( $form, 'email' );
				if ( ! empty( $fields ) ) {
					$this->forms[ $form['id'] ] = \GFExport::add_default_export_fields( $form );
				}
			}

			if ( empty( $this->forms ) ) {
				return array();
			}


			// Get Entries for each form
			foreach ( $forms as $form ) {
				$fields = \GFAPI::get_fields_by_type( $form, 'email' );
				// Get input keys
				foreach ( $fields as $field ) {
					if ( ! empty( $field->inputs ) ) {
						$ids = wp_list_pluck( $field->inputs, 'id' );
					}
				}

				$field_filters = array();
				foreach ( $ids as $id ) {
					$field_filters[] = array(
						'key'      => $id,
						'operator' => \GF_Query_Condition::CONTAINS,
						'value'    => $email,
					);
				}
				$field_filters['mode'] = 'any';

				// Get entries
				$entries_for_forms = \GFAPI::get_entries( $form['id'], array(
					'field_filters' => $field_filters,
				) );
				$entries           = array_merge( $entries, $entries_for_forms );
			}

			return $entries;
		}

		/**
		 * GDPR Exporter for Gravity Forms.
		 *
		 * @param string $email_address Email address.
		 * @param int    $page          Page number to prevent time outs.
		 *
		 * @return array Array of data to export.
		 */
		public function gdpr_exporter( $email_address, $page = 1 ) {
			$entries = $this->get_entries_by_email( $email_address );
			$this->setup_forminputs();

			$labels = $this->get_labels();

			$export_items = array();
			foreach ( $entries as $entry ) {
				$item = array(
					'group_id'    => 'gravityforms',
					'group_label' => __( 'Gravity Forms', 'wps' ),
					'item_id'     => 'gravityforms-entries-' . $entry['id'],
					'data'        => array(),
				);
				foreach ( $entry as $key => $value ) {
					if ( '' === $value || null === $value || in_array( $key, $this->get_excluded(), true ) ) {
						continue;
					}

					$name = $key;
					if ( isset( $this->forminputs[ $entry['form_id'] ][ $key ] ) ) {
						$name = $this->forminputs[ $entry['form_id'] ][ $key ];
					} elseif ( isset( $labels[ $key ] ) ) {
						$name = $labels[ $key ];
					}

					$item_data_name  = apply_filters( 'wps_gravityforms_export_personal_data_name', $name, $key, $this, $entry );
					$item_data_value = apply_filters( 'wps_gravityforms_export_personal_data_value', $value, $key, $this, $entry );
					$item_data       = array(
						'name'  => $item_data_name,
						'value' => $item_data_value,
					);

					$item['data'][] = apply_filters( 'wps_gravityforms_export_personal_data', $item_data, $key, $this, $entry );
				}

				$export_items[] = $item;
			}

			return array(
				'data' => $export_items,
				'done' => true,
			);
		}

		/**
		 * Registers the new GDPR exporter.
		 *
		 * @param array[string[mixed]] $exporters An array of callable exporters of personal data. Default empty array.
		 *             Array of a exporter_friendly_name (string, Translated user facing friendly name for the
		 *             exporter) and callback (Callable exporter function that accepts an email address and a page
		 *             and returns an array of name => value pairs of personal data.)
		 *
		 * @return mixed
		 */
		function register_gdpr_exporter( $exporters ) {
			$exporters['wps-gravityforms'] = array(
				'exporter_friendly_name' => __( 'Gravity Forms', 'wps' ),
				'callback'               => array( $this, 'gdpr_exporter' ),
			);

			return $exporters;
		}
	}
}
