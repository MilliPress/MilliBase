<?php
/**
 * WP-CLI command registration — orchestrates the per-subcommand classes.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

use MilliBase\Concerns\HasConfig;
use MilliBase\Settings\Group;
use WP_CLI;

/**
 * Registers `wp <slug> config <subcommand>` for every MilliBase plugin.
 *
 * Each subcommand (get, set, reset, backup, restore, export, import) lives
 * in its own class under `src/CLI/` extending {@see CLI\Command}; this class
 * just wires them up. See `docs/02-usage/04-wp-cli.md` for the operator-facing
 * command reference.
 *
 * @since 2.5.0
 */
final class CLI {

	use HasConfig;

	/**
	 * The plugin configuration.
	 *
	 * @since 2.5.0
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Settings registry shared by every subcommand.
	 * Cross-prefix tolerant; do not add a native type.
	 * See docs/04-reference/04-namespace-prefixing.md.
	 *
	 * @noinspection PhpMissingFieldTypeInspection
	 *
	 * @since 2.5.0
	 * @var Group
	 */
	private $group;

	/**
	 * Construct the CLI orchestrator.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param array<string, mixed> $config The plugin configuration.
	 * @param Group                $group  Cross-prefix tolerant; see {@see self::$group}.
	 */
	public function __construct( array $config, $group ) {
		$this->config = $config;
		$this->group  = $group;
	}

	/**
	 * Register every subcommand with WP-CLI.
	 *
	 * @since 2.5.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		$root = $this->config_string( 'slug', 'millibase' ) . ' config';

		WP_CLI::add_command( "{$root} get", new CLI\Get( $this->config, $this->group ) );
		WP_CLI::add_command( "{$root} set", new CLI\Set( $this->config, $this->group ) );
		WP_CLI::add_command( "{$root} reset", new CLI\Reset( $this->config, $this->group ) );
		WP_CLI::add_command( "{$root} backup", new CLI\Backup( $this->config, $this->group ) );
		WP_CLI::add_command( "{$root} restore", new CLI\Restore( $this->config, $this->group ) );
		WP_CLI::add_command( "{$root} export", new CLI\Export( $this->config, $this->group ) );
		WP_CLI::add_command( "{$root} import", new CLI\Import( $this->config, $this->group ) );
	}
}
