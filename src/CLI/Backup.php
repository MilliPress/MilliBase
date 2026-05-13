<?php
/**
 * `wp <slug> config backup` — capture a settings snapshot.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\CLI;

use WP_CLI;

/**
 * Create a backup of current settings.
 *
 * Backup expires after 12 hours.
 *
 * ## OPTIONS
 *
 * [--network]
 * : Operate on network-scoped settings. Errors when no network Settings is registered.
 *
 * ## EXAMPLES
 *
 *     wp myplugin config backup
 *     wp myplugin config backup --network
 *
 * @since 2.5.0
 */
final class Backup extends Command {

	/**
	 * Execute the subcommand.
	 *
	 * @since 2.5.0
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Named arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$settings = $this->resolve( $assoc_args );
		$settings->backup();
		WP_CLI::success( 'Backup created. Expires in 12 hours.' );
	}
}
