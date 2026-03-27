<?php
/**
 * Sanitization and schema for the select field type.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\Fields;

/**
 * Select field type — validates against a whitelist of allowed option values.
 *
 * @since 1.0.0
 */
final class Select implements FieldInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_type(): string {
		return 'select';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Falls back to the field's default value when the submitted value
	 * is not in the allowed options list.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The raw value.
	 * @param array<string, mixed> $field The field definition.
	 */
	public function sanitize( $value, array $field ) {
		$options      = is_array( $field['options'] ?? null ) ? $field['options'] : array();
		$valid_values = array_column( $options, 'value' );

		if ( in_array( $value, $valid_values, true ) ) {
			return $value;
		}

		return $field['default'] ?? '';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Includes an `enum` constraint listing all valid option values.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field The field definition.
	 */
	public function get_schema( array $field ): array {
		$options = is_array( $field['options'] ?? null ) ? $field['options'] : array();
		$enum    = array_column( $options, 'value' );

		return array(
			'type' => 'string',
			'enum' => $enum,
		);
	}
}
