<?php
/**
 * Sanitization and schema for the number field type.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\Fields;

/**
 * Number field type — clamps values to optional min/max bounds.
 *
 * @since 1.0.0
 */
final class Number implements FieldInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_type(): string {
		return 'number';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Clamps the value to the `min` and `max` bounds defined in the
	 * field definition, if present.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The raw value.
	 * @param array<string, mixed> $field The field definition.
	 */
	public function sanitize( $value, array $field ) {
		$value = is_numeric( $value ) ? $value + 0 : 0;

		$min = isset( $field['min'] ) && is_numeric( $field['min'] ) ? $field['min'] + 0 : null;
		$max = isset( $field['max'] ) && is_numeric( $field['max'] ) ? $field['max'] + 0 : null;

		if ( null !== $min && $value < $min ) {
			$value = $min;
		}
		if ( null !== $max && $value > $max ) {
			$value = $max;
		}

		return $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Includes `minimum` and `maximum` constraints when defined.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field The field definition.
	 */
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
