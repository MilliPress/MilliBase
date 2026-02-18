<?php
/**
 * Interface for field type sanitization and schema generation.
 *
 * @package MilliSettings
 */

namespace MilliSettings\FieldTypes;

/**
 * Each field type provides sanitization logic and a JSON schema fragment.
 */
interface FieldTypeInterface {

	/**
	 * Get the field type identifier.
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Sanitize a value for this field type.
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
	 * @param array<string, mixed> $field The field definition.
	 *
	 * @return array<string, mixed>
	 */
	public function get_schema( array $field ): array;
}
