<?php

namespace MilliSettings\FieldTypes;

final class NumberField implements FieldTypeInterface {

	public function get_type(): string {
		return 'number';
	}

	public function sanitize( $value, array $field ) {
		$value = is_numeric( $value ) ? $value + 0 : 0;

		if ( isset( $field['min'] ) && $value < $field['min'] ) {
			$value = $field['min'];
		}
		if ( isset( $field['max'] ) && $value > $field['max'] ) {
			$value = $field['max'];
		}

		return $value;
	}

	public function get_schema( array $field ): array {
		$schema = array( 'type' => 'number' );

		if ( isset( $field['min'] ) ) {
			$schema['minimum'] = $field['min'];
		}
		if ( isset( $field['max'] ) ) {
			$schema['maximum'] = $field['max'];
		}

		return $schema;
	}
}
