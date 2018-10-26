<?php

if ( ! class_exists( 'GF_GW_Req_Char_Length' ) ) {

	/**
	 * Require a minimum and/or maximum character length for specific Gravity Forms fields, displaying an informative
	 * Gravity Form validation error to the user for disallowed entries.
	 *
	 * Adds support for requiring a minimum and/or maximum number of characters for text type fields (e.g. Single Line Text,
	 * Email) and array type fields (e.g. Name, Address).
	 * See $this->set_defaults() for allowed/required input values. Optionally add your own text domain to messages.
	 * See Changelog for required Gravity Forms and PHP versions.
	 *
	 * @version 2.0.0
	 * @author  TourKick LLC (Clifford Paulick)
	 * @license GPL version 3 or any later version
	 * @link    https://github.com/cliffordp/gf-gw-req-char-length This new/forked version of this class, by Clifford.
	 * @link    https://gist.github.com/cliffordp/551eb4f67b8db8e19d3d59a0f2b7a6f9 See this for multiple code usage examples.
	 * @link    https://gravitywiz.com/require-minimum-character-limit-gravity-forms/ The accompanying Gravity Wiz article.
	 */
	class GF_GW_Req_Char_Length {
		/**
		 * Changelog:
		 *
		 * Version 2.0.0: October 26, 2018
		 * - Pretty much fully rewritten from https://gist.github.com/spivurno/8220561 (considered as Version 1.0.0 from
		 *   May 30, 2014)
		 * - Now requires Gravity Forms version 2.3+ (an arbitrarily-chosen recent version where GFForms::$version is
		 *   used instead of the now-deprecated GFCommon::$version). Current GF version at time of this release is 2.3.6.
		 * - Now requires PHP 5.4+ (uses array short syntax).
		 * - Changed license from GPLv2+ to GPLv3+.
		 * - Renamed class to better describe actual functionality, as it can be used for minimum and/or maximum.
		 * - 'field_id' argument now supports array type fields, such as Name and Address fields.
		 *   (e.g. require Address Line 1 to be 5-30 characters long)
		 * - 'field_id' argument now supports passing an array to apply the same rules and messaging to multiple fields
		 *   at once. (e.g. same length and messaging to First Name and Last Name)
		 * - Added composer.php
		 * - Added multiple new examples to demonstrate available functionality, but removed them from this file in case
		 *   this class is added to your project via Composer.
		 */

		/**
		 * The array of default values, set at runtime.
		 *
		 * @var array
		 */
		private $defaults = [];

		/**
		 * The array of parsed values, set at runtime from passed args and defaults.
		 *
		 * @var array
		 */
		private $args = [];

		/**
		 * GF_GW_Req_Char_Length constructor.
		 *
		 * @param array $args
		 */
		public function __construct( $args = [] ) {
			// make sure we're running the required minimum version of Gravity Forms
			if (
				! property_exists( 'GFForms', 'version' )
				|| ! version_compare( GFForms::$version, '2.3', '>=' )
			) {
				return;
			}

			// set our default values
			$this->set_defaults();

			// parse passed and default values and store for use throughout the class
			$this->args = wp_parse_args( $args, $this->defaults );

			$this->add_validation_hooks();
		}

		/**
		 * Set the default values. To be ran before wp_parse_args().
		 */
		public function set_defaults() {
			$this->defaults = [
				'form_id'                => 0, // integer
				'field_id'               => '', // numeric or array of numerics
				'min_chars'              => 0, // absint()
				'max_chars'              => - 1, // absint() or -1 for unlimited
				'min_validation_message' => esc_html__( 'Please enter at least %d characters.' ), // string, optionally add your own text domain
				'max_validation_message' => esc_html__( 'You may only enter %d characters.' ), // string, optionally add your own text domain
			];
		}

		/**
		 * Sanitize the args. To be ran after wp_parse_args().
		 *
		 * Sets $this->args['field_ids']
		 */
		public function sanitize_args() {
			// Form ID must be an integer
			$this->args['form_id'] = absint( $this->args['form_id'] );

			// Field ID may be a string or array (multiple Field IDs getting the same min, max, and messaging)
			$this->args['field_id'] = (array) $this->args['field_id'];
			$this->args['field_id'] = array_filter( $this->args['field_id'] );
			$this->args['field_id'] = array_unique( $this->args['field_id'] );

			// Set $this->args['field_ids'] and $this->args['field_id_ints']
			$this->args['field_ids']     = [];
			$this->args['field_id_ints'] = [];

			foreach ( $this->args['field_id'] as $field_id ) {
				// Field ID must be numeric, such as 7.2 for "Address Line 2" from Field #7.
				$field_id = (float) $field_id;

				$this->args['field_ids'][] = $field_id;

				// Set the integer value of the Field ID, used by the Gravity Forms hook
				$this->args['field_id_ints'][] = $this->get_int_before_after_period( $field_id );
			}

			$this->args['field_id_ints'] = array_filter( $this->args['field_id_ints'] );
			$this->args['field_id_ints'] = array_unique( $this->args['field_id_ints'] );

			// Min and Max must be integers
			$this->args['min_chars'] = absint( $this->args['min_chars'] );
			// But Max could be -1 for unlimited
			$this->args['max_chars'] = (int) $this->args['max_chars'];
		}

		/**
		 * Check if the args are okay to proceed.
		 *
		 * @return bool
		 */
		public function args_are_valid() {
			// Major fails
			if (
				empty( $this->args['form_id'] )
				|| - 1 > $this->args['max_chars']
			) {
				return false;
			}

			// Setting Field IDs via sanitization didn't go as expected
			if (
				empty( $this->args['field_id_ints'] )
				|| empty( $this->args['field_ids'] )
			) {
				return false;
			}

			// Pointless validation (maximum length of zero -- use negative one to allow unlimited)
			if ( 0 === $this->args['max_chars'] ) {
				return false;
			}

			// Pointless validation (no minimum and unlimited maximum)
			if (
				0 === $this->args['min_chars']
				&& - 1 === $this->args['max_chars']
			) {
				return false;
			}

			// Invalid validation (minimum greater than maximum)
			if (
				- 1 !== $this->args['max_chars']
				&& $this->args['min_chars'] > $this->args['max_chars']
			) {
				return false;
			}

			return true;
		}

		/**
		 * Get the integer value of the "before the period" or the "after the period" of a number.
		 *
		 * @param int|float|string $number
		 * @param bool             $before
		 *
		 * @return bool|int
		 */
		private function get_int_before_after_period( $number, $before = true ) {
			if ( ! is_numeric( $number ) ) {
				return false;
			}

			$periods_count = substr_count( $number, '.' );

			// Invalid if more than 1 period
			if ( 1 < $periods_count ) {
				return false;
			}

			$number = (string) $number;

			// if an integer, add ".0" to the end
			if ( 0 === $periods_count ) {
				$number = $number . '.0';
			}

			// if starts with a period, add leading zero
			if ( 0 === strpos( $number, '.' ) ) {
				$number = '0' . $number;
			}

			$array = explode( '.', $number );

			if ( $before ) {
				return (int) $array[0];
			} else {
				return (int) $array[1];
			}
		}

		/**
		 * Sanitize data and then only add the Gravity Forms validation hook if data is valid.
		 */
		public function add_validation_hooks() {
			// prevent errors from improper use
			$this->sanitize_args();

			// only add hook if data makes sense
			if ( false === $this->args_are_valid() ) {
				return;
			}

			foreach ( $this->args['field_id_ints'] as $field_id_int ) {
				add_filter( "gform_field_validation_{$this->args['form_id']}_{$field_id_int}", [ $this, 'validate_character_count' ], 10, 4 );
			}
		}

		/**
		 * Logic used by the Gravity Forms validation hook.
		 *
		 * @param $result
		 * @param $value
		 * @param $form
		 * @param $field
		 *
		 * @return array
		 */
		public function validate_character_count( $result, $value, $form, $field ) {
			foreach ( $this->args['field_ids'] as $field_id ) {
				if ( ! empty( $result['is_valid'] ) ) {
					break;
				}

				$field_id_before_period = $this->get_int_before_after_period( $field_id );

				// if looking for fields 7.2, 7.3, and 8.2, and we're processing Field ID 8, then skip 7.2 and 7.3
				if (
					empty( $field_id_before_period )
					|| $field_id_before_period !== absint( $field['id'] )
				) {
					continue;
				}

				if ( is_array( $value ) ) {
					if ( ! empty( $value[$field_id] ) ) {
						$our_val = $value[$field_id];
					} else {
						// Field ID not found within the array, so bail
						return $value;
					}
				} else {
					$our_val = $value;
				}

				// bail if unexpected value, such as boolean, array, object, or resource
				// similar to https://secure.php.net/manual/function.is-scalar.php but we don't want booleans
				if (
					! is_string( $our_val )
					&& ! is_float( $our_val )
					&& ! is_int( $our_val )
				) {
					return $value;
				}

				// do our custom validation
				$char_count = strlen( $our_val );

				if ( $char_count >= $this->args['min_chars'] ) {
					$is_min_reached = true;
				} else {
					$is_min_reached = false;
				}

				if ( - 1 === $this->args['max_chars'] ) {
					$is_max_exceeded = false;
				} else {
					if ( $char_count > $this->args['max_chars'] ) {
						$is_max_exceeded = true;
					} else {
						$is_max_exceeded = false;
					}
				}

				if ( ! empty( $is_min_reached ) ) {
					$result['is_valid'] = false;
					$result['message']  = sprintf( $this->args['min_validation_message'], $this->args['min_chars'] );
				} else if ( $is_max_exceeded ) {
					$result['is_valid'] = false;
					$result['message']  = sprintf( $this->args['max_validation_message'], $this->args['max_chars'] );
				}
			}

			return $result;
		}
	} // end of class
} // end of class_exists()