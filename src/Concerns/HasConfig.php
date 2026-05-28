<?php
/**
 * Shared config-array accessors for classes constructed with a plugin config.
 *
 * @package MilliBase
 * @author  Philipp Wellmer & Vedran <hello@millipress.com>
 */

namespace MilliBase\Concerns;

/**
 * Shared `$config` accessors. Consuming classes own the `private array $config` storage.
 *
 * @since 2.5.0
 */
trait HasConfig {

	/**
	 * Get a string value from the config array.
	 *
	 * Honors empty / `'0'` config values as-is — the caller decides whether
	 * those have meaning (e.g. `menu_parent => ''` documents "top-level
	 * menu"). Callers that need a guaranteed non-falsy result for functions
	 * like `register_rest_route` should use {@see self::config_non_falsy_string()}.
	 *
	 * @since 2.5.0
	 *
	 * @param string $key      The config key.
	 * @param string $fallback The fallback value.
	 * @return string
	 */
	private function config_string( string $key, string $fallback = '' ): string {
		$value = $this->config[ $key ] ?? $fallback;
		return is_string( $value ) ? $value : $fallback;
	}

	/**
	 * Get a non-falsy string value from the config array.
	 *
	 * Like {@see self::config_string()} but coerces empty and `'0'` config
	 * values to the fallback. With a non-falsy fallback the return type
	 * narrows to `non-falsy-string`, so the result can pass straight into
	 * WP functions that require non-falsy strings (`register_rest_route`,
	 * `wp_register_ability`) without an extra runtime check at the call site.
	 *
	 * @since 2.6.0
	 *
	 * @param string $key      The config key.
	 * @param string $fallback The fallback value.
	 * @return string
	 *
	 * @phpstan-return ($fallback is non-falsy-string ? non-falsy-string : string)
	 */
	private function config_non_falsy_string( string $key, string $fallback = '' ): string {
		$value = $this->config[ $key ] ?? null;
		if ( is_string( $value ) && '' !== $value && '0' !== $value ) {
			return $value;
		}
		return $fallback;
	}
}
