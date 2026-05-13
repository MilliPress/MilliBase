<?php
/**
 * Settings registry — looks up a Settings instance by scope (per-site vs network).
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\Settings;

use MilliBase\Settings;

/**
 * Holds the Settings instances registered under one shared CLI slug and
 * resolves the right one for a given scope (per-site or network).
 *
 * Used by the CLI: each subcommand reads the `--network` flag from
 * `$assoc_args` and calls {@see self::resolve()} to pick the matching
 * Settings before performing its operation. No polymorphic routing,
 * no module-name guessing — the caller picks the scope explicitly.
 *
 * Cross-prefix tolerant — accepts Settings instances from any namespace
 * prefix; the wrapped object only needs to expose the Settings API
 * shape, not the literal `\MilliBase\Settings` class.
 *
 * @since 2.5.0
 */
final class Group {

	/**
	 * Registered Settings instances. Primary at index 0.
	 *
	 * @noinspection PhpMissingFieldTypeInspection
	 *
	 * @var array<int, Settings>
	 */
	private array $list;

	/**
	 * Construct the registry around a primary Settings.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $primary First registered Settings.
	 */
	public function __construct( $primary ) {
		$this->list = array( $primary );
	}

	/**
	 * Append an additional Settings to the registry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $settings Settings to add.
	 * @return void
	 */
	public function add( $settings ): void {
		$this->list[] = $settings;
	}

	/**
	 * Resolve the Settings for the requested scope.
	 *
	 * - `$wants_network = true` returns the first network-scoped member
	 *   (or null if none — the caller should error out).
	 * - `$wants_network = false` returns the first per-site member, falling
	 *   through to a network member when the registry is network-only. Lets
	 *   single-Manager plugins ignore the `--network` flag entirely.
	 *
	 * @since 2.5.0
	 *
	 * @param bool $wants_network Whether the operator explicitly asked for network scope.
	 * @return Settings|null
	 *
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	public function resolve( bool $wants_network ) {
		if ( $wants_network ) {
			return $this->find( static fn( $s ) => $s->is_network() );
		}
		return $this->find( static fn( $s ) => ! $s->is_network() )
			?? $this->find( static fn( $s ) => $s->is_network() );
	}

	/**
	 * First member matching the predicate, or null.
	 *
	 * @noinspection PhpMissingReturnTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param callable $predicate `function( Settings $s ): bool`.
	 * @return Settings|null
	 */
	private function find( callable $predicate ) {
		foreach ( $this->list as $settings ) {
			if ( $predicate( $settings ) ) {
				return $settings;
			}
		}
		return null;
	}
}
