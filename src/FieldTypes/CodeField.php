<?php

namespace MilliSettings\FieldTypes;

final class CodeField implements FieldTypeInterface {

	public function get_type(): string {
		return 'code';
	}

	public function sanitize( $value, array $field ) {
		return is_string( $value ) ? $value : (string) $value;
	}

	public function get_schema( array $field ): array {
		return array( 'type' => 'string' );
	}
}
