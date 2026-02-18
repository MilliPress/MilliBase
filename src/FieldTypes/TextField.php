<?php

namespace MilliSettings\FieldTypes;

final class TextField implements FieldTypeInterface {

	public function get_type(): string {
		return 'text';
	}

	public function sanitize( $value, array $field ) {
		return is_string( $value ) ? sanitize_text_field( $value ) : (string) $value;
	}

	public function get_schema( array $field ): array {
		return array( 'type' => 'string' );
	}
}
