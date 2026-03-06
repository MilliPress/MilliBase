<?php
/**
 * Safe accessor for $_SERVER superglobal values.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Provides sanitized access to $_SERVER variables.
 *
 * Centralizes sanitization so that individual call sites do not need
 * to worry about unslashing or escaping, and PHPCS superglobal rules
 * are satisfied in a single, auditable location.
 *
 * @since 1.0.0
 */
final class ServerVars {

	/**
	 * Get the value of a server variable safely.
	 *
	 * Sanitizes the value by stripping slashes and converting special
	 * characters to HTML entities.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The $_SERVER key to retrieve.
	 *
	 * @return string The sanitized value, or empty string if not set.
	 */
	public static function get( string $key ): string {
		if ( ! isset( $_SERVER[ $key ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitised via stripslashes + htmlspecialchars here.
		return htmlspecialchars( stripslashes( (string) $_SERVER[ $key ] ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Check whether a server variable is set.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The $_SERVER key to check.
	 *
	 * @return bool
	 */
	public static function has( string $key ): bool {
		return isset( $_SERVER[ $key ] );
	}
}
