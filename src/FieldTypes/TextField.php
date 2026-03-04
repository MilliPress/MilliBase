<?php
/**
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\FieldTypes;

/**
 * Text field type — sanitizes values through `sanitize_text_field()`.
 *
 * @since 1.0.0
 */
final class TextField implements FieldTypeInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_type(): string {
		return 'text';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function sanitize( $value, array $field ): string {
		return is_string( $value ) ? sanitize_text_field( $value ) : ( is_scalar( $value ) ? (string) $value : '' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_schema( array $field ): array {
		return array( 'type' => 'string' );
	}
}
