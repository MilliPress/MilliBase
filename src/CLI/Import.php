<?php
/**
 * `wp <slug> config import` — load settings from a JSON file.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\CLI;

use WP_CLI;

/**
 * Import settings from a JSON file.
 *
 * Creates an automatic backup before importing.
 *
 * ## OPTIONS
 *
 * [--file=<path>]
 * : Path to the JSON file to import.
 *
 * [--merge]
 * : Merge with existing settings instead of replacing.
 * ---
 * default: true
 * ---
 *
 * [--yes]
 * : Skip the confirmation prompt.
 *
 * ## EXAMPLES
 *
 *     wp myplugin config import --file=settings.json
 *     wp myplugin config import --file=settings.json --no-merge --yes
 *
 * @since 2.5.0
 */
final class Import extends Command {

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
		$file = $assoc_args['file'] ?? null;
		/** Merge flag: true = merge into existing, false = overwrite.  @var bool $merge */
		$merge = WP_CLI\Utils\get_flag_value( $assoc_args, 'merge', true );

		if ( null === $file || '' === $file ) {
			WP_CLI::error( 'Usage: wp <slug> config import --file=<path>' );
		}

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			WP_CLI::error( "File not found or not readable: {$file}" );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = file_get_contents( $file );
		if ( false === $contents ) {
			WP_CLI::error( "Failed to read file: {$file}" );
		}

		/** Decoded JSON payload. @var array<string, mixed>|null $data */
		$data = json_decode( $contents, true );
		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'Invalid JSON in file.' );
		}

		if ( ! $merge ) {
			WP_CLI::confirm( 'Import will replace all existing settings. Continue?', $assoc_args );
		}

		$this->settings->backup();

		if ( ! $this->settings->import( $data, (bool) $merge ) ) {
			WP_CLI::error( 'Import failed. No valid modules found in the provided data.' );
		}

		$count = count( $data );
		$mode  = $merge ? 'merged' : 'replaced';
		WP_CLI::success( "Imported {$count} module(s) ({$mode})." );
	}
}
