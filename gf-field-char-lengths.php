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
			'max_chars'              => -1, // negative one for unlimited
			'validation_message'     => false,
			'min_validation_message' => __( 'Please enter at least %d characters.' ),
			'max_validation_message' => __( 'You may only enter %d characters.' ),
		];
	}

	/**
	 * Sanitize the args, which runs after wp_parse_args().
	 */
	public function sanitize_args() {
		// Form ID must be an integer
		$this->args['form_id'] = absint( $this->args['form_id'] );

		// Field ID must be numeric, such as 7.2 for address field
		$this->args['field_id'] = (float) $this->args['field_id'];

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
			|| -1 > $this->args['max_chars']
		) {
			return false;
		}

		// Pointless validation (minimum and maximum both zero)
		if (
			0 === $this->args['min_chars']
			&& 0 === $this->args['max_chars']
		){
			return false;
		}

		// Pointless validation (no minimum and unlimited maximum)
		if (
			0 === $this->args['min_chars']
			&& -1 === $this->args['max_chars']
		){
			return false;
		}

		// Invalid validation (minimum greater than maximum)
		if (
			-1 !== $this->args['max_chars']
			&& $this->args['min_chars'] > $this->args['max_chars']
		){
			return false;
		}

		return true;
	}

	/**
	 * Sanitize data and then only add the Gravity Forms validation hook if data is valid.
	 */
	public function add_validation_hook() {
		// prevent errors from improper use
		$this->sanitize_args();

		// only add hook if data makes sense
		if ( true === $this->args_are_valid() ) {
			add_filter( "gform_field_validation_{$this->args['form_id']}_{$this->args['field_id']}", [ $this, 'validate_character_count' ], 10, 4 );
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
		$char_count      = strlen( $value );
		$is_min_reached  = $this->args['min_chars'] !== false && $char_count >= $this->args['min_chars'];
		$is_max_exceeded = $this->args['max_chars'] !== false && $char_count > $this->args['max_chars'];

		if ( ! $is_min_reached ) {
			$message = $this->args['validation_message'];
			if ( ! $message ) {
				$message = $this->args['min_validation_message'];
			}

			$result['is_valid'] = false;
			$result['message']  = sprintf( $message, $this->args['min_chars'] );
		} else if ( $is_max_exceeded ) {
			$message = $this->args['max_validation_message'];
			if ( ! $message ) {
				$message = $this->args['validation_message'];
			}

			$result['is_valid'] = false;
			$result['message']  = sprintf( $message, $this->args['max_chars'] );
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
		'min_validation_message' => __( 'Oops! You need to enter at least %d characters.' ),
		'max_validation_message' => __( 'Oops! You can only enter %d characters.' )
	]
);