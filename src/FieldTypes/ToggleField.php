<?php

namespace MilliSettings\FieldTypes;

final class ToggleField implements FieldTypeInterface {

	public function get_type(): string {
		return 'toggle';
	}

	public function sanitize( $value, array $field ) {
		return (bool) $value;
	}

	public function get_schema( array $field ): array {
		return array( 'type' => 'boolean' );
	}
}
