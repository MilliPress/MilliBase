<?php
/**
 * Composite Settings that routes operations across multiple Settings.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\Settings;

use MilliBase\Settings;

/**
 * Wraps multiple Settings instances behind a single Settings-compatible
 * surface. Operations route by module — each Setting declares which
 * modules it owns via its defaults, and the Group dispatches reads,
 * writes, and resets to the owning Settings.
 *
 * Used by Manager to merge multiple Settings into a single CliController
 * when two or more Managers share the same `cli.slug`, so a plugin can
 * expose `wp <slug> config get/set/...` as one command tree even when
 * its data is split across per-site and network options.
 *
 * Cross-prefix tolerant — accepts Settings instances from any namespace
 * prefix; the wrapped object only needs to expose the Settings API
 * shape, not the literal `\MilliBase\Settings` class.
 *
 * @since 2.5.0
 */
final class Group {

	/**
	 * Wrapped Settings instances. Primary at index 0.
	 *
	 * @noinspection PhpMissingFieldTypeInspection
	 *
	 * @var array<int, Settings>
	 */
	private array $list;

	/**
	 * Construct the Group around a primary Settings.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @param Settings $primary Primary Settings — handles fallback lookups for
	 *                          keys whose module isn't owned by any wrapped Settings.
	 */
	public function __construct( $primary ) {
		$this->list = array( $primary );
	}

	/**
	 * Append an additional Settings instance to this group.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @param Settings $settings Settings to add.
	 * @return void
	 */
	public function add( $settings ): void {
		$this->list[] = $settings;
	}

	/**
	 * Get a setting value (routed to its owner) or the merged tree.
	 *
	 * @param string|null $key      Dot-notation key, module name, or null for the full merged tree.
	 * @param mixed       $fallback Returned if a routed lookup misses.
	 * @return mixed
	 */
	public function get( ?string $key = null, $fallback = null ) {
		if ( null === $key ) {
			return $this->merge_trees();
		}

		$owner = $this->owner_of( $key ) ?? $this->list[0];
		return $owner->get( $key, $fallback );
	}

	/**
	 * Set a value, routing to the owning Settings by module prefix.
	 *
	 * @param string $key   Dot-notation key.
	 * @param mixed  $value New value.
	 * @return bool True on write; false if no Settings owns the module.
	 */
	public function set( string $key, $value ): bool {
		$owner = $this->owner_of( $key );
		if ( null === $owner ) {
			return false;
		}
		return $owner->set( $key, $value );
	}

	/**
	 * Reset settings to defaults.
	 *
	 * @param string|null $module Module to reset; null resets every wrapped Settings.
	 * @return bool True on success.
	 */
	public function reset( ?string $module = null ): bool {
		if ( null === $module ) {
			$this->for_each( static fn( $s ) => $s->reset() );
			return true;
		}
		$owner = $this->owner_of_module( $module );
		return null !== $owner && $owner->reset( $module );
	}

	/**
	 * Back up current settings.
	 *
	 * @param string|null $module Module-scoped backup (routed); null backs up every wrapped Settings.
	 * @return void
	 */
	public function backup( ?string $module = null ): void {
		if ( null === $module ) {
			$this->for_each( static fn( $s ) => $s->backup() );
			return;
		}
		$owner = $this->owner_of_module( $module );
		if ( null !== $owner ) {
			$owner->backup( $module );
		}
	}

	/**
	 * Whether any wrapped Settings has a backup.
	 *
	 * @return bool
	 */
	public function has_backup(): bool {
		return $this->any_of( static fn( $s ) => $s->has_backup() );
	}

	/**
	 * Restore from backup on every wrapped Settings.
	 *
	 * @return bool True if at least one restored successfully.
	 */
	public function restore_backup(): bool {
		return $this->any_of( static fn( $s ) => $s->restore_backup() );
	}

	/**
	 * Export settings — merged across all wrapped Settings, or routed by module.
	 *
	 * @param string|null $module            Module to export, or null for all.
	 * @param bool        $include_encrypted Whether to include decrypted enc_* values.
	 * @return array<string, mixed>
	 */
	public function export( ?string $module = null, bool $include_encrypted = false ): array {
		if ( null !== $module ) {
			$owner = $this->owner_of_module( $module );
			return null !== $owner ? $owner->export( $module, $include_encrypted ) : array();
		}
		return $this->merge_from_all( static fn( $s ) => $s->export( null, $include_encrypted ) );
	}

	/**
	 * Import a settings payload, bucketing each top-level module to its owner.
	 *
	 * Unknown modules are silently skipped.
	 *
	 * @param array<string, mixed> $settings Module → values map.
	 * @param bool                 $merge    Merge with existing settings (vs replace).
	 * @return bool True if at least one bucket imported successfully.
	 */
	public function import( array $settings, bool $merge = true ): bool {
		$buckets = array();
		foreach ( $settings as $module => $values ) {
			if ( ! is_string( $module ) || ! is_array( $values ) ) {
				continue;
			}
			$owner = $this->owner_of_module( $module );
			if ( null === $owner ) {
				continue;
			}
			$hash = spl_object_hash( $owner );
			if ( ! isset( $buckets[ $hash ] ) ) {
				$buckets[ $hash ] = array( $owner, array() );
			}
			$buckets[ $hash ][1][ $module ] = $values;
		}

		$imported = false;
		foreach ( $buckets as [ $owner, $payload ] ) {
			if ( $owner->import( $payload, $merge ) ) {
				$imported = true;
			}
		}
		return $imported;
	}

	/**
	 * Defaults — merged across all wrapped Settings, or routed.
	 *
	 * @param string|null $module Specific module to retrieve, or null for all.
	 * @return array<string, array<string, mixed>>
	 */
	public function get_default_settings( ?string $module = null ): array {
		if ( null !== $module ) {
			$owner = $this->owner_of_module( $module );
			return null !== $owner ? $owner->get_default_settings( $module ) : array();
		}
		/**
		 * Narrow PHPStan's inferred type from the generic helper.
		 *
		 * @var array<string, array<string, mixed>> $merged
		 */
		$merged = $this->merge_from_all( static fn( $s ) => $s->get_default_settings() );
		return $merged;
	}

	/**
	 * Source for a specific value, routed to the owning Settings.
	 *
	 * @param string $module Module name.
	 * @param string $key    Setting key within the module.
	 * @return string `constant`, `file`, `db`, or `default`.
	 */
	public function get_source( string $module, string $key ): string {
		$owner = $this->owner_of_module( $module );
		return null !== $owner ? $owner->get_source( $module, $key ) : 'default';
	}

	/**
	 * Locate the Settings owning the module embedded in a dot-notation key.
	 *
	 * @noinspection PhpMissingReturnTypeInspection
	 *
	 * @param string $key Dot-notation key (`module.field`) or bare module.
	 * @return Settings|null
	 */
	private function owner_of( string $key ) {
		return $this->owner_of_module( explode( '.', $key, 2 )[0] );
	}

	/**
	 * Locate the Settings owning a specific module.
	 *
	 * @noinspection PhpMissingReturnTypeInspection
	 *
	 * @param string $module Module name.
	 * @return Settings|null
	 */
	private function owner_of_module( string $module ) {
		foreach ( $this->list as $s ) {
			if ( array_key_exists( $module, $s->get_default_settings() ) ) {
				return $s;
			}
		}
		return null;
	}

	/**
	 * Merge full settings trees from every wrapped Settings.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function merge_trees(): array {
		/**
		 * Narrow PHPStan's inferred type from the generic helper.
		 *
		 * @var array<string, array<string, mixed>> $merged
		 */
		$merged = $this->merge_from_all(
			static function ( $s ): array {
				$tree = $s->get();
				return is_array( $tree ) ? $tree : array();
			}
		);
		return $merged;
	}

	/**
	 * Invoke a callback against every wrapped Settings; ignore return values.
	 *
	 * Used by the fan-out methods (reset, backup, etc.) where the per-Settings
	 * return type isn't aggregated, only the action matters.
	 *
	 * @param callable $callback `function( Settings $s ): mixed` — return value discarded.
	 * @return void
	 */
	private function for_each( callable $callback ): void {
		foreach ( $this->list as $s ) {
			$callback( $s );
		}
	}

	/**
	 * Invoke a callback against every wrapped Settings; return true if any
	 * call returned truthy. Used for "did any backup restore?" / "does any
	 * have a backup?" semantics.
	 *
	 * @param callable $callback `function( Settings $s ): bool`.
	 * @return bool
	 */
	private function any_of( callable $callback ): bool {
		$any = false;
		foreach ( $this->list as $s ) {
			if ( $callback( $s ) ) {
				$any = true;
			}
		}
		return $any;
	}

	/**
	 * Invoke a callback against every wrapped Settings and merge the
	 * returned arrays. Used for "merged tree" and "merged defaults"
	 * semantics where each Setting contributes its own keys.
	 *
	 * @param callable $callback `function( Settings $s ): array`.
	 * @return array<string, mixed>
	 */
	private function merge_from_all( callable $callback ): array {
		$merged = array();
		foreach ( $this->list as $s ) {
			$merged = array_merge( $merged, (array) $callback( $s ) );
		}
		return $merged;
	}
}
