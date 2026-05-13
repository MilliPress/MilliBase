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
}
