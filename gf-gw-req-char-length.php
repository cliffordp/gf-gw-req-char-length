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
	 * @version 2.0.1
	 * @author  TourKick LLC (Clifford Paulick)
	 * @license GPL version 3 or any later version
	 * @link    https://github.com/cliffordp/gf-gw-req-char-length This class' repository. See its README.md for
	 *                                                             changelog, credits, links, and instructions.
	 */
	class GF_GW_Req_Char_Length {
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
				// Convert to string so array key lookups work as expected (since PHP rounds float keys to integers)
				$field_id = (string) $field_id;

				$field_id_before_period = $this->get_int_before_after_period( $field_id );
				$field_id_after_period  = $this->get_int_before_after_period( $field_id, false );

				// if looking for fields 7.2, 7.3, and 8.2, and we're processing Field ID 8, then skip 7.2 and 7.3
				if (
					empty( $field_id_before_period )
					|| $field_id_before_period !== absint( $field['id'] )
				) {
					continue;
				}

				if ( is_array( $value ) ) {
					if ( isset( $value[$field_id] ) ) {
						$our_val = $value[$field_id];
					} else {
						// Field ID not found within the array, so bail
						return $result;
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
					return $result;
				}

				// do our custom validation
				$char_count = strlen( $our_val );

				if ( $char_count >= $this->args['min_chars'] ) {
					$is_min_reached = true;
				} else {
					$is_min_reached = false;
				}

				// if field is not required and value is empty, do not trigger validation failure
				if (
					empty( $field['isRequired'] )
					&& empty( $char_count )
				) {
					return $result;
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

				if ( false === $is_min_reached ) {
					$result['is_valid'] = false;
					$message            = sprintf( $this->args['min_validation_message'], $this->args['min_chars'] );
				} elseif ( true === $is_max_exceeded ) {
					$result['is_valid'] = false;
					$message            = sprintf( $this->args['max_validation_message'], $this->args['max_chars'] );
				}

				// If an array-type field, prepend with the input's label so user knows *which* input the validation error applies to
				if (
					! empty( $message )
					&& ! empty( $field_id_after_period )
				) {
					$field_label = strip_tags( GFFormsModel::get_label( $field, $field_id, true ) );

					$message = sprintf( '<em>%s:</em> %s', $field_label, $message );
				}

				if ( ! empty( $message ) ) {
					if ( ! empty( $result['message'] ) ) {
						$result['message'] .= '<br>' . $message;
					} else {
						$result['message'] = $message;
					}
				}
			}

			return $result;
		}
	} // end of class
} // end of class_exists()