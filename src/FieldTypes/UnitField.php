<?php

namespace MilliSettings\FieldTypes;

final class UnitField implements FieldTypeInterface {

	public function get_type(): string {
		return 'unit';
	}

	public function sanitize( $value, array $field ) {
		return is_numeric( $value ) ? $value + 0 : 0;
	}

	public function get_schema( array $field ): array {
		return array( 'type' => 'number' );
	}
}
