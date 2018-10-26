<?php

/**
 * Gravity Wiz // Require Minimum Character Limit for Gravity Forms
 *
 * Adds support for requiring a minimum number of characters for text-based and
 * address Gravity Forms fields.
 *
 * @version   1.0.0
 * @author    David Smith <david@gravitywiz.com>
 * @license   GPL version 3 or any later version
 * @link      http://gravitywiz.com/...
 * @copyright 2013 Gravity Wiz
 */
class GW_Minimum_Characters {

	public function __construct( $args = array() ) {

		// make sure we're running the required minimum version of Gravity Forms
		if ( ! property_exists( 'GFCommon', 'version' ) || ! version_compare( GFCommon::$version, '1.7', '>=' ) ) {
			return;
		}

		// set our default arguments, parse against the provided arguments, and store for use throughout the class
		$this->_args = wp_parse_args(
			$args, array(
			'form_id'                => false,
			'field_id'               => false,
			'min_chars'              => 0,
			'max_chars'              => false,
			'validation_message'     => false,
			'min_validation_message' => __( 'Please enter at least %s characters.' ),
			'max_validation_message' => __( 'You may only enter %s characters.' )
		)
		);

		extract( $this->_args );

		if ( ! $form_id || ! $field_id || ! $min_chars ) {
			return;
		}

		// time for hooks
		add_filter( "gform_field_validation_{$form_id}_{$field_id}", array( $this, 'validate_character_count' ), 10, 4 );

	}

	public function validate_character_count( $result, $value, $form, $field ) {

		$char_count      = strlen( $value );
		$is_min_reached  = $this->_args['min_chars'] !== false && $char_count >= $this->_args['min_chars'];
		$is_max_exceeded = $this->_args['max_chars'] !== false && $char_count > $this->_args['max_chars'];

		if ( ! $is_min_reached ) {

			$message = $this->_args['validation_message'];
			if ( ! $message ) {
				$message = $this->_args['min_validation_message'];
			}

			$result['is_valid'] = false;
			$result['message']  = sprintf( $message, $this->_args['min_chars'] );

		} else if ( $is_max_exceeded ) {

			$message = $this->_args['max_validation_message'];
			if ( ! $message ) {
				$message = $this->_args['validation_message'];
			}

			$result['is_valid'] = false;
			$result['message']  = sprintf( $message, $this->_args['max_chars'] );

		}

		return $result;
	}

}

# Configuration

new GW_Minimum_Characters(
	array(
		'form_id'                => 524,
		'field_id'               => 1,
		'min_chars'              => 4,
		'max_chars'              => 5,
		'min_validation_message' => __( 'Oops! You need to enter at least %s characters.' ),
		'max_validation_message' => __( 'Oops! You can only enter %s characters.' )
	)
);