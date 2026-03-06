<?php
/**
 * Sanitization and schema for the token list field type.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\FieldTypes;

/**
 * Token list field type — stores an array of sanitized string tokens.
 *
 * @since 1.0.0
 */
final class TokenListField implements FieldTypeInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_type(): string {
		return 'token-list';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Sanitizes each token individually and removes empty entries.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The raw value.
	 * @param array<string, mixed> $field The field definition.
	 */
	public function sanitize( $value, array $field ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'sanitize_text_field', $value ) ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $field The field definition.
	 */
	public function get_schema( array $field ): array {
		return array(
			'type'  => 'array',
			'items' => array( 'type' => 'string' ),
		);
	}
}
