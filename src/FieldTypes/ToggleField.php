<?php
/**
 * Sanitization and schema for the toggle (boolean) field type.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\FieldTypes;

/**
 * Toggle (boolean) field type.
 *
 * @since 1.0.0
 */
final class ToggleField implements FieldTypeInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_type(): string {
		return 'toggle';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The raw value.
	 * @param array<string, mixed> $field The field definition.
	 */
	public function sanitize( $value, array $field ) {
		return (bool) $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field The field definition.
	 */
	public function get_schema( array $field ): array {
		return array( 'type' => 'boolean' );
	}
}
