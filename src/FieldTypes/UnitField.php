<?php
/**
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\FieldTypes;

/**
 * Unit field type — stores a numeric value paired with a CSS unit selector.
 *
 * @since 1.0.0
 */
final class UnitField implements FieldTypeInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_type(): string {
		return 'unit';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function sanitize( $value, array $field ) {
		return is_numeric( $value ) ? $value + 0 : 0;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function get_schema( array $field ): array {
		return array( 'type' => 'number' );
	}
}
