<?php

namespace MilliSettings\FieldTypes;

final class SelectField implements FieldTypeInterface {

	public function get_type(): string {
		return 'select';
	}

	public function sanitize( $value, array $field ) {
		$valid_values = array_column( $field['options'] ?? array(), 'value' );

		if ( in_array( $value, $valid_values, true ) ) {
			return $value;
		}

		return $field['default'] ?? '';
	}

	public function get_schema( array $field ): array {
		$enum = array_column( $field['options'] ?? array(), 'value' );

		return array(
			'type' => 'string',
			'enum' => $enum,
		);
	}
}
