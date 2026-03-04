<?php
/**
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\FieldTypes;

/**
 * Select field type — validates against a whitelist of allowed option values.
 *
 * @since 1.0.0
 */
final class SelectField implements FieldTypeInterface {

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
	 */
	public function sanitize( $value, array $field ) {
		/** @var array<int, array<string, mixed>> $options */
		$options      = $field['options'] ?? array();
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
	 */
	public function get_schema( array $field ): array {
		/** @var array<int, array<string, mixed>> $options */
		$options = $field['options'] ?? array();
		$enum    = array_column( $options, 'value' );

		return array(
			'type' => 'string',
			'enum' => $enum,
		);
	}
}
