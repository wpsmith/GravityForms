<?php


namespace WPS\WP\Plugins\GravityForms;


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\DisableSubmit' ) ) {
	/**
	 * Gravity Wiz // Gravity Forms // Disable Submit Button Until Required Fields are Field Out
	 *
	 * Disable submit buttones until all required fields have been filled out. Currently only supports single-page forms.
	 *
	 * @version     1.0
	 * @author      David Smith <david@gravitywiz.com>
	 * @license     GPL-2.0+
	 * @link        http://gravitywiz.com/...
	 * @copyright   2013 Gravity Wiz
	 */
	class DisableSubmit {

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
			add_action( "gform_register_init_scripts_{$form_id}", array( $this, 'add_init_script' ) );

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

                var GWDisableSubmit = GWDisableSubmit || {};
                (function ($) {
                    GWDisableSubmit = function (args) {
                        var self = this;
                        self._args = args;
                        self.$inputs = [];

                        // copy all args to current object: formId, fieldId
                        for (prop in args) {
                            if (args.hasOwnProperty(prop))
                                self[prop] = args[prop];
                        }

                        self.lastPage = function () {
                            return $('.gform_page').length || 1;
                        };

                        self.getPage = function () {
                            return parseInt($('input[name="gform_source_page_number_' + args.formId + '"]').val(), 10);
                        };

                        self.init = function () {
                            $(args.inputHtmlIds[self.getPage()].join(', ')).change(function () {
                                self.runCheck();
                            });

                            self.runCheck();
                        };

                        self.runCheck = function () {
                            var submitButton;
                            if (self.getPage() === self.lastPage()) {
                                submitButton = $('#gform_submit_button_' + self.formId);
                            } else {
                                submitButton = $('#gform_page_' + self.formId + '_' + self.getPage() + ' input.gform_next_button');
                            }

                            if (self.areRequiredPopulated()) {
                                submitButton.attr('disabled', false).removeClass('gwds-disabled');
                            } else {
                                submitButton.attr('disabled', true).addClass('gwds-disabled');
                            }
                        };

                        self.getInputCount = function ($inputs) {
                            var inputCount = args.inputCounts[self.getPage()];

                            if ($inputs.length < inputCount) {
                                var $choices = $($inputs).filter('input[id^="choice"]'),
                                    choices = [];

                                $($choices).each(function () {
                                    var id = self.getFieldIdFromInput($(this).prop('id'));
                                    if (!choices.includes(id)) {
                                        choices.push(id);
                                    }
                                });
                                if (choices.length > 0) {
                                    return $inputs.length - $choices.length + choices.length;
								}

                                return $inputs.length;
                            }

                            return inputCount;
                        };

                        self.getFieldIdFromInput = function (id) {
                            return id.split('_')[2];
                        };

                        self.areRequiredPopulated = function () {
                            var $inputs = $(args.inputHtmlIds[self.getPage()].join(', ')),
                                inputCount = self.getInputCount($inputs),
                                fullCount = 0;

                            self.$inputs = $inputs;
                            $($inputs).each(function () {
                                var input = $(this),
                                    fieldId = input.attr('id').split('_')[2];

                                // don't count fields hidden via conditional logic towards the inputCount
                                if (window['gf_check_field_rule'] && 'hide' === gf_check_field_rule(self.formId, fieldId, null, null)) {
                                    inputCount -= 1;
                                    return;
                                }

                                if ('radio' === $(this).prop('type') || 'checkbox' === $(this).prop('type')) {
                                    if ($(this).is(":checked")) {
                                        fullCount += 1;
                                    }
                                } else if ($.trim($(this).val())) {
                                    fullCount += 1;
                                } else if ($(this).hasClass('gfield_checkbox')) {
                                    $($(this).find('input')).each(function () {
                                        if ($(this).is(":checked")) {
                                            fullCount += 1;
                                            return false;
                                        }
                                    });
                                } else {
                                    return false;
                                }

                            });

                            return fullCount === inputCount;
                        };

                        self.init();
                    }
                })(jQuery);

			</script>

			<?php
		}

		/**
		 * Adds the inline script.
		 *
		 * @param array $form Gravity Forms form object.
		 */
		public function add_init_script( $form ) {

			$inputCount = array();
			$inputs     = $this->get_required_input_html_ids( $form );
			foreach ( $inputs as $page_number => $_inputs ) {
				$inputCount[ $page_number ] = count( $_inputs );
			}
			$args = array(
				'formId'       => $form['id'],
				'inputHtmlIds' => $inputs,
				'inputCounts'  => $inputCount,
			);

			$script = '; (function($){
				$(document).ready(function() {
					new GWDisableSubmit( ' . json_encode( $args ) . ' );
				}); 
			})(jQuery);';
			$slug   = "gw_disable_submit_{$form['id']}";

			\GFFormDisplay::add_init_script( $form['id'], $slug, \GFFormDisplay::ON_PAGE_RENDER, $script );

		}

		/**
		 * Gets the required input HTML IDs.
		 *
		 * @param array $form Gravity Forms form object.
		 *
		 * @return array|string
		 */
		public function get_required_input_html_ids( $form ) {

			$html_ids = array();

			foreach ( $form['fields'] as &$field ) {

				$field_html_ids = array();
				if ( ! $field['isRequired'] ) {
					continue;
				}

				$input_ids = false;
				switch ( \GFFormsModel::get_input_type( $field ) ) {
					case 'pricing':
						$input_ids = array( 2, 3 );
						break;

					case 'creditcard':
						$input_ids = array( 1, '2_month', '2_year', 3, 5 );
						break;
					case 'address':
						$input_ids = array( 1, 3, 4, 5, 6 );
						break;

					case 'name':
						$input_ids = array( 1, 2, 3, 4, 5, 6 );
						break;

					case 'radio':
						$field_html_ids[] = "input[name=\"input_{$field->id}\"]";
						break;

					default:
						$field_html_ids[] = "#input_{$form['id']}_{$field['id']}";
						break;
				}

				if ( $input_ids ) {
					foreach ( $input_ids as $input_id ) {
						$field_html_ids[] = "#input_{$form['id']}_{$field['id']}_{$input_id}";
					}
				}

				if ( ! isset( $html_ids[ $field->pageNumber ] ) ) {
					$html_ids[ $field->pageNumber ] = array();
				}

				$html_ids[ $field->pageNumber ] = array_merge( $html_ids[ $field->pageNumber ], $field_html_ids );
			}

			return $html_ids;
		}

	}
}
