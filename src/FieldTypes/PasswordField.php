<?php
/**
 * Sanitization and schema for the password field type.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\FieldTypes;

/**
 * Password field type — stores values as-is without sanitization stripping.
 *
 * @since 1.0.0
 */
final class PasswordField implements FieldTypeInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_type(): string {
		return 'password';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The raw value.
	 * @param array<string, mixed> $field The field definition.
	 */
	public function sanitize( $value, array $field ): string {
		return is_string( $value ) ? $value : ( is_scalar( $value ) ? (string) $value : '' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field The field definition.
	 */
	public function get_schema( array $field ): array {
		return array( 'type' => 'string' );
	}
}
