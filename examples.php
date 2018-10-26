<?php

/**
 * Usage examples that require https://github.com/cliffordp/gf-gw-req-char-length
 */

if ( class_exists( 'GF_GW_Req_Char_Length' ) ) {
	/**
	 * Example Usage: Field 1 from Form 524 must be 4-5 characters long.
	 */
	new GF_GW_Req_Char_Length(
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
	 * Example Usage: Field 7 from Form 322 is an Address field and therefore Field ID 7.1 is the Address Line 1, and it
	 * must be 5-30 characters long.
	 */
	new GF_GW_Req_Char_Length(
		[
			'form_id'                => 322,
			'field_id'               => 7.1,
			'min_chars'              => 5,
			'max_chars'              => 30,
			'min_validation_message' => esc_html__( 'Oops! Address Line 1 must be at least %d characters.' ),
			'max_validation_message' => esc_html__( 'Oops! Address Line 1 must be %d or fewer characters.' )
		]
	);

	/**
	 * Example Usage: Field 1 from Form 746 is a Name field and therefore Field ID 1.3 is the First Name and 1.6 is the
	 * Last Name and both have the same validation of 2-40 characters long. Use the default validation message text.
	 */
	new GF_GW_Req_Char_Length(
		[
			'form_id'   => 746,
			'field_id'  => [ 1.3, 1.6 ],
			'min_chars' => 2,
			'max_chars' => 40,
		]
	);
} // end of class_exists()