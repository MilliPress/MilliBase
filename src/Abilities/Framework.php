<?php
/**
 * Framework-level ability factory — wraps Settings operations as abilities.
 *
 * @package MilliBase
 * @author  Philipp Wellmer & Vedran <hello@millipress.com>
 */

namespace MilliBase\Abilities;

use MilliBase\Settings;

/**
 * Builds ability config entries that wrap the built-in Settings
 * operations (export, reset, backup, restore). See docs/02-usage/05-abilities.md.
 *
 * @since 2.5.0
 */
final class Framework {

	/**
	 * Build the four standard settings abilities for a plugin.
	 *
	 * When the bound Settings is network-scoped, every ability id gets a
	 * `network-` prefix and the label/description name the scope explicitly
	 * so the network surface never collides with the per-site surface on
	 * the same plugin slug and consumers can tell the two apart.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $settings Cross-prefix tolerant; bound into closures.
	 * @return array<int, array<string, mixed>>
	 */
	public static function settings( $settings ): array {
		// @phpstan-ignore function.alreadyNarrowedType (defensive: a different bundled library version of Settings may predate is_network())
		$is_network = method_exists( $settings, 'is_network' ) && $settings->is_network();

		return array(
			self::export( $settings, $is_network ),
			self::reset( $settings, $is_network ),
			self::backup( $settings, $is_network ),
			self::restore( $settings, $is_network ),
		);
	}

	/**
	 * Build the `settings-export` ability entry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $settings   The Settings to read from.
	 * @param bool     $is_network Whether the bound Settings is network-scoped.
	 * @return array<string, mixed>
	 */
	private static function export( $settings, bool $is_network ): array {
		return array(
			'id'            => ( $is_network ? 'network-' : '' ) . 'settings-export',
			'label'         => $is_network
				? __( 'Export Network Settings', 'millibase' )
				: __( 'Export Settings', 'millibase' ),
			'description'   => $is_network
				/* translators: An AI reads this to decide when to call this operation. Keep `module` verbatim; it is a literal API field name, not a word to translate. */
				? __( 'Export the network settings as an object keyed by module name (site settings are not included). Pass the optional `module` argument to limit which modules are populated; the response shape is always module → settings. Passwords and API keys are never returned: a configured secret reads back as a row of bullet characters, an unconfigured one as an empty string. Never treat a masked value as the real one.', 'millibase' )
				/* translators: An AI reads this to decide when to call this operation. Keep `module` verbatim; it is a literal API field name, not a word to translate. */
				: __( 'Export the site settings as an object keyed by module name. Pass the optional `module` argument to limit which modules are populated; the response shape is always module → settings. Passwords and API keys are never returned: a configured secret reads back as a row of bullet characters, an unconfigured one as an empty string. Never treat a masked value as the real one.', 'millibase' ),
			// Masked, never decrypted: this leaves the site over REST and MCP.
			'callback'      => static function ( $input = null ) use ( $settings ): array {
				return self::as_objects( $settings->export( self::input_string( $input, 'module' ), 'mask' ) );
			},
			'input_schema'  => array(
				'type'       => array( 'object', 'null' ),
				'properties' => array(
					'module' => array( 'type' => 'string' ),
				),
			),
			'output_schema' => array(
				'type'                 => 'object',
				'description'          => __( 'Settings keyed by module name; each module is an object of key/value pairs.', 'millibase' ),
				'additionalProperties' => array(
					'type' => 'object',
				),
			),
			'meta'          => array(
				'annotations' => array(
					'readonly' => true,
				),
			),
		);
	}

	/**
	 * Turn empty module arrays into objects.
	 *
	 * PHP cannot tell an empty map from an empty list, so an emptied module
	 * serializes to `[]`, contradicting the declared
	 * `additionalProperties: {type: object}` and reading to a model as a list
	 * rather than as "module present, nothing in it".
	 *
	 * @since 2.9.0
	 *
	 * @param array<string, mixed> $tree Module → settings tree.
	 * @return array<string, mixed>
	 */
	private static function as_objects( array $tree ): array {
		foreach ( $tree as $module => $settings ) {
			if ( array() === $settings ) {
				$tree[ $module ] = (object) array();
			}
		}

		return $tree;
	}

	/**
	 * Build the `settings-reset` ability entry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $settings   The Settings to mutate.
	 * @param bool     $is_network Whether the bound Settings is network-scoped.
	 * @return array<string, mixed>
	 */
	private static function reset( $settings, bool $is_network ): array {
		return array(
			'id'            => ( $is_network ? 'network-' : '' ) . 'settings-reset',
			'label'         => $is_network
				? __( 'Reset Network Settings to Defaults', 'millibase' )
				: __( 'Reset Settings to Defaults', 'millibase' ),
			'description'   => $is_network
				/* translators: An AI reads this to decide when to call this destructive operation. Keep the automatic-backup promise intact; it is what makes the reset recoverable. */
				? __( 'Reset the network settings to their defaults (site settings are not affected). An automatic backup is created before the reset.', 'millibase' )
				/* translators: An AI reads this to decide when to call this destructive operation. Keep the automatic-backup promise intact; it is what makes the reset recoverable. */
				: __( 'Reset the site settings to their defaults. An automatic backup is created before the reset.', 'millibase' ),
			'callback'      => static function ( $input = null ) use ( $settings ): array {
				$module = self::input_string( $input, 'module' );
				return array( 'success' => $settings->reset( $module ) );
			},
			'input_schema'  => array(
				'type'       => array( 'object', 'null' ),
				'properties' => array(
					'module' => array( 'type' => 'string' ),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
				),
				'required'   => array( 'success' ),
			),
			'meta'          => array(
				'annotations' => array(
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Build the `settings-backup` ability entry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $settings   The Settings to back up.
	 * @param bool     $is_network Whether the bound Settings is network-scoped.
	 * @return array<string, mixed>
	 */
	private static function backup( $settings, bool $is_network ): array {
		return array(
			'id'            => ( $is_network ? 'network-' : '' ) . 'settings-backup',
			'label'         => $is_network
				? __( 'Back Up Network Settings', 'millibase' )
				: __( 'Back Up Settings', 'millibase' ),
			'description'   => $is_network
				/* translators: An AI reads this to decide when to call this operation. Keep the 3-day expiry; an AI may offer this as a safety net before a risky change. */
				? __( 'Take a backup of the current network settings (site settings are not included). There is one backup slot, so this replaces any earlier backup, and settings-restore always restores this one. The response reports when it expires.', 'millibase' )
				/* translators: An AI reads this to decide when to call this operation. Keep the 3-day expiry; an AI may offer this as a safety net before a risky change. */
				: __( 'Take a backup of the current site settings. There is one backup slot, so this replaces any earlier backup, and settings-restore always restores this one. The response reports when it expires.', 'millibase' ),
			'callback'      => static function ( $input = null ) use ( $settings ): array {
				$module     = self::input_string( $input, 'module' );
				$expires_at = $settings->backup( $module );

				return array(
					'success'    => $settings->has_backup(),
					'module'     => $module ?? '',
					'expires_at' => $expires_at > 0 ? gmdate( 'c', $expires_at ) : '',
				);
			},
			'input_schema'  => array(
				'type'       => array( 'object', 'null' ),
				'properties' => array(
					'module' => array( 'type' => 'string' ),
				),
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'success'    => array( 'type' => 'boolean' ),
					'module'     => array(
						'type'        => 'string',
						'description' => __( 'The module that was backed up, or empty for all of them.', 'millibase' ),
					),
					'expires_at' => array(
						'type'        => 'string',
						'description' => __( 'ISO 8601 timestamp after which the backup is gone and settings-restore has nothing to restore.', 'millibase' ),
					),
				),
				'required'   => array( 'success' ),
			),
			'meta'          => array(
				'annotations' => array(
					'idempotent' => true,
				),
			),
		);
	}

	/**
	 * Build the `settings-restore` ability entry.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $settings   The Settings to restore into.
	 * @param bool     $is_network Whether the bound Settings is network-scoped.
	 * @return array<string, mixed>
	 */
	private static function restore( $settings, bool $is_network ): array {
		return array(
			'id'            => ( $is_network ? 'network-' : '' ) . 'settings-restore',
			'label'         => $is_network
				? __( 'Restore Network Settings from Backup', 'millibase' )
				: __( 'Restore Settings from Backup', 'millibase' ),
			'description'   => $is_network
				/* translators: An AI reads this to decide when to call this destructive operation. Keep `success: false` verbatim; it is a literal response value the AI checks for. */
				? __( 'Restore the most recent network settings backup (site settings are not affected). Returns success: false when no backup is available.', 'millibase' )
				/* translators: An AI reads this to decide when to call this destructive operation. Keep `success: false` verbatim; it is a literal response value the AI checks for. */
				: __( 'Restore the most recent site settings backup. Returns success: false when no backup is available.', 'millibase' ),
			'callback'      => static function () use ( $settings ): array {
				return array( 'success' => $settings->restore_backup() );
			},
			'input_schema'  => array(
				'type'                 => array( 'object', 'null' ),
				'additionalProperties' => false,
			),
			'output_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array( 'type' => 'boolean' ),
				),
				'required'   => array( 'success' ),
			),
			'meta'          => array(
				'annotations' => array(
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Extract a string field from ability input, returning null when absent or empty.
	 *
	 * @since 2.5.0
	 *
	 * @param mixed  $input The ability input as received by execute_callback.
	 * @param string $key   The field name to extract.
	 * @return string|null
	 */
	private static function input_string( $input, string $key ): ?string {
		if ( ! is_array( $input ) ) {
			return null;
		}
		$value = $input[ $key ] ?? null;
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		return $value;
	}
}
