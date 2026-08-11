<?php
/**
 * Settings storage layer with dot-notation access, encryption, backup/restore,
 * and a standalone mode for reading config before WordPress loads.
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Handles settings storage: option CRUD, dot-notation get/set with in-memory
 * caching, encryption, constants override, config file sync, backup/restore,
 * and import/export.
 *
 * @since 1.0.0
 */
final class Settings {

	/**
	 * Placeholder returned to REST clients in place of a stored secret.
	 *
	 * Carries no key material: a set enc_ field reads back as this string so
	 * the UI shows a value exists without exposing it. Treated as "keep the
	 * stored value" on write — see {@see self::preserve_secret_writes()}.
	 * Opt-in fields may instead read back a partial mask that reveals a few
	 * leading/trailing characters — see {@see self::mask_secret()}.
	 *
	 * @since 2.6.0
	 * @var string
	 */
	private const SECRET_MASK = '••••••••••••••••••••';

	/**
	 * How long a settings backup survives, in seconds.
	 *
	 * @since 2.9.0
	 * @var int
	 */
	private const BACKUP_LIFETIME = 3 * DAY_IN_SECONDS;

	/**
	 * The bullet character used in secret masks (U+2022).
	 *
	 * On write its presence in an enc_ value marks the value as a (full or
	 * partial) mask the client never edited — a real secret never contains it.
	 *
	 * @since 2.6.0
	 * @var string
	 */
	private const SECRET_BULLET = '•';

	/**
	 * Default leading characters revealed by a partial mask on a `type: 'key'` field.
	 *
	 * @since 2.6.0
	 * @var int
	 */
	private const MASK_FIRST_DEFAULT = 4;

	/**
	 * Default trailing characters revealed by a partial mask on a `type: 'key'` field.
	 *
	 * @since 2.6.0
	 * @var int
	 */
	private const MASK_LAST_DEFAULT = 4;

	/**
	 * The option name in the database.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $option_name;

	/**
	 * Constant prefix for wp-config.php overrides (e.g. 'MC' → MC_STORAGE_HOST).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $constant_prefix;

	/**
	 * Whether sodium encryption is enabled for enc_* fields.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $encryption;

	/**
	 * Default settings extracted from the schema.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, mixed>>
	 */
	private array $defaults;

	/**
	 * Dot-notation keys whose values survive a full reset.
	 *
	 * @since 2.6.4
	 * @var array<int, string>
	 */
	private array $preserved_keys = array();

	/**
	 * The ConfigFile instance, or null if config files are disabled.
	 *
	 * @since 1.0.0
	 * @var ConfigFile|null
	 */
	private ?ConfigFile $config_file;

	/**
	 * Plugin slug for filter hook naming ({slug}_settings_defaults).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Whether this instance operates in standalone mode (no WordPress DB).
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $standalone;

	/**
	 * Whether these are Network Settings.
	 *
	 * @since 2.5.0
	 * @var bool
	 */
	private bool $network;

	/**
	 * In-memory cache of resolved settings, keyed by cache key.
	 *
	 * Cleared on set(), reset(), and import().
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private array $resolved = array();

	/**
	 * When true, `filter_settings_by_constants` returns its input
	 * unchanged. Used internally by {@see self::read_raw()} to bypass
	 * schema-level stripping for migration callbacks that need to see
	 * legacy data the current schema would otherwise hide.
	 *
	 * @since 2.5.0
	 * @var bool
	 */
	private bool $bypass_schema_filter = false;

	/**
	 * When true, {@see self::fire_setting_changed_hooks()} returns without
	 * firing. Set around the reset preservation write-back so re-storing an
	 * unchanged preserved value does not emit a phantom "added" change event.
	 *
	 * @since 2.6.4
	 * @var bool
	 */
	private bool $suppress_change_hooks = false;

	/**
	 * When true, option writes do not sync to the config file — the
	 * {@see self::reconcile_overrides()} row write treats the file as a
	 * source, never a target.
	 *
	 * @since 2.8.0
	 * @var bool
	 */
	private bool $suppress_file_sync = false;

	/**
	 * When true, decryption is skipped. Lets {@see self::read_stored()}
	 * return enc_ values at rest so the config-file heal never writes
	 * plaintext.
	 *
	 * @since 2.8.0
	 * @var bool
	 */
	private bool $bypass_decryption = false;

	/**
	 * Create a new Settings instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Configuration array.
	 *
	 * @throws \InvalidArgumentException If slug is empty.
	 */
	public function __construct( array $config ) {
		$slug       = $config['slug'] ?? '';
		$this->slug = is_string( $slug ) ? $slug : '';

		if ( '' === $this->slug ) {
			throw new \InvalidArgumentException( 'Settings requires a non-empty "slug" config value.' );
		}

		/** @var array<string, array<string, mixed>> $defaults_config */
		$defaults_config = is_array( $config['defaults'] ?? null ) ? $config['defaults'] : array();
		$this->defaults  = $defaults_config;

		$default_option        = $this->slug;
		$option_name           = $config['option_name'] ?? $default_option;
		$this->option_name     = is_string( $option_name ) ? $option_name : $default_option;
		$constant_prefix       = $config['constant_prefix'] ?? '';
		$this->constant_prefix = strtoupper( is_string( $constant_prefix ) ? $constant_prefix : '' );
		$this->encryption      = (bool) ( $config['encryption'] ?? false );
		$this->standalone      = (bool) ( $config['standalone'] ?? false );
		$this->network         = (bool) ( $config['network'] ?? false );

		if ( is_array( $config['preserved_keys'] ?? null ) ) {
			$this->preserved_keys = array_values( array_filter( $config['preserved_keys'], 'is_string' ) );
		}

		// Initialize the config file handler if configured.
		if ( ! empty( $config['config_file'] ) && is_array( $config['config_file'] ) ) {
			$directory         = $config['config_file']['directory'] ?? '';
			$this->config_file = new ConfigFile(
				is_string( $directory ) ? $directory : '',
				fn(): string => $this->resolve_domain(),
				$this->option_name
			);
		} else {
			$this->config_file = null;
		}

		$this->register_hooks();
	}

	/**
	 * Create a standalone Settings instance for use before WordPress loads.
	 *
	 * Reads from config files and constants only — no database access.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Configuration array.
	 *
	 * @return self
	 */
	public static function standalone( array $config ): self {
		$config['standalone'] = true;
		return new self( $config );
	}

	/**
	 * Register WordPress hooks for option filtering, encryption, and config file sync.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( $this->standalone || ! function_exists( 'add_filter' ) ) {
			return;
		}

		// Network mode swaps `option` for `site_option` in every WP hook name.
		$opt  = $this->network ? 'site_option' : 'option';
		$name = $this->option_name;

		// Merge defaults and strip constant-defined keys from the stored option.
		add_filter( "{$opt}_{$name}", array( $this, 'filter_settings_by_constants' ) );
		add_filter( "default_{$opt}_{$name}", array( $this, 'filter_settings_by_constants' ) );

		// Encryption hooks.
		if ( $this->encryption ) {
			add_filter( "pre_update_{$opt}_{$name}", array( $this, 'encrypt_sensitive_settings_data' ), 0 );
			add_filter( "{$opt}_{$name}", array( $this, 'decrypt_sensitive_settings_data' ), 0 );
		}

		// Setting change hooks (config file sync + change notifications).
		add_action( "add_{$opt}_{$name}", array( $this, 'on_add_option' ), 10, 2 );

		// Different args for `update_site_option_<name>` and `update_option_<name>`.
		if ( $this->network ) {
			add_action(
				"update_{$opt}_{$name}",
				function ( $option, $value, $old_value ) {
					$this->on_update_option( (array) $old_value, (array) $value );
				},
				10,
				3
			);
		} else {
			add_action( "update_{$opt}_{$name}", array( $this, 'on_update_option' ), 10, 2 );
		}

		add_action( "delete_{$opt}", array( $this, 'on_delete_option' ) );
	}

	/**
	 * Merge additional defaults into this instance.
	 *
	 * Used by the Manager to inject schema-extracted defaults (e.g. active-toggle
	 * keys) into a pre-built Settings instance that was created before the Schema
	 * was available. Existing keys are never overwritten.
	 *
	 * @since 1.0.3
	 *
	 * @param array<string, array<string, mixed>> $additional Additional defaults to merge.
	 *
	 * @return void
	 */
	public function merge_defaults( array $additional ): void {
		$this->defaults = array_replace_recursive( $additional, $this->defaults );
		$this->resolved = array();
	}

	/**
	 * Merge additional preserved keys into this instance.
	 *
	 * Mirrors {@see self::merge_defaults()}: lets the Manager inject
	 * schema-extracted preserve flags (and add-on extensions) into a pre-built
	 * Settings instance. Keys are unioned; order is not significant.
	 *
	 * @since 2.6.4
	 *
	 * @param array<int, string> $keys Dot-notation keys to preserve across a full reset.
	 *
	 * @return void
	 */
	public function merge_preserved_keys( array $keys ): void {
		$this->preserved_keys = array_values(
			array_unique( array_merge( $this->preserved_keys, array_filter( $keys, 'is_string' ) ) )
		);
	}

	/**
	 * Whether this Settings instance reads/writes via network options.
	 *
	 * @since 2.5.0
	 *
	 * @return bool
	 */
	public function is_network(): bool {
		return $this->network;
	}

	/**
	 * Check whether a field key denotes an encrypted field.
	 *
	 * @since 1.1.0
	 *
	 * @param string $key Field key.
	 * @return bool
	 */
	private static function is_enc_key( string $key ): bool {
		return strpos( $key, 'enc_' ) === 0;
	}

	/**
	 * Return a copy of a settings tree with stored secrets masked.
	 *
	 * Every enc_ field holding a non-empty string is replaced with
	 * {@see self::SECRET_MASK}; an unset enc_ field stays empty so the UI can
	 * tell "configured" from "not configured". Non-enc_ keys and non-string
	 * values are left untouched. Applied at the REST boundary to both the
	 * settings tree and the constants map; keys are always preserved so
	 * constant-locked fields still resolve as locked client-side.
	 *
	 * Fields listed in $mask_map read back as a partial mask exposing their
	 * leading/trailing characters for recognition — see {@see self::mask_secret()}.
	 *
	 * @since 2.6.0
	 * @since 2.6.0 Added the $mask_map parameter.
	 *
	 * @param array<string, mixed>                                        $tree     Module → key → value tree.
	 * @param array<string, array{first:int, last:int, structured?:bool}> $mask_map Per-field partial-mask config keyed by "module.key".
	 * @return array<string, mixed>
	 */
	public function redact_secrets( array $tree, array $mask_map = array() ): array {
		foreach ( $tree as $module_key => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				if ( self::is_enc_key( $key ) && is_string( $value ) && '' !== $value ) {
					$module_settings[ $key ] = self::mask_secret( $value, $mask_map[ "{$module_key}.{$key}" ] ?? null );
				}
			}
			$tree[ $module_key ] = $module_settings;
		}

		return $tree;
	}

	/**
	 * Normalize a field's `mask` shorthand into the array shape
	 * {@see self::mask_secret()} consumes.
	 *
	 * Accepts: `'full'` → returns null (signal: skip partial, full-mask);
	 * `'structured'` → defaults + structured on; `array{first?:int, last?:int,
	 * structured?:bool}` → custom; null/anything else → defaults.
	 *
	 * @since 2.6.0
	 *
	 * @param mixed $mask Raw `mask` shorthand from a field config.
	 * @return array{first:int, last:int, structured:bool}|null
	 */
	public static function normalize_mask_config( $mask ): ?array {
		if ( 'full' === $mask ) {
			return null;
		}

		$first      = self::MASK_FIRST_DEFAULT;
		$last       = self::MASK_LAST_DEFAULT;
		$structured = ( 'structured' === $mask );

		if ( is_array( $mask ) ) {
			if ( isset( $mask['first'] ) && is_int( $mask['first'] ) ) {
				$first = $mask['first'];
			}
			if ( isset( $mask['last'] ) && is_int( $mask['last'] ) ) {
				$last = $mask['last'];
			}
			$structured = ! empty( $mask['structured'] );
		}

		return array(
			'first'      => $first,
			'last'       => $last,
			'structured' => $structured,
		);
	}

	/**
	 * Compute the masked placeholder for a stored value using a field's
	 * `mask` config — public mirror of the per-field masking applied by
	 * {@see self::redact_secrets()} at the REST boundary.
	 *
	 * For consumers (e.g. surfacing a network-license key's masked shape
	 * inside a subsite admin UI) that need the same partial placeholder a
	 * REST GET would yield, without routing the value through the REST stack.
	 * Empty input returns '' — caller decides any fallback.
	 *
	 * @since 2.6.0
	 *
	 * @param string               $value Decrypted plaintext secret value.
	 * @param array<string, mixed> $field Field config; consults `mask`.
	 * @return string
	 */
	public static function mask_for_field( string $value, array $field ): string {
		if ( '' === $value ) {
			return '';
		}
		$config = self::normalize_mask_config( $field['mask'] ?? null );
		if ( null === $config ) {
			return self::SECRET_MASK;
		}
		return self::mask_secret( $value, $config );
	}

	/**
	 * Build the masked placeholder for a stored secret value.
	 *
	 * Default mode renders a bullet middle at input length (e.g.
	 * "ABCD••••••••••••••••••WXYZ"); `structured` mode preserves separators
	 * like `-` `/` `:` `_` (e.g. "MILL•-••••-••••-••••-DDDD"). Both reveal
	 * the input's length; only the full {@see self::SECRET_MASK} hides it.
	 * Falls back to the full mask for ENC:-prefixed values and for inputs
	 * too short to keep ≥4 chars hidden.
	 *
	 * @since 2.6.0
	 *
	 * @param string                                            $value Decrypted plaintext secret.
	 * @param array{first:int, last:int, structured?:bool}|null $mask  Partial-mask config, or null for full mask.
	 * @return string
	 */
	private static function mask_secret( string $value, ?array $mask ): string {
		if ( null === $mask ) {
			return self::SECRET_MASK;
		}
		if ( 0 === strpos( $value, 'ENC:' ) ) {
			return self::SECRET_MASK;
		}

		$first      = $mask['first'];
		$last       = $mask['last'];
		$middle_len = mb_strlen( $value ) - $first - $last;
		if ( $middle_len < 4 ) {
			return self::SECRET_MASK;
		}

		$middle_raw = mb_substr( $value, $first, $middle_len );
		$bullets    = str_repeat( self::SECRET_BULLET, $middle_len );
		$middle     = empty( $mask['structured'] )
			? $bullets
			: ( preg_replace( '/[\p{L}\p{N}]/u', self::SECRET_BULLET, $middle_raw ) ?? $bullets );

		return mb_substr( $value, 0, $first ) . $middle . mb_substr( $value, -$last );
	}

	/**
	 * Reinstate stored secrets the client did not change.
	 *
	 * Clients never receive real enc_ values (see {@see self::redact_secrets()}),
	 * so on save an enc_ field that still carries a bullet (full or partial mask)
	 * or is still ENC:-encrypted means "unchanged" — the stored value is restored
	 * so saving an unrelated setting cannot wipe the secret (an untouched field
	 * round-trips the mask, never ''). A genuine new string contains no bullet,
	 * passes through and is encrypted normally; an empty string is an explicit
	 * clear and is written through as-is. REST save path only; {@see self::update()}
	 * stays unconditional for internal/Pro callers.
	 *
	 * @since 2.6.0
	 *
	 * @param array<string, mixed> $incoming Submitted settings tree.
	 * @return array<string, mixed>
	 */
	public function preserve_secret_writes( array $incoming ): array {
		$stored = null;

		foreach ( $incoming as $module_key => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				if ( ! self::is_enc_key( $key ) ) {
					continue;
				}
				$unchanged = ( is_string( $value ) && false !== mb_strpos( $value, self::SECRET_BULLET ) )
					|| ( is_string( $value ) && 0 === strpos( $value, 'ENC:' ) );
				if ( ! $unchanged ) {
					continue;
				}

				if ( null === $stored ) {
					$stored = $this->read_raw();
				}

				$module_stored = $stored[ $module_key ] ?? array();
				$existing      = is_array( $module_stored ) ? ( $module_stored[ $key ] ?? '' ) : '';

				// Restore the stored secret; never let the mask reach storage.
				$module_settings[ $key ] = ( is_string( $existing ) && '' !== $existing )
					? $existing
					: '';
			}
			$incoming[ $module_key ] = $module_settings;
		}

		return $incoming;
	}

	// ─── Settings access ────────────────────────────────────────────────

	/**
	 * Get settings using optional dot notation.
	 *
	 * - `get()`                → all settings.
	 * - `get('cache')`         → all settings for the cache module.
	 * - `get('cache.ttl')`     → single value.
	 * - `get('cache.ttl', 60)` → single value with fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $key      Dot notation key, module name, or null for all.
	 * @param mixed       $fallback Fallback value if key not found.
	 *
	 * @return ($key is null ? array<string, array<string, mixed>> : mixed)
	 */
	public function get( ?string $key = null, $fallback = null ) {
		$settings = $this->resolve();

		if ( null === $key ) {
			return $settings;
		}

		return $this->dig( $settings, $key, $fallback );
	}

	/**
	 * Walk a settings tree by the dot-notation key.
	 *
	 * @since 2.6.2
	 *
	 * @param array<string, mixed> $tree     Tree to dig into.
	 * @param string               $key      Dot-notation key.
	 * @param mixed                $fallback Returned when any segment is missing.
	 * @return mixed
	 */
	private function dig( array $tree, string $key, $fallback ) {
		foreach ( explode( '.', $key ) as $k ) {
			if ( ! is_array( $tree ) || ! array_key_exists( $k, $tree ) ) {
				return $fallback;
			}
			$tree = $tree[ $k ];
		}

		return $tree;
	}

	/**
	 * Replace the stored settings with a complete new value.
	 *
	 * The Schema's sanitize callback runs automatically via WordPress's
	 * `sanitize_option_<name>` filter chain. Clears the in-memory resolve
	 * cache so subsequent reads reflect the new state.
	 *
	 * @since 2.5.0
	 *
	 * @param array<string, mixed> $value The full settings tree to store.
	 * @return bool True on successful write, false if no change.
	 */
	public function update( array $value ): bool {
		$this->resolved = array();
		return $this->network
			? update_site_option( $this->option_name, $value )
			: update_option( $this->option_name, $value );
	}

	/**
	 * Set a value using dot notation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Dot notation key (e.g., 'cache.ttl'). Minimum 2 levels (module.key).
	 * @param mixed  $value The value to set.
	 *
	 * @return bool True if the value was set successfully or already stored.
	 */
	public function set( string $key, $value ): bool {
		$keys = explode( '.', $key );

		if ( count( $keys ) < 2 ) {
			return false;
		}

		$last_key = array_pop( $keys );
		$module   = array_shift( $keys );
		$settings = $this->resolve( null, true );

		if ( ! isset( $settings[ $module ] ) ) {
			$settings[ $module ] = array();
		}

		$ref = &$settings[ $module ];

		foreach ( $keys as $k ) {
			if ( ! isset( $ref[ $k ] ) || ! is_array( $ref[ $k ] ) ) {
				$ref[ $k ] = array();
			}
			$ref = &$ref[ $k ];
		}

		// update_option() returns false when the value is unchanged, which is
		// indistinguishable from a writing failure — treat a no-op set as success.
		if ( array_key_exists( $last_key, $ref ) && $ref[ $last_key ] === $value ) {
			return true;
		}

		$ref[ $last_key ] = $value;

		$this->resolved = array();

		return $this->network
			? update_site_option( $this->option_name, $settings )
			: update_option( $this->option_name, $settings );
	}

	// ─── Raw access (migration escape hatch) ────────────────────────────

	/**
	 * Read the stored row without the schema-strip filter.
	 *
	 * Decryption still runs. Reads from the current blog or network;
	 * wrap with switch_to_blog() to target another blog.
	 *
	 * @since 2.5.0
	 * @return array<string, mixed>
	 */
	public function read_raw(): array {
		if ( $this->standalone || ! function_exists( 'get_option' ) ) {
			return array();
		}

		$this->bypass_schema_filter = true;

		try {
			$value = $this->network
				? get_site_option( $this->option_name, array() )
				: get_option( $this->option_name, array() );
		} finally {
			$this->bypass_schema_filter = false;
		}

		return is_array( $value ) ? $value : array();
	}

	/**
	 * Read one key's raw stored value bypassing the defaults-gate.
	 *
	 * Internal value resolution only (e.g. during the `{slug}_settings_schema`
	 * filter, before defaults exist). Never use for REST/config output. Pairs
	 * with read_raw(); decryption and the network/site branch are inherited.
	 *
	 * Constants outrank the stored row and are checked by name, so a
	 * constant-only value resolves before its key is in the defaults.
	 *
	 * @since 2.6.2
	 * @since 2.8.0 Resolves constant overrides before the stored row.
	 *
	 * @param string $key      Dot-notation key.
	 * @param mixed  $fallback Returned when the key resolves nowhere.
	 *
	 * @return mixed
	 */
	public function get_raw( string $key, $fallback = null ) {
		$segments = explode( '.', $key );
		if ( count( $segments ) >= 2 ) {
			$module   = array_shift( $segments );
			$constant = $this->defined_constant_name( $module, implode( '_', $segments ) );

			if ( null !== $constant ) {
				return constant( $constant );
			}
		}

		$sentinel = "\0milli_get_raw_absent\0";
		$raw      = $this->dig( $this->read_raw(), $key, $sentinel );

		// Present in the stored row → return as-is, any type.
		if ( $sentinel !== $raw ) {
			return $raw;
		}

		// Absent from the row → defer to normal resolution.
		return $this->get( $key, $fallback );
	}

	// ─── Settings resolution ────────────────────────────────────────────

	/**
	 * Resolve merged settings from all sources with a priority hierarchy.
	 *
	 * Priority: Constants > Config File > Database > Defaults.
	 * Results are cached in memory for the current request.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module         Specific module to retrieve.
	 * @param bool        $skip_constants Whether to skip constants.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function resolve( ?string $module = null, bool $skip_constants = false ): array {
		$cache_key = ( $module ?? '__all__' ) . ( $skip_constants ? ':raw' : '' );

		if ( isset( $this->resolved[ $cache_key ] ) ) {
			return $this->resolved[ $cache_key ];
		}
		$settings = $this->get_default_settings( $module );

		// Merge from config file.
		$file_settings = $this->config_file ? $this->config_file->read( $module ) : array();

		// File-cached settings bypass `option_<name>` filters, so decrypt here if needed.
		if ( ! empty( $file_settings ) && $this->encryption ) {
			$file_settings = $this->decrypt_sensitive_settings_data( $file_settings );
		}

		// Prefer file over DB; the file is authoritative when present.
		$config_settings = ! empty( $file_settings ) ? $file_settings : $this->get_settings_from_db( $module );

		$settings = self::overlay_known( $settings, $config_settings );

		// Constants override.
		if ( ! $skip_constants && '' !== $this->constant_prefix ) {
			$constant_settings = $this->get_settings_from_constants( $module );
			foreach ( $constant_settings as $module_key => $module_settings ) {
				foreach ( $module_settings as $key => $value ) {
					$settings[ $module_key ][ $key ] = $value;
				}
			}
		}

		$this->resolved[ $cache_key ] = $settings;

		return $settings;
	}

	/**
	 * Overlay stored values onto a defaults-shaped tree, keeping only keys
	 * the defaults know (the defaults-gate).
	 *
	 * @since 2.8.0
	 *
	 * @param array<string, array<string, mixed>> $settings Defaults-shaped tree.
	 * @param array<string, mixed>                $values   Stored values to overlay.
	 * @return array<string, array<string, mixed>>
	 */
	private static function overlay_known( array $settings, array $values ): array {
		foreach ( $values as $module_key => $module_settings ) {
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				if ( isset( $settings[ $module_key ] ) && array_key_exists( $key, $settings[ $module_key ] ) ) {
					$settings[ $module_key ][ $key ] = $value;
				}
			}
		}

		return $settings;
	}

	/**
	 * Return defaults with add-on filters applied (cached in $resolved).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function defaults(): array {
		if ( isset( $this->resolved['__defaults__'] ) ) {
			return $this->resolved['__defaults__'];
		}

		$defaults = $this->defaults;

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter default settings.
			 *
			 * Allows add-on plugins to register additional setting modules
			 * and keys so they are recognized throughout the settings lifecycle.
			 *
			 * @since 1.0.0
			 * @since 2.6.0 Added $is_network argument.
			 *
			 * @param array<string, array<string, mixed>> $defaults   Default settings.
			 * @param bool                                $is_network Whether this Settings instance is network-scoped.
			 */
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- The hook prefix IS the consuming plugin's slug; per-plugin prefixing is the framework contract.
			$defaults = apply_filters( "{$this->slug}_settings_defaults", $defaults, $this->network );
		}

		$this->resolved['__defaults__'] = $defaults;

		return $defaults;
	}

	/**
	 * Get the default settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Specific module to retrieve.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_default_settings( ?string $module = null ): array {
		$defaults = $this->defaults();

		if ( $module ) {
			return isset( $defaults[ $module ] ) ? array( $module => $defaults[ $module ] ) : array();
		}

		return $defaults;
	}

	/**
	 * Get settings from wp-config.php constants.
	 *
	 * Builds constant names from the prefix, module key, and setting key via
	 * {@see self::defined_constant_name()} (e.g. `MC` + `storage` + `host`
	 * → `MC_STORAGE_HOST`).
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Specific module to retrieve.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_settings_from_constants( ?string $module = null ): array {
		if ( '' === $this->constant_prefix ) {
			return array();
		}

		$defaults = $this->defaults();
		$result   = array();

		$modules_to_check = $module ? array( $module => $defaults[ $module ] ?? array() ) : $defaults;

		foreach ( $modules_to_check as $module_key => $module_settings ) {
			foreach ( $module_settings as $key => $value ) {
				$constant = $this->defined_constant_name( (string) $module_key, (string) $key );

				if ( null !== $constant ) {
					$result[ $module_key ][ $key ] = constant( $constant );
				}
			}
		}

		return $result;
	}

	/**
	 * The defined constant overriding a module/key pair, or null when none is.
	 *
	 * Hyphens normalize to underscores (`object-cache` + `active` →
	 * `MC_OBJECT_CACHE_ACTIVE`), and encrypted fields also answer without the
	 * `ENC_` marker (`license.enc_key` → `MC_LICENSE_KEY`).
	 *
	 * @since 2.8.0
	 *
	 * @param string $module Module key.
	 * @param string $key    Setting key within the module.
	 * @return ?string
	 */
	private function defined_constant_name( string $module, string $key ): ?string {
		if ( '' === $this->constant_prefix ) {
			return null;
		}

		$constant   = str_replace( '-', '_', strtoupper( "{$this->constant_prefix}_{$module}_{$key}" ) );
		$candidates = array( $constant );

		if ( self::is_enc_key( $key ) ) {
			$candidates[] = str_replace( 'ENC_', '', $constant );
		}

		foreach ( $candidates as $candidate ) {
			if ( defined( $candidate ) ) {
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Get settings from the database.
	 *
	 * Returns an empty array in standalone mode or when WordPress is
	 * not loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module Specific module to retrieve.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_settings_from_db( ?string $module = null ): array {
		if ( $this->standalone || ! function_exists( 'get_option' ) ) {
			return array();
		}

		$db_settings = (array) ( $this->network
			? get_site_option( $this->option_name, array() )
			: get_option( $this->option_name, array() )
		);

		if ( $module ) {
			$module_settings = isset( $db_settings[ $module ] ) ? (array) $db_settings[ $module ] : array();
			return isset( $db_settings[ $module ] ) ? array( $module => $module_settings ) : array();
		}

		$result = array_map(
			function ( $setting ): array {
				return (array) $setting;
			},
			$db_settings
		);

		return $result;
	}

	/**
	 * Get the source of a setting value.
	 *
	 * Checks sources in priority order and returns the first match.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module The settings module.
	 * @param string $key    The setting key.
	 *
	 * @return string 'constant', 'file', 'db', or 'default'.
	 */
	public function get_source( string $module, string $key ): string {
		$constant_settings = $this->get_settings_from_constants( $module );
		if ( isset( $constant_settings[ $module ][ $key ] ) ) {
			return 'constant';
		}

		if ( $this->config_file ) {
			$file_settings = $this->config_file->read( $module );
			if ( isset( $file_settings[ $module ][ $key ] ) ) {
				return 'file';
			}
		}

		$db_settings = $this->get_settings_from_db( $module );
		if ( isset( $db_settings[ $module ][ $key ] ) ) {
			return 'db';
		}

		return 'default';
	}

	// ─── Filter settings by constants ───────────────────────────────────

	/**
	 * Filter settings: strip constant-defined keys and merge with defaults.
	 *
	 * Hooked into `option_{$name}` and `default_option_{$name}` to ensure
	 * the returned value is always a clean, schema-conformant array.
	 *
	 * @since 1.0.0
	 *
	 * @param false|array<string, array<string, mixed>> $settings The option value.
	 *
	 * @return false|array<string, array<string, mixed>>
	 */
	public function filter_settings_by_constants( $settings ) {
		if ( $this->bypass_schema_filter ) {
			return $settings;
		}

		if ( false === $settings ) {
			return false;
		}

		// @phpstan-ignore function.alreadyNarrowedType (defensive: another plugin may filter the option value to a non-array)
		if ( ! is_array( $settings ) ) {
			return array();
		}

		// Remove constant-defined keys from the stored value.
		$constant_settings = $this->get_settings_from_constants();
		foreach ( $constant_settings as $mod => $mod_settings ) {
			foreach ( $mod_settings as $key => $value ) {
				unset( $settings[ $mod ][ $key ] );
			}
		}

		// Merge with defaults: add missing keys, remove obsolete ones.
		$default_settings = $this->defaults();
		foreach ( $default_settings as $mod => $mod_settings ) {
			if ( ! isset( $settings[ $mod ] ) ) {
				$settings[ $mod ] = array();
			}

			// Add missing default keys.
			foreach ( $mod_settings as $key => $value ) {
				if ( ! isset( $settings[ $mod ][ $key ] ) && ! isset( $constant_settings[ $mod ][ $key ] ) ) {
					$settings[ $mod ][ $key ] = $value;
				}
			}

			// Remove obsolete keys.
			foreach ( $settings[ $mod ] as $key => $value ) {
				if ( ! array_key_exists( $key, $mod_settings ) ) {
					unset( $settings[ $mod ][ $key ] );
				}
			}
		}

		// Remove obsolete modules.
		foreach ( $settings as $mod => $mod_settings ) {
			if ( ! isset( $default_settings[ $mod ] ) ) {
				unset( $settings[ $mod ] );
			}
		}

		return $settings;
	}

	// ─── Encryption ─────────────────────────────────────────────────────

	/**
	 * Encrypt sensitive settings data (fields prefixed with 'enc_').
	 *
	 * @since 1.0.0
	 *
	 * @param false|array<string, array<string, mixed>> $settings The settings before saving.
	 *
	 * @return false|array<string, array<string, mixed>>
	 *
	 * @throws \Exception        If random bytes cannot be generated.
	 * @throws \SodiumException  If encryption fails.
	 */
	public function encrypt_sensitive_settings_data( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $module => $module_settings ) {
			// @phpstan-ignore function.alreadyNarrowedType (defensive: pre-save option value may be malformed at runtime)
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				if ( self::is_enc_key( $key ) && is_string( $value ) ) {
					$settings[ $module ][ $key ] = self::encrypt_value( $value );
				}
			}
		}

		return $settings;
	}

	/**
	 * Decrypt sensitive settings data (fields prefixed with 'enc_').
	 *
	 * Per-value failures are swallowed: a malformed ciphertext leaves the
	 * encrypted value in place rather than aborting the whole batch.
	 *
	 * @since 1.0.0
	 *
	 * @param false|array<string, array<string, mixed>> $settings The stored settings,
	 *        or `false` when the option does not exist.
	 *
	 * @return false|array<string, array<string, mixed>>
	 */
	public function decrypt_sensitive_settings_data( $settings ) {
		if ( $this->bypass_decryption || ! is_array( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $module => $module_settings ) {
			// @phpstan-ignore function.alreadyNarrowedType (defensive: stored option value may be malformed at runtime)
			if ( ! is_array( $module_settings ) ) {
				continue;
			}
			foreach ( $module_settings as $key => $value ) {
				if ( ! self::is_enc_key( $key ) || ! is_string( $value ) ) {
					continue;
				}
				try {
					$settings[ $module ][ $key ] = self::decrypt_value( $value );
				} catch ( \SodiumException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// Leave the encrypted value in place for this key.
				}
			}
		}

		return $settings;
	}

	/**
	 * Load WordPress's bundled sodium_compat polyfill when the native
	 * ext-sodium extension is unavailable.
	 *
	 * @since 2.8.0
	 */
	private static function maybe_load_sodium_compat(): void {
		if ( ! function_exists( 'sodium_crypto_secretbox' ) && defined( 'ABSPATH' ) ) {
			require_once ABSPATH . 'wp-includes/sodium_compat/autoload.php';
		}
	}

	/**
	 * Encrypt a value using sodium.
	 *
	 * Uses `AUTH_KEY` and `SECURE_AUTH_KEY` as the key material. Values
	 * already prefixed with `ENC:` are returned as-is.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The value to encrypt.
	 *
	 * @return string The encrypted value prefixed with 'ENC:'.
	 *
	 * @throws \Exception       If random bytes cannot be generated.
	 * @throws \SodiumException If encryption fails.
	 */
	public static function encrypt_value( string $value ): string {
		if ( empty( $value ) || strpos( $value, 'ENC:' ) === 0 ) {
			return $value;
		}

		self::maybe_load_sodium_compat();

		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$key   = sodium_crypto_generichash( AUTH_KEY . SECURE_AUTH_KEY, '', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES ); // @phpstan-ignore-line

		$encrypted = sodium_crypto_secretbox( $value, $nonce, $key );
		return 'ENC:' . base64_encode( $nonce . $encrypted );
	}

	/**
	 * Decrypt a value using sodium.
	 *
	 * Loads WordPress's bundled sodium_compat if the native extension
	 * is not available.
	 *
	 * @since 1.0.0
	 *
	 * @param string $encrypted_value The encrypted value.
	 *
	 * @return string The decrypted value, or empty string on failure.
	 *
	 * @throws \SodiumException If decryption fails.
	 */
	public static function decrypt_value( string $encrypted_value ): string {
		self::maybe_load_sodium_compat();

		if ( strpos( $encrypted_value, 'ENC:' ) !== 0 ) {
			return $encrypted_value;
		}

		$encrypted_value = substr( $encrypted_value, 4 );
		$key             = sodium_crypto_generichash( AUTH_KEY . SECURE_AUTH_KEY, '', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES ); // @phpstan-ignore-line
		$decoded         = base64_decode( $encrypted_value );

		$nonce      = mb_substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
		$ciphertext = mb_substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );

		$decrypted = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

		return false !== $decrypted ? $decrypted : '';
	}

	// ─── Backup / Restore ───────────────────────────────────────────────

	/**
	 * Back up current settings to a transient.
	 *
	 * There is one backup slot per scope, so a second call replaces the first.
	 *
	 * @since 1.0.0
	 * @since 2.9.0 Returns the expiry timestamp so callers can report it
	 *              instead of hard-coding the lifetime.
	 *
	 * @param string|null $module Specific module to back up, or null for all.
	 *
	 * @return int Unix timestamp the backup expires at, or 0 when nothing was stored.
	 */
	public function backup( ?string $module = null ): int {
		$current = $this->resolve( $module );

		if ( ! $current ) {
			return 0;
		}

		$key = $this->option_name . '_backup';
		if ( $this->network ) {
			set_site_transient( $key, $current, self::BACKUP_LIFETIME );
		} else {
			set_transient( $key, $current, self::BACKUP_LIFETIME );
		}

		return time() + self::BACKUP_LIFETIME;
	}

	/**
	 * Check if a backup exists.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_backup(): bool {
		$key = $this->option_name . '_backup';
		return (bool) ( $this->network ? get_site_transient( $key ) : get_transient( $key ) );
	}

	/**
	 * Restore settings from a backup transient.
	 *
	 * Deletes the transient after a successful restore.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if restored successfully.
	 */
	public function restore_backup(): bool {
		$key    = $this->option_name . '_backup';
		$backup = $this->network ? get_site_transient( $key ) : get_transient( $key );

		if ( ! $backup ) {
			return false;
		}

		if ( $this->network ) {
			update_site_option( $this->option_name, $backup );
			delete_site_transient( $key );
		} else {
			update_option( $this->option_name, $backup );
			delete_transient( $key );
		}

		return true;
	}

	/**
	 * Check if settings are at their defaults (ignoring constants).
	 *
	 * Preserve-flagged keys are stripped from both operands first: they hold
	 * identity/credential state that is orthogonal to configuration and that a
	 * full reset deliberately keeps, so a lingering license key must not make
	 * an otherwise-default install read as customized. See {@see self::reset()}.
	 *
	 * Constant-defined keys are stripped too: {@see self::reconcile_overrides()}
	 * mirrors them into the row, and a mirror must not read as customization.
	 *
	 * @since 1.0.0
	 * @since 2.6.4 Ignores preserve-flagged keys.
	 * @since 2.8.0 Ignores row values mirroring constants.
	 *
	 * @return bool
	 */
	public function has_default_settings(): bool {
		return $this->without_preserved_keys( $this->without_constant_keys( $this->resolve( null, true ) ) )
			=== $this->without_preserved_keys( $this->without_constant_keys( $this->get_default_settings() ) );
	}

	/**
	 * Return a copy of a settings tree with constant-defined keys removed.
	 *
	 * @since 2.8.0
	 *
	 * @param array<string, array<string, mixed>> $tree Settings tree to strip.
	 * @return array<string, array<string, mixed>>
	 */
	private function without_constant_keys( array $tree ): array {
		foreach ( $this->get_settings_from_constants() as $module => $keys ) {
			foreach ( array_keys( $keys ) as $key ) {
				unset( $tree[ $module ][ $key ] );
			}
		}

		return $tree;
	}

	/**
	 * Return a copy of a settings tree with preserve-flagged keys removed.
	 *
	 * @since 2.6.4
	 *
	 * @param array<string, array<string, mixed>> $tree Settings tree to strip.
	 * @return array<string, array<string, mixed>>
	 */
	private function without_preserved_keys( array $tree ): array {
		foreach ( $this->preserved_keys as $key ) {
			$parts = explode( '.', $key );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			unset( $tree[ $parts[0] ][ $parts[1] ] );
		}

		return $tree;
	}

	// ─── Reset ──────────────────────────────────────────────────────────

	/**
	 * Reset settings to defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $module The module to reset, or null for all.
	 *
	 * @return bool True if reset successfully.
	 */
	public function reset( ?string $module = null ): bool {
		// Snapshot current state before the destructive op so callers always
		// have a restore path; reset() is the canonical user-facing entry.
		$this->backup( $module );

		$this->resolved = array();

		// Full reset deletes the option so reads fall through to defaults
		// (matches REST __reset, keeps has_default_settings() consistent
		// and lets schema-supplied defaults stay live). Preserve-flagged keys
		// are captured first and re-stored as a minimal option after delete.
		if ( null === $module ) {
			$preserved = $this->capture_preserved_settings();
			$this->delete();

			if ( ! empty( $preserved ) ) {
				// The preserved values are unchanged, so suppress the change
				// hooks the write-back would otherwise fire as phantom "adds"
				// (config-file sync in on_add_option still runs).
				$this->suppress_change_hooks = true;
				try {
					if ( $this->network ) {
						update_site_option( $this->option_name, $preserved );
					} else {
						update_option( $this->option_name, $preserved );
					}
				} finally {
					$this->suppress_change_hooks = false;
				}
			}

			return true;
		}

		$settings = $this->resolve( null, true );
		$defaults = $this->get_default_settings( $module );
		if ( isset( $defaults[ $module ] ) ) {
			$settings[ $module ] = $defaults[ $module ];
		}

		return $this->network
			? (bool) update_site_option( $this->option_name, $settings )
			: update_option( $this->option_name, $settings );
	}

	/**
	 * Delete all settings (including files).
	 *
	 * @return void
	 */
	public function delete() {
		if ( $this->network ) {
			delete_site_option( $this->option_name );
		} else {
			delete_option( $this->option_name );
		}
	}

	/**
	 * Capture the stored values of preserve-flagged keys as a minimal tree.
	 *
	 * Reads the stored row (constant-defined keys live in their constant, not
	 * the option, so they are naturally skipped). A key is captured only when
	 * its stored value is non-empty and differs from its default — an empty or
	 * already-default value carries nothing a reset wouldn't restore anyway, so
	 * the written-back option stays minimal. Decryption runs via
	 * {@see self::read_raw()}, so the re-store path's encrypt filter re-encrypts
	 * enc_ values cleanly.
	 *
	 * @since 2.6.4
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function capture_preserved_settings(): array {
		if ( empty( $this->preserved_keys ) ) {
			return array();
		}

		$raw       = $this->read_raw();
		$defaults  = $this->get_default_settings();
		$preserved = array();

		foreach ( $this->preserved_keys as $key ) {
			$parts = explode( '.', $key );
			if ( count( $parts ) < 2 ) {
				continue;
			}

			$module = $parts[0];
			$field  = $parts[1];

			if ( ! isset( $raw[ $module ] ) || ! is_array( $raw[ $module ] ) || ! array_key_exists( $field, $raw[ $module ] ) ) {
				continue;
			}

			$value   = $raw[ $module ][ $field ];
			$default = $defaults[ $module ][ $field ] ?? null;

			if ( null === $value || '' === $value || $value === $default ) {
				continue;
			}

			$preserved[ $module ][ $field ] = $value;
		}

		return $preserved;
	}

	// ─── Import / Export ────────────────────────────────────────────────

	/**
	 * Export settings.
	 *
	 * `$secrets` decides what happens to every enc_ field:
	 *
	 *  - `strip`   (default) removes the key entirely.
	 *  - `mask`    keeps the key; a stored secret reads back as
	 *              {@see self::SECRET_MASK}, an unset one as an empty string.
	 *              Nothing is decrypted — the resolved tree holds ciphertext
	 *              and {@see self::mask_secret()} full-masks any `ENC:` value.
	 *  - `decrypt` returns the plaintext.
	 *
	 * Use `mask` for anything that leaves the site: `strip` cannot express the
	 * difference between "not configured" and "hidden", because the key is not
	 * there either way.
	 *
	 * @since 1.0.0
	 * @since 2.9.0 `$secrets` replaces the `$include_encrypted` boolean, which
	 *              is still accepted (`true` = `decrypt`, `false` = `strip`).
	 *
	 * @param string|null $module  Module to export, or null for all.
	 * @param bool|string $secrets One of `strip`, `mask`, `decrypt`; or a legacy boolean.
	 *
	 * @return array<string, mixed>
	 */
	public function export( ?string $module = null, $secrets = 'strip' ): array {
		if ( is_bool( $secrets ) ) {
			$secrets = $secrets ? 'decrypt' : 'strip';
		}

		$settings = $this->resolve( $module, true );

		if ( 'mask' === $secrets ) {
			return $this->redact_secrets( $settings );
		}

		foreach ( $settings as $module_key => $module_settings ) {
			foreach ( $module_settings as $key => $value ) {
				if ( ! self::is_enc_key( $key ) ) {
					continue;
				}

				if ( 'decrypt' === $secrets && is_string( $value ) ) {
					$settings[ $module_key ][ $key ] = self::decrypt_value( $value );
				} elseif ( 'decrypt' !== $secrets ) {
					unset( $settings[ $module_key ][ $key ] );
				}
			}
		}

		return $settings;
	}

	/**
	 * Import settings from an array.
	 *
	 * Only modules present in the defaults are accepted; unknown modules
	 * are silently discarded.
	 *
	 * Two guards apply to every import, so no caller has to remember them:
	 *
	 *  - Masked secrets never reach storage. A `mask` export reads enc_ fields
	 *    back as {@see self::SECRET_MASK}, and importing that verbatim would
	 *    overwrite a real password with bullet characters, unrecoverably —
	 *    {@see self::preserve_secret_writes()} restores the stored value.
	 *  - The current settings are backed up first, so a bad import can be
	 *    rolled back with {@see self::restore_backup()}, as with
	 *    {@see self::reset()}.
	 *
	 * A `strip` export omits enc_ fields entirely. Merging leaves the stored
	 * secrets alone, but replacing drops them along with everything else the
	 * export left out — export with `mask` when the result is meant to be
	 * imported back.
	 *
	 * @since 1.0.0
	 * @since 2.9.0 Preserves masked secrets and backs up before writing.
	 *
	 * @param array<string, mixed> $settings The settings to import.
	 * @param bool                 $merge    Whether to merge with existing.
	 *
	 * @return bool True if imported successfully.
	 */
	public function import( array $settings, bool $merge = true ): bool {
		$valid_modules     = array_keys( $this->defaults() );
		$filtered_settings = array();

		foreach ( $settings as $module => $module_settings ) {
			if ( in_array( $module, $valid_modules, true ) && is_array( $module_settings ) ) {
				$filtered_settings[ $module ] = $module_settings;
			}
		}

		if ( empty( $filtered_settings ) ) {
			return false;
		}

		$filtered_settings = $this->preserve_secret_writes( $filtered_settings );

		$this->backup();

		if ( $merge ) {
			$current = $this->resolve( null, true );
			foreach ( $filtered_settings as $module => $module_settings ) {
				if ( ! is_array( $module_settings ) ) {
					continue;
				}
				if ( ! isset( $current[ $module ] ) ) {
					$current[ $module ] = array();
				}
				$current[ $module ] = array_merge( $current[ $module ], $module_settings );
			}
			$filtered_settings = $current;
		}

		$this->resolved = array();

		return update_option( $this->option_name, $filtered_settings );
	}

	// ─── Utilities ──────────────────────────────────────────────────────

	/**
	 * Coerce a string value to its appropriate PHP type.
	 *
	 * Converts `'true'`/`'false'`/`'null'` to their native types, and
	 * numeric strings to int or float.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The string value.
	 *
	 * @return mixed The coerced value.
	 */
	public static function coerce_value( string $value ) {
		$lower = strtolower( $value );

		if ( 'true' === $lower ) {
			return true;
		}
		if ( 'false' === $lower ) {
			return false;
		}
		if ( 'null' === $lower ) {
			return null;
		}
		if ( is_numeric( $value ) && strpos( $value, '.' ) === false ) {
			return (int) $value;
		}
		if ( is_numeric( $value ) && strpos( $value, '.' ) !== false ) {
			return (float) $value;
		}

		return $value;
	}

	/**
	 * Recursively diff two nested arrays into flat dot-notation changes.
	 *
	 * Returns an associative array keyed by dot-notation paths, each containing
	 * `['old' => …, 'new' => …]` for every leaf value that changed. Additions
	 * have `old => null`, removals have `new => null`.
	 *
	 * @since 1.3.0
	 *
	 * @param array<array-key, mixed> $old_data The old settings array.
	 * @param array<array-key, mixed> $new_data The new settings array.
	 * @param string                  $prefix   Internal dot-notation prefix for recursion.
	 *
	 * @return array<string, array{old: mixed, new: mixed}>
	 */
	public static function flatten_diff( array $old_data, array $new_data, string $prefix = '' ): array {
		$changes  = array();
		$all_keys = array_unique( array_merge( array_keys( $old_data ), array_keys( $new_data ) ) );

		foreach ( $all_keys as $key ) {
			$dot_key = '' === $prefix ? (string) $key : $prefix . '.' . $key;
			$old_val = array_key_exists( $key, $old_data ) ? $old_data[ $key ] : null;
			$new_val = array_key_exists( $key, $new_data ) ? $new_data[ $key ] : null;

			$old_is_array = is_array( $old_val );
			$new_is_array = is_array( $new_val );

			if ( $old_is_array && $new_is_array ) {
				$changes = array_merge( $changes, self::flatten_diff( $old_val, $new_val, $dot_key ) );
			} elseif ( null === $old_val && $new_is_array ) {
				// Addition of a new branch — recurse with empty old.
				$changes = array_merge( $changes, self::flatten_diff( array(), $new_val, $dot_key ) );
			} elseif ( $old_is_array && null === $new_val ) {
				// Removal of a branch — recurse with empty new.
				$changes = array_merge( $changes, self::flatten_diff( $old_val, array(), $dot_key ) );
			} elseif ( $old_val !== $new_val ) {
				$changes[ $dot_key ] = array(
					'old' => $old_val,
					'new' => $new_val,
				);
			}
		}

		return $changes;
	}

	/**
	 * Get the option name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_option_name(): string {
		return $this->option_name;
	}

	// ─── Config file hook callbacks ─────────────────────────────────────

	/**
	 * Handle add_option hook — sync newly created options to the config file.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $option   The option name.
	 * @param array<mixed> $settings The settings value.
	 *
	 * @return void
	 */
	public function on_add_option( string $option, array $settings ): void {
		$this->resolved = array();

		if ( $this->config_file && ! $this->suppress_file_sync ) {
			$this->record_config_sync( $this->config_file->write( $settings ) );
		}

		$this->fire_setting_changed_hooks( array(), $settings );
	}

	/**
	 * Handle update_option hook — sync updated options to the config file.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $old_settings The old value.
	 * @param array<mixed> $settings     The new value.
	 *
	 * @return void
	 */
	public function on_update_option( array $old_settings, array $settings ): void {
		$this->resolved = array();

		if ( $this->config_file && ! $this->suppress_file_sync ) {
			$this->record_config_sync( $this->config_file->write( $settings ) );
		}

		$this->fire_setting_changed_hooks( $old_settings, $settings );
	}

	/**
	 * Handle the delete_option hook — remove the config file when the option is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option The option name.
	 *
	 * @return void
	 */
	public function on_delete_option( string $option ): void {
		if ( $option !== $this->option_name ) {
			return;
		}

		$this->resolved = array();

		if ( $this->config_file ) {
			// A file surviving the delete stays authoritative and would let
			// reconcile_overrides() resurrect the deleted settings — mark it.
			$this->config_file->delete();
			$this->record_config_sync( ! $this->config_file->exists() );
		}
	}

	// ─── Override reconciliation ────────────────────────────────────────

	/**
	 * Apply constant/config-file overrides as if they were written: fire the
	 * standard `{slug}_setting_changed` events for every drifted key, then
	 * sync the values into the row — the "last applied" memory — so each
	 * change applies exactly once. The row write neither re-fires the events
	 * nor rewrites the config file (a deployed file must stay untouched).
	 *
	 * Wired to `admin_init` by the Manager; only defaults-known keys are
	 * considered, so a pre-defaults call is a safe no-op.
	 *
	 * @since 2.8.0
	 *
	 * @return void
	 */
	public function reconcile_overrides(): void {
		if ( $this->standalone || ! function_exists( 'update_option' ) ) {
			return;
		}

		// A failed sync left the file STALE, not deployed — reconciling
		// against it would regress the row. Heal first; while that fails, bail.
		if ( null !== $this->config_sync_failed_at() && ! $this->heal_config_file() ) {
			return;
		}

		$stored    = $this->read_raw();
		$base      = self::overlay_known( $this->get_default_settings(), $stored );
		$effective = $this->resolve();

		$changes = self::flatten_diff( $base, $effective );
		if ( array() === $changes ) {
			return;
		}

		// Sync at module.key granularity so changed sub-arrays land whole.
		$row = $stored;
		foreach ( array_keys( $changes ) as $dot_key ) {
			$parts = explode( '.', $dot_key );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			list( $module, $key ) = $parts;

			if ( ! isset( $row[ $module ] ) || ! is_array( $row[ $module ] ) ) {
				$row[ $module ] = array();
			}
			$row[ $module ][ $key ] = $effective[ $module ][ $key ] ?? null;
		}

		$this->suppress_change_hooks = true;
		$this->suppress_file_sync    = true;
		try {
			$this->update( $row );
		} finally {
			$this->suppress_change_hooks = false;
			$this->suppress_file_sync    = false;
		}

		// Fire after the write (native order): listener writes land on top and win.
		$this->fire_setting_changed_hooks( $base, $effective );
	}

	/**
	 * Unix timestamp of the last failed config-file sync, or null when healthy.
	 *
	 * @since 2.8.0
	 *
	 * @return ?int
	 */
	public function config_sync_failed_at(): ?int {
		if ( $this->standalone || ! function_exists( 'get_option' ) ) {
			return null;
		}

		$at = $this->network
			? get_site_option( $this->config_sync_marker_name(), null )
			: get_option( $this->config_sync_marker_name(), null );

		return is_numeric( $at ) ? (int) $at : null;
	}

	/**
	 * Record a config-file sync outcome: failure sets the marker, success
	 * clears it.
	 *
	 * @since 2.8.0
	 *
	 * @param bool $synced Whether the file now reflects the row.
	 */
	private function record_config_sync( bool $synced ): void {
		if ( ! $synced ) {
			if ( $this->network ) {
				update_site_option( $this->config_sync_marker_name(), time() );
			} else {
				update_option( $this->config_sync_marker_name(), time() );
			}
			return;
		}

		if ( null !== $this->config_sync_failed_at() ) {
			if ( $this->network ) {
				delete_site_option( $this->config_sync_marker_name() );
			} else {
				delete_option( $this->config_sync_marker_name() );
			}
		}
	}

	/**
	 * Marker option name for a failed config-file sync.
	 *
	 * @since 2.8.0
	 */
	private function config_sync_marker_name(): string {
		return $this->option_name . '_config_sync_failed';
	}

	/**
	 * Retry the config-file sync: rewrite the file from the row's at-rest
	 * form, or delete it when no row exists; clears the marker on success.
	 * Deliberately last-writer-wins — a file deployed during a marked window
	 * is overwritten by the row state.
	 *
	 * @since 2.8.0
	 *
	 * @return bool Whether the file now reflects the row.
	 */
	private function heal_config_file(): bool {
		if ( ! $this->config_file ) {
			$this->record_config_sync( true );
			return true;
		}

		$stored = $this->read_stored();

		if ( array() === $stored ) {
			$this->config_file->delete();
			$healed = ! $this->config_file->exists();
		} else {
			$healed = $this->config_file->write( $stored );
		}

		$this->record_config_sync( $healed );

		return $healed;
	}

	/**
	 * The stored row exactly as persisted — no stripping, no decryption —
	 * so the config-file heal never writes plaintext secrets.
	 *
	 * @since 2.8.0
	 *
	 * @return array<string, mixed>
	 */
	private function read_stored(): array {
		if ( $this->standalone || ! function_exists( 'get_option' ) ) {
			return array();
		}

		$this->bypass_schema_filter = true;
		$this->bypass_decryption    = true;

		try {
			$value = $this->network
				? get_site_option( $this->option_name, array() )
				: get_option( $this->option_name, array() );
		} finally {
			$this->bypass_schema_filter = false;
			$this->bypass_decryption    = false;
		}

		return is_array( $value ) ? $value : array();
	}

	// ─── Setting change notifications ──────────────────────────────────

	/**
	 * Diff old vs new settings and fire per-key and general change actions.
	 *
	 * @since 1.3.0
	 *
	 * @param array<string, mixed> $old_settings The previous settings.
	 * @param array<string, mixed> $new_settings The updated settings.
	 *
	 * @return void
	 */
	private function fire_setting_changed_hooks( array $old_settings, array $new_settings ): void {
		if ( $this->suppress_change_hooks ) {
			return;
		}

		$changes = self::flatten_diff( $old_settings, $new_settings );

		if ( empty( $changes ) ) {
			return;
		}

		foreach ( $changes as $key => $change ) {
			/**
			 * Fires when an individual setting key changes.
			 *
			 * @since 1.3.0
			 *
			 * @param mixed  $new_value The new value (null if removed).
			 * @param mixed  $old_value The old value (null if added).
			 * @param string $key       The dot-notation key that changed.
			 */
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- The hook prefix IS the consuming plugin's slug; per-plugin prefixing is the framework contract.
			do_action( "{$this->slug}_setting_changed/{$key}", $change['new'], $change['old'], $key );
		}

		/**
		 * Fires once after settings are saved, if any keys changed.
		 *
		 * @since 1.3.0
		 *
		 * @param array<string, array{old: mixed, new: mixed}> $changes      All changed keys.
		 * @param array<string, mixed>                         $new_settings The full new settings.
		 * @param array<string, mixed>                         $old_settings The full old settings.
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- The hook prefix IS the consuming plugin's slug; per-plugin prefixing is the framework contract.
		do_action( "{$this->slug}_setting_changed", $changes, $new_settings, $old_settings );
	}

	// ─── Private helpers ────────────────────────────────────────────────

	/**
	 * Resolve the per-blog identifier used for the config file name.
	 *
	 * Called on every read/write so multisite blog switches are honoured.
	 * `home_url()` is preferred when WP is loaded — it is blog-aware via
	 * `switch_to_blog()` and matches the visitor-facing host (so it agrees
	 * with `$_SERVER['HTTP_HOST']` and excludes Bedrock-style WP install
	 * paths). We fall back to `$_SERVER['HTTP_HOST']` only for standalone /
	 * pre-WP reads.
	 *
	 * @since 1.0.0
	 * @since 2.4.3 Subdirectory multisite appends the blog's path segment.
	 *
	 * @return string
	 */
	private function resolve_domain(): string {
		if ( $this->network ) {
			if ( function_exists( 'get_network' ) ) {
				$network_id = (int) get_current_network_id();
			} elseif ( defined( 'SITE_ID_CURRENT_SITE' ) ) {
				$network_id = (int) SITE_ID_CURRENT_SITE;
			} else {
				$network_id = 1;
			}
			return '_network-' . $network_id;
		}

		$host = '';
		$path = '';
		$mode = self::multisite_mode();

		if ( function_exists( 'home_url' ) ) {
			$parsed = wp_parse_url( home_url() );
			$host   = $parsed['host'] ?? '';
			if ( 'subdirectory' === $mode ) {
				$path = trim( (string) ( $parsed['path'] ?? '' ), '/' );
			}
		} elseif ( ServerVars::has( 'HTTP_HOST' ) ) {
			$host = ServerVars::get( 'HTTP_HOST' );
			if ( 'subdirectory' === $mode && ServerVars::has( 'REQUEST_URI' ) ) {
				$segments = explode( '/', ServerVars::get( 'REQUEST_URI' ), 3 );
				$path     = $segments[1] ?? '';
			}
		}

		$identifier = '' === $path ? $host : $host . '/' . $path;

		return (string) preg_replace( '/[^a-zA-Z0-9_\-]/', '_', $identifier );
	}

	/**
	 * Identify the multisite mode, working pre-WP and in WP context.
	 *
	 * Pre-WP relies on the `MULTISITE` and `SUBDOMAIN_INSTALL` constants
	 * defined by `wp-config.php`; WP context uses `is_multisite()`.
	 *
	 * @since 2.4.3
	 *
	 * @return 'subdomain'|'subdirectory'|null
	 */
	private static function multisite_mode(): ?string {
		$is_multisite = function_exists( 'is_multisite' )
			? is_multisite()
			// @phpstan-ignore phpstanWP.wpConstant.fetch (intentional non-WP fallback; WP context uses is_multisite() above)
			: ( defined( 'MULTISITE' ) && MULTISITE );

		if ( ! $is_multisite ) {
			return null;
		}

		// @phpstan-ignore phpstanWP.wpConstant.fetch (intentional non-WP fallback for subdomain detection)
		return ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL )
			? 'subdomain'
			: 'subdirectory';
	}
}
