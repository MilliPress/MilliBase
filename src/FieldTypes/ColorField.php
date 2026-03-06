<?php
/**
 * Sanitization and schema for the color field type.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\FieldTypes;

/**
 * Color field type — validates hex color strings (#RGB, #RRGGBB, #RRGGBBAA).
 *
 * @since 1.0.0
 */
final class ColorField implements FieldTypeInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_type(): string {
		return 'color';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Uses `sanitize_hex_color()` when available, otherwise falls back
	 * to a regex check.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $value The raw value.
	 * @param array<string, mixed> $field The field definition.
	 */
	public function sanitize( $value, array $field ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		if ( function_exists( 'sanitize_hex_color' ) ) {
			return sanitize_hex_color( $value ) ?? '';
		}

		return preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ? $value : '';
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
