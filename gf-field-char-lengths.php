<?php

/**
 * Gravity Wiz // Require Minimum Character Limit for Gravity Forms
 *
 * Adds support for requiring a minimum number of characters for text-based and
 * address Gravity Forms fields.
 *
 * @version   1.1.0
 * @author    David Smith <david@gravitywiz.com>
 * @license   GPL version 3 or any later version
 * @link      https://gravitywiz.com/require-minimum-character-limit-gravity-forms/
 * @copyright 2013 Gravity Wiz
 */
class GW_Minimum_Characters {

	private $defaults = [];

	private $args = [];

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

		$this->add_validation_hook();
	}

	/**
	 * Set the default values, which must run within __construct() before wp_parse_args().
	 */
	public function set_defaults() {
		$this->defaults = [
			'form_id'                => 0,
			'field_id'               => 0,
			'min_chars'              => 0,
			'max_chars'              => - 1, // negative one for unlimited
			'validation_message'     => false,
			'min_validation_message' => __( 'Please enter at least %d characters.' ),
			'max_validation_message' => __( 'You may only enter %d characters.' ),
		];
	}

	/**
	 * Sanitize data and then only add the Gravity Forms validation hook if data is valid.
	 */
	public function add_validation_hook() {
		// prevent errors from improper use
		$this->sanitize_args();

		// only add hook if data makes sense
		if ( true === $this->args_are_valid() ) {
			add_filter( "gform_field_validation_{$this->args['form_id']}_{$this->args['field_id_int']}", [ $this, 'validate_character_count' ], 10, 4 );
		}
	}

	/**
	 * Sanitize the args, which runs after wp_parse_args().
	 *
	 * Also sets $this->args['field_id_int'], which is required since the Gravity Forms hook requires it.
	 */
	public function sanitize_args() {
		// Form ID must be an integer
		$this->args['form_id'] = absint( $this->args['form_id'] );

		// Field ID must be numeric, such as 7.2 for address field
		$this->args['field_id'] = (float) $this->args['field_id'];

		// Set the integer value of the Field ID, used by the Gravity Forms hook
		$this->args['field_id_int'] = absint( floor( $this->args['field_id'], 0 ) );

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
			|| 0 > $this->args['field_id']
			|| - 1 > $this->args['max_chars']
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
		// only check if valid if comes through as valid already
		if ( empty( $result['is_valid'] ) ) {
			return $result;
		}

		$field_id = $this->args['field_id'];

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
		$char_count      = strlen( $our_val );

		if ( $char_count >= $this->args['min_chars'] ) {
			$is_min_reached = true;
		} else {
			$is_min_reached = false;
		}

		if ( -1 === $this->args['max_chars'] ) {
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

		return $result;
	}

}

/**
 * Example Usage: Field 1 from Form 524 must be 4-5 characters long.
 */
new GW_Minimum_Characters(
	[
		'form_id'                => 524,
		'field_id'               => 1,
		'min_chars'              => 4,
		'max_chars'              => 5,
		'min_validation_message' => esc_html__( 'Oops! You need to enter at least %d characters.' ),
		'max_validation_message' => esc_html__( 'Oops! You can only enter %d characters.' )
	]
);

/**
 * Example Usage: Field 7 from Form 12 is an Address field and therefore Field ID 7.1 is the Address Line 1, and it
 * must be 5-30 characters long.
 */
new GW_Minimum_Characters(
	[
		'form_id'                => 12,
		'field_id'               => 7.1,
		'min_chars'              => 5,
		'max_chars'              => 30,
		'min_validation_message' => esc_html__( 'Oops! Address Line 1 must be at least %d characters.' ),
		'max_validation_message' => esc_html__( 'Oops! Address Line 1 must be %d or fewer characters.' )
	]
);