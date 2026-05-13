<?php
/**
 * `wp <slug> config export` — emit settings as JSON.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\CLI;

use WP_CLI;

/**
 * Export settings as JSON.
 *
 * Outputs JSON to stdout by default. Use --file to write directly to a file.
 *
 * ## OPTIONS
 *
 * [--module=<module>]
 * : Export only a specific module.
 *
 * [--include-encrypted]
 * : Include decrypted values of encrypted fields.
 *
 * [--file=<path>]
 * : Write output to a file instead of stdout.
 *
 * [--network]
 * : Operate on network-scoped settings. Errors when no network Settings is registered.
 *
 * ## EXAMPLES
 *
 *     # Export to stdout.
 *     wp myplugin config export
 *
 *     # Export directly to a file.
 *     wp myplugin config export --file=settings.json
 *
 *     # Export a single module.
 *     wp myplugin config export --module=cache
 *
 *     # Export the network scope.
 *     wp myplugin config export --network
 *
 * @since 2.5.0
 */
final class Export extends Command {

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
		$settings          = $this->resolve( $assoc_args );
		$module            = $assoc_args['module'] ?? null;
		$include_encrypted = isset( $assoc_args['include-encrypted'] );
		$file              = $assoc_args['file'] ?? null;

		$data = $settings->export( $module, $include_encrypted );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		if ( false === $json ) {
			WP_CLI::error( 'Failed to encode settings as JSON.' );
		}

		if ( null !== $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false === file_put_contents( $file, $json . "\n" ) ) {
				WP_CLI::error( "Failed to write to '{$file}'." );
			}
			WP_CLI::success( "Settings exported to '{$file}'." );
			return;
		}

		WP_CLI::line( $json );
	}
}
