<?php
/**
 * Runs declarative migrations registered on a Manager.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\Migration;

use MilliBase\Manager;

/**
 * Runs the declarative migration list a consumer plugin attached to its
 * Manager config.
 *
 * Identity is `name@version` — bumping the version of an existing
 * migration creates a new identity and re-runs it (escape hatch for
 * "I shipped the migration wrong"). State is persisted as a map of
 * `name@version => 'completed'` or `['failed', $message, $timestamp]`,
 * stored in either `wp_options` (site scope) or `wp_sitemeta` (network
 * scope) under the key `<slug>_migration_state`.
 *
 * Array order is the source of truth — migrations run in the order
 * declared in the config, across both scopes. State rows are read once
 * per scope (lazily, on the first migration in that scope) and written
 * once per scope at the end of the run.
 *
 * @since 2.5.0
 */
final class Runner {

	/**
	 * Manager slug, used as the prefix for the state row option name.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Declarative migration list from the Manager config.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $migrations;

	/**
	 * Manager instance passed to each migration callback. Untyped because
	 * Settings/Manager are cross-prefix tolerant (see Settings docs).
	 *
	 * @noinspection PhpMissingFieldTypeInspection
	 * @var Manager
	 */
	private $manager;

	/**
	 * Create a Runner.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @param string                           $slug       Manager slug.
	 * @param array<int, array<string, mixed>> $migrations Migration list.
	 * @param Manager                          $manager    Manager instance for callbacks.
	 */
	public function __construct( string $slug, array $migrations, $manager ) {
		$this->slug       = $slug;
		$this->migrations = $migrations;
		$this->manager    = $manager;
	}

	/**
	 * Run all pending migrations.
	 *
	 * Skips migrations already recorded as `completed` or `failed`. Network-
	 * scoped migrations on a single-site install are silently skipped and
	 * re-evaluated on the next run (so the migration picks up automatically
	 * if the install later becomes multisite).
	 *
	 * Callback success: state recorded as `'completed'`.
	 * Callback throws: state recorded as `['failed', message, timestamp]`.
	 * Don't catch `\Error` — those are programmer bugs, not migration outcomes.
	 *
	 * @return void
	 */
	public function run(): void {
		$is_multisite = function_exists( 'is_multisite' ) && is_multisite();

		$states  = array();
		$changed = array();

		foreach ( $this->migrations as $migration ) {
			if ( ! is_array( $migration ) ) {
				continue;
			}

			$name    = $migration['name'] ?? '';
			$version = $migration['version'] ?? '';
			$scope   = $migration['scope'] ?? 'site';

			if ( ! is_string( $name ) || '' === $name ) {
				continue;
			}
			if ( ! is_string( $version ) || '' === $version ) {
				continue;
			}
			if ( 'site' !== $scope && 'network' !== $scope ) {
				continue;
			}
			if ( 'network' === $scope && ! $is_multisite ) {
				continue;
			}

			if ( ! isset( $states[ $scope ] ) ) {
				$states[ $scope ] = $this->read_state( $scope );
			}

			$id = "{$name}@{$version}";
			if ( isset( $states[ $scope ][ $id ] ) ) {
				continue;
			}

			$callback = $migration['callback'] ?? null;
			if ( ! is_callable( $callback ) ) {
				continue;
			}

			try {
				$callback( $this->manager );
				$states[ $scope ][ $id ] = 'completed';
			} catch ( \Exception $e ) {
				$states[ $scope ][ $id ] = array( 'failed', $e->getMessage(), time() );
			}

			$changed[ $scope ] = true;
		}

		foreach ( array_keys( $changed ) as $scope ) {
			$this->write_state( $scope, $states[ $scope ] );
		}
	}

	/**
	 * Option name for the migration state row.
	 *
	 * @return string
	 */
	private function state_key(): string {
		return $this->slug . '_migration_state';
	}

	/**
	 * Read the migration state row for the given scope.
	 *
	 * @param string $scope `'site'` or `'network'`.
	 * @return array<string, string|array{0: string, 1: string, 2: int}>
	 */
	private function read_state( string $scope ): array {
		$value = 'network' === $scope
			? get_site_option( $this->state_key(), array() )
			: get_option( $this->state_key(), array() );

		return is_array( $value ) ? $value : array();
	}

	/**
	 * Write the migration state row for the given scope.
	 *
	 * @param string                                                    $scope `'site'` or `'network'`.
	 * @param array<string, string|array{0: string, 1: string, 2: int}> $state State map.
	 * @return void
	 */
	private function write_state( string $scope, array $state ): void {
		if ( 'network' === $scope ) {
			update_site_option( $this->state_key(), $state );
		} else {
			update_option( $this->state_key(), $state );
		}
	}
}
