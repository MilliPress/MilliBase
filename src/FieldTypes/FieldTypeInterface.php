<?php
/**
 * Interface for field type sanitization and schema generation.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\FieldTypes;

/**
 * Each field type provides sanitization logic and a JSON schema fragment.
 *
 * @since 1.0.0
 */
interface FieldTypeInterface {

	/**
	 * Get the field type identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Sanitize a value for this field type.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The raw value.
	 * @param array<string, mixed> $field The field definition.
	 *
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, array $field );

	/**
	 * Get the JSON schema fragment for this field type.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field The field definition.
	 *
	 * @return array<string, mixed>
	 */
	public function get_schema( array $field ): array;
}
