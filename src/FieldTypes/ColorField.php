<?php

namespace MilliSettings\FieldTypes;

final class ColorField implements FieldTypeInterface {

	public function get_type(): string {
		return 'color';
	}

	public function sanitize( $value, array $field ) {
		if ( function_exists( 'sanitize_hex_color' ) ) {
			return sanitize_hex_color( $value ) ?? '';
		}

		return is_string( $value ) && preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ? $value : '';
	}

	public function get_schema( array $field ): array {
		return array( 'type' => 'string' );
	}
}
