<?php

namespace MilliSettings\FieldTypes;

final class PasswordField implements FieldTypeInterface {

	public function get_type(): string {
		return 'password';
	}

	public function sanitize( $value, array $field ) {
		return is_string( $value ) ? $value : (string) $value;
	}

	public function get_schema( array $field ): array {
		return array( 'type' => 'string' );
	}
}
