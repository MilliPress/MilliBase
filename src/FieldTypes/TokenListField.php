<?php

namespace MilliSettings\FieldTypes;

final class TokenListField implements FieldTypeInterface {

	public function get_type(): string {
		return 'token-list';
	}

	public function sanitize( $value, array $field ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'sanitize_text_field', $value ) ) );
	}

	public function get_schema( array $field ): array {
		return array(
			'type'  => 'array',
			'items' => array( 'type' => 'string' ),
		);
	}
}
