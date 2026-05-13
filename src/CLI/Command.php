<?php
/**
 * Abstract base for MilliBase WP-CLI subcommands.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase\CLI;

use MilliBase\Concerns\HasConfig;
use MilliBase\Settings;
use MilliBase\Settings\Group;
use WP_CLI;

/**
 * Shared state (config, Settings/Group) and helpers for every subcommand
 * under `wp <slug> config`. Concrete subcommands extend this class and
 * implement `__invoke( array $args, array $assoc_args )`.
 *
 * @since 2.5.0
 */
abstract class Command {

	use HasConfig;

	/**
	 * The plugin configuration.
	 *
	 * @since 2.5.0
	 * @var array<string, mixed>
	 */
	protected array $config;

	/**
	 * Settings (or `Settings\Group`) backing this subcommand.
	 * Cross-prefix tolerant; do not add a native type.
	 * See docs/04-reference/04-namespace-prefixing.md.
	 *
	 * @noinspection PhpMissingFieldTypeInspection
	 *
	 * @since 2.5.0
	 * @var Settings|Group
	 */
	protected $settings;

	/**
	 * Construct the subcommand.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param array<string, mixed> $config   The plugin configuration.
	 * @param Settings|Group       $settings Cross-prefix tolerant; see {@see self::$settings}.
	 */
	public function __construct( array $config, $settings ) {
		$this->config   = $config;
		$this->settings = $settings;
	}

	/**
	 * Output items in the requested format.
	 *
	 * For JSON, outputs the raw nested data structure (not row objects).
	 * Other formats go through WP_CLI's built-in formatter.
	 *
	 * @since 2.5.0
	 *
	 * @param array<int, array<string, string>> $items    The rows to display.
	 * @param mixed                             $raw_data The original nested data (for JSON output).
	 * @param string                            $format   The output format.
	 * @param array<int, string>                $columns  The column names.
	 * @return void
	 */
	protected function output_items( array $items, $raw_data, string $format, array $columns ): void {
		if ( 'json' === $format ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$json = json_encode( $raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			WP_CLI::line( false !== $json ? $json : '{}' );
			return;
		}

		WP_CLI\Utils\format_items( $format, $items, $columns );
	}

	/**
	 * Flatten nested settings into dot-notation rows for display.
	 *
	 * @since 2.5.0
	 *
	 * @param array<string, mixed> $data        Nested settings array.
	 * @param bool                 $show_source Whether to include a source column.
	 * @return array<int, array<string, string>>
	 */
	protected function flatten_settings( array $data, bool $show_source ): array {
		$rows = array();

		foreach ( $data as $module => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				$row = array(
					'key'   => $module . '.' . $key,
					'value' => $this->stringify( $value ),
				);

				if ( $show_source ) {
					$row['source'] = $this->settings->get_source( (string) $module, (string) $key );
				}

				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Convert a value to a human-readable string for CLI output.
	 *
	 * @since 2.5.0
	 *
	 * @param mixed $value The value to convert.
	 * @return string
	 */
	protected function stringify( $value ): string {
		if ( null === $value ) {
			return 'null';
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_array( $value ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			$json = json_encode( $value, JSON_UNESCAPED_SLASHES );
			return false !== $json ? $json : '(array)';
		}
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}
		return '';
	}
}
