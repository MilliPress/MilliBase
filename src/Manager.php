<?php
/**
 * Settings manager — the main entry point for consuming plugins.
 *
 * Usage:
 *   // Minimal — Manager creates its own Settings on init:
 *   $manager = new \MilliBase\Manager(
 *       slug: 'milliplugin',
 *       config: fn() => [
 *           'tabs' => [ ... ],
 *           // ... full config array with __() calls
 *       ],
 *   );
 *
 *   // With a pre-built Settings singleton — enables early access
 *   // (schema defaults are merged at construction time, before init):
 *   $manager = new \MilliBase\Manager(
 *       slug: 'milliplugin',
 *       config: fn() => [ ... ],
 *       settings: Settings::instance(),
 *   );
 *   $manager->settings()->get('cache.ttl'); // works immediately
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

use MilliBase\Abilities\Controller as AbilitiesController;
use MilliBase\CLI as CliController;
use MilliBase\Concerns\HasConfig;
use MilliBase\Migration\Runner as MigrationRunner;
use MilliBase\REST\Controller as RestController;
use MilliBase\Settings\Group as SettingsGroup;

/**
 * Orchestrator that wires Settings + Schema + AdminPage + REST\Controller + CLI\Controller + Abilities\Controller together.
 *
 * Accepts a Closure that returns the full configuration array. The closure
 * is called on `init`, so translation functions like __() execute after the
 * textdomain has been loaded.
 *
 * At construction time, schema-derived defaults (field defaults, active-toggle
 * keys) are extracted via the `{slug}_settings_schema` filter and merged into
 * the provided Settings instance — making them available before `init`.
 *
 * @since 1.0.0
 * @since 2.0.0 Constructor accepts a Closure instead of an array.
 */
final class Manager {

	use HasConfig;

	/**
	 * The plugin slug, used for filters and auto-derived config keys.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $slug;

	/**
	 * The Settings instance. Cross-prefix tolerant; do not add a native type.
	 * Any consumer of this value must follow the same rule.
	 * See docs/04-reference/04-namespace-prefixing.md.
	 *
	 * @noinspection PhpMissingFieldTypeInspection
	 *
	 * @since 1.0.0
	 * @var Settings|null
	 */
	private $settings;

	/**
	 * The resolved configuration array.
	 *
	 * Populated when the config closure is called during initialize().
	 *
	 * @since 1.0.0
	 * @var array<string, mixed>
	 */
	private array $config = array();

	/**
	 * The Schema instance.
	 *
	 * @since 1.0.0
	 * @var Schema|null
	 */
	private ?Schema $schema = null;

	/**
	 * The AdminPage instance.
	 *
	 * @since 1.0.0
	 * @var AdminPage|null
	 */
	private ?AdminPage $admin_page = null;

	/**
	 * The RestController instance.
	 *
	 * @since 1.0.0
	 * @var RestController|null
	 */
	private ?RestController $rest_controller = null;

	/**
	 * The CliController instance.
	 *
	 * @since 1.2.0
	 * @var CliController|null
	 */
	private ?CliController $cli_controller = null;

	/**
	 * The AbilitiesController instance.
	 *
	 * @since 2.5.0
	 * @var AbilitiesController|null
	 */
	private ?AbilitiesController $abilities_controller = null;

	/**
	 * Whether initialize() has run.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Slug-keyed registry of Settings\Group instances.
	 *
	 * @since 2.5.0
	 * @var array<string, SettingsGroup>
	 */
	private static array $settings_groups = array();

	/**
	 * Registered Manager fingerprints — `<slug>:<network>` — for collision detection.
	 *
	 * @since 2.5.0
	 * @var array<string, true>
	 */
	private static array $registered_fingerprints = array();

	/**
	 * Create a new Manager instance.
	 *
	 * The config closure is called on `init` (or immediately if `init` has
	 * already fired). Schema-derived defaults are extracted at construction
	 * time and merged into the provided Settings instance, so they are
	 * available before `init`.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Accepts a Closure for deferred config resolution.
	 *
	 * @param string        $slug     The plugin slug (used for filters and option_name).
	 * @param \Closure      $config   Returns the full settings configuration array.
	 * @param Settings|null $settings Pre-built Settings instance. When null, one is
	 *                                created from the resolved config during initialize().
	 */
	public function __construct(
		string $slug,
		\Closure $config,
		$settings = null
	) {
		$this->slug     = $slug;
		$this->settings = $settings;

		// Empty slug breaks every downstream component (Settings throws,
		// abilities-api regex fails, AdminPage cannot register). Bail
		// out cleanly so the host site does not 500.
		if ( '' === $slug ) {
			if ( function_exists( '_doing_it_wrong' ) ) {
				_doing_it_wrong(
					__METHOD__,
					esc_html__( 'MilliBase\\Manager requires a non-empty slug; nothing will be registered for this consumer.', 'millibase' ),
					'2.5.0'
				);
			}
			return;
		}

		$this->merge_early_defaults();

		if ( function_exists( 'did_action' ) && did_action( 'init' ) ) {
			$this->initialize( $config );
			$this->boot();
		} elseif ( function_exists( 'add_action' ) ) {
			add_action(
				'init',
				function () use ( $config ) {
					$this->initialize( $config );
					$this->boot();
				},
				0
			);
		}
	}

	/**
	 * Register all WordPress integrations.
	 *
	 * Hooked to `init` (priority 0) when the Manager is created before
	 * `init`, or called immediately when created on/after `init`.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function boot(): void {
		$schema   = $this->schema;
		$settings = $this->settings;

		if ( ! function_exists( 'add_action' ) || null === $schema || null === $settings ) {
			return;
		}

		$this->guard_against_slug_collision();

		$this->register_settings();

		$this->register_migrations();

		$this->register_cli( $settings );

		$this->admin_page = new AdminPage( $this->config, $schema );
		$this->admin_page->register_hooks();

		$this->rest_controller = new RestController( $this->config, $settings );
		$this->rest_controller->register_hooks();

		$this->register_abilities( $settings );

		// Apply constant/config-file overrides as writes (fires the standard
		// change events once per drift) — after migrations, on admin_init.
		if ( function_exists( 'did_action' ) && did_action( 'admin_init' ) ) {
			$settings->reconcile_overrides();
		} else {
			add_action( 'admin_init', array( $settings, 'reconcile_overrides' ) );
		}
	}

	/**
	 * Register the Schema sanitize callback on the writer-specific filter.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings(): void {
		$schema   = $this->schema;
		$settings = $this->settings;

		if ( null === $settings || null === $schema ) {
			return;
		}

		$option_name = $this->config_string( 'option_name' );
		$defaults    = $settings->get_default_settings();
		$sanitize    = static fn( $values ) => $schema->sanitize( $values, $defaults );

		add_filter(
			! empty( $this->config['network'] ) ? "pre_update_site_option_{$option_name}" : "pre_update_option_{$option_name}",
			$sanitize,
			-100
		);
	}

	/**
	 * Register the CLI controller for this Manager, with auto-merge.
	 *
	 * When two Managers share the primary slug (typically a site + network
	 * split), the second Manager's Settings appends to the existing Group
	 * instead of attempting a duplicate `WP_CLI::add_command` call.
	 * Operators get one `wp <slug> config` command tree; each subcommand's
	 * `--network` flag picks the right Settings at call time.
	 *
	 * Configurable shape:
	 *   'cli' => false              → skip CLI registration entirely
	 *   'cli' => true | (omitted)   → register under `<slug> config`
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @param Settings $settings This Manager's Settings instance.
	 * @return void
	 */
	private function register_cli( $settings ): void {
		if ( false === ( $this->config['cli'] ?? true ) ) {
			return;
		}

		// Multi-Manager auto-merge — when two Managers share the primary slug.
		if ( isset( self::$settings_groups[ $this->slug ] ) ) {
			self::$settings_groups[ $this->slug ]->add( $settings );
			return;
		}

		$group                                = new SettingsGroup( $settings );
		self::$settings_groups[ $this->slug ] = $group;

		$this->cli_controller = new CliController( $this->config, $group );
		$this->cli_controller->register_hooks();
	}

	/**
	 * Look up the Settings\Group registered under a slug.
	 *
	 * Returns the slug's registry of scoped Settings instances (one per
	 * Manager sharing the slug — typically site + network). Call
	 * `$group->resolve( bool $wants_network )` to pick a specific
	 * Settings; null is returned when no Manager registered a group
	 * under `$slug` (e.g. `'cli' => false` skips registration today).
	 *
	 * @since 2.6.0
	 *
	 * @param string $slug Plugin slug.
	 * @return SettingsGroup|null
	 */
	public static function settings_group_for( string $slug ): ?SettingsGroup {
		return self::$settings_groups[ $slug ] ?? null;
	}

	/**
	 * Warn when a second Manager registers under the same slug + network combo.
	 *
	 * The intended multi-Manager pattern is one per-site + one network Manager
	 * sharing a slug. Two per-site Managers (or two network Managers) sharing
	 * a slug collide on `option_name`, `rest_namespace`, and AdminPage menu
	 * slug — the host plugin almost certainly didn't mean that.
	 *
	 * @since 2.5.0
	 *
	 * @return void
	 */
	private function guard_against_slug_collision(): void {
		$fingerprint = $this->slug . ':' . ( ! empty( $this->config['network'] ) ? '1' : '0' );

		if ( isset( self::$registered_fingerprints[ $fingerprint ] ) && function_exists( '_doing_it_wrong' ) ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html(
					sprintf(
						/* translators: 1: slug, 2: network-mode value (true/false). */
						__( 'A MilliBase Manager is already registered for slug "%1$s" with network=%2$s. Two Managers sharing a primary slug must differ in `network` mode (one per-site, one network). Use distinct slugs otherwise.', 'millibase' ),
						$this->slug,
						! empty( $this->config['network'] ) ? 'true' : 'false'
					)
				),
				'2.5.0'
			);
		}

		self::$registered_fingerprints[ $fingerprint ] = true;
	}

	/**
	 * Register the abilities controller for this Manager.
	 *
	 * Per-Manager registration — each Manager registers its own framework
	 * abilities scoped to its own Settings. The network Manager's ability
	 * IDs are suffixed with `-network` by {@see Abilities\Framework}
	 * so site and network surfaces never collide on the same ID.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 *
	 * @since 2.5.0
	 *
	 * @param Settings $settings This Manager's Settings instance.
	 * @return void
	 */
	private function register_abilities( $settings ): void {
		$this->abilities_controller = new AbilitiesController( $this->config, $settings );
		$this->abilities_controller->register_hooks();
	}

	/**
	 * Schedule the declarative migration list (if any) to run on `init`.
	 *
	 * Runs at `init` priority 5 so migrations complete after MilliBase's
	 * own setup (priority 0) but before most plugin code (default 10) —
	 * downstream code reads already-migrated state.
	 *
	 * When the Manager is constructed after `init` has fired, the runner
	 * is invoked immediately rather than hooked.
	 *
	 * @since 2.5.0
	 *
	 * @return void
	 */
	private function register_migrations(): void {
		$migrations = $this->config['migrations'] ?? null;
		if ( ! is_array( $migrations ) || empty( $migrations ) ) {
			return;
		}

		$slug    = $this->slug;
		$manager = $this;

		$run = static function () use ( $slug, $migrations, $manager ): void {
			( new MigrationRunner( $slug, $migrations, $manager ) )->run();
		};

		if ( function_exists( 'did_action' ) && did_action( 'init' ) ) {
			$run();
		} else {
			add_action( 'init', $run, 5 );
		}
	}

	/**
	 * Register an additional admin page backed by this Manager's Settings instance.
	 *
	 * Useful when a plugin needs a second admin surface — e.g., a Network
	 * Admin page on multisite — that shares the same WP option / settings
	 * file but presents a different field subset, menu placement, or REST
	 * namespace. The `register_setting` call is NOT repeated; only the
	 * UI & REST surfaces are added.
	 *
	 * @since 2.5.0
	 *
	 * @param array<string, mixed> $config Full MilliBase config for the additional page.
	 * @return void
	 */
	public function add_page( array $config ): void {
		if ( null === $this->settings ) {
			return;
		}

		$schema = new Schema( $config );

		( new AdminPage( $config, $schema ) )->register_hooks();
		( new RestController( $config, $this->settings ) )->register_hooks();
	}

	/**
	 * Get the Settings instance for programmatic settings access.
	 *
	 * When a Settings instance was passed to the constructor, it is
	 * available immediately (before `init`). Otherwise, this method
	 * throws until initialize() has run.
	 *
	 * @since 1.0.0
	 *
	 * @throws \LogicException If no Settings instance is available yet.
	 *
	 * @return Settings
	 */
	public function settings(): Settings {
		if ( null === $this->settings ) {
			throw new \LogicException( 'Settings not available. Pass a Settings instance to the constructor or wait until after init.' );
		}
		return $this->settings;
	}

	/**
	 * Get the Schema instance.
	 *
	 * Only available after `init` when the config closure has been resolved.
	 *
	 * @since 1.0.0
	 *
	 * @throws \LogicException If called before init.
	 *
	 * @return Schema
	 */
	public function schema(): Schema {
		if ( null === $this->schema ) {
			throw new \LogicException( 'Schema is available after init.' );
		}
		return $this->schema;
	}

	/**
	 * Whether the WordPress Abilities API is loaded on this site.
	 *
	 * Mirrors the soft-detect check used internally by `Abilities\Controller`,
	 * so consumer plugins can gate their own UI (admin pointers, MCP-related
	 * settings) on the same condition without grepping for `wp_register_ability`
	 * themselves.
	 *
	 * @since 2.5.0
	 *
	 * @return bool
	 */
	public function abilities_active(): bool {
		return function_exists( 'wp_register_ability' );
	}

	/**
	 * Extract schema-derived defaults and merge them into the Settings instance.
	 *
	 * Called at construction time (before `init`). Fires the
	 * `{slug}_settings_schema` filter with a minimal config to collect
	 * add-on schema extensions, then extracts field defaults and
	 * active-toggle keys.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	private function merge_early_defaults(): void {
		if ( null === $this->settings ) {
			return;
		}

		$config = function_exists( 'apply_filters' )
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- The hook prefix IS the consuming plugin's slug; per-plugin prefixing is the framework contract.
			? apply_filters( "{$this->slug}_settings_schema", array( 'tabs' => array() ), $this->settings->is_network() )
			: array( 'tabs' => array() );

		$schema   = new Schema( $config );
		$defaults = $schema->get_defaults();

		if ( ! empty( $defaults ) ) {
			$this->settings->merge_defaults( $defaults );
		}

		$this->settings->merge_preserved_keys( $schema->get_preserved_keys() );
	}

	/**
	 * Resolve the config closure and initialize all components.
	 *
	 * Called on `init` (or immediately if `init` has already fired).
	 *
	 * @since 2.0.0
	 *
	 * @param \Closure $config_resolver Returns the full settings configuration array.
	 *
	 * @return void
	 */
	private function initialize( \Closure $config_resolver ): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		$config = $config_resolver();

		$config['slug'] ??= $this->slug;

		if ( ! isset( $config['option_name'] ) ) {
			$config['option_name'] = $this->slug;
		}
		if ( ! isset( $config['rest_namespace'] ) ) {
			$config['rest_namespace'] = $this->slug . '/v1';
		}

		$this->config   = $config;
		$this->schema   = $this->resolve_schema();
		$this->settings = $this->resolve_settings( $this->schema, $this->settings );
	}

	/**
	 * Create and optionally filter the Schema from the configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return Schema
	 */
	private function resolve_schema(): Schema {
		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filters the settings configuration before Schema initialization.
			 *
			 * @param array<string, mixed> $config     The full settings configuration array.
			 * @param bool                 $is_network Whether this MilliBase Manager runs in network mode.
			 */
			$this->config = apply_filters(
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- The hook prefix IS the consuming plugin's slug; per-plugin prefixing is the framework contract.
				"{$this->slug}_settings_schema",
				$this->config,
				! empty( $this->config['network'] )
			);
		}

		return new Schema( $this->config );
	}

	/**
	 * Resolve the Settings: use an existing instance or build one from the schema.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpMissingReturnTypeInspection
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Accepts an explicit Settings instance parameter.
	 *
	 * @param Schema        $schema   The resolved Schema instance.
	 * @param Settings|null $existing Cross-prefix tolerant; see {@see self::$settings}.
	 *
	 * @return Settings
	 */
	private function resolve_settings( Schema $schema, $existing = null ) {
		$config = $this->config;

		if ( null !== $existing ) {
			$settings = $existing;
		} else {
			$defaults = array_replace_recursive(
				(array) ( $config['defaults'] ?? array() ),
				$schema->get_defaults()
			);

			$settings = new Settings(
				array(
					'slug'            => $config['slug'] ?? '',
					'option_name'     => $config['option_name'],
					'constant_prefix' => $config['constant_prefix'] ?? '',
					'encryption'      => $config['encryption'] ?? false,
					'config_file'     => $config['config_file'] ?? false,
					'network'         => ! empty( $config['network'] ),
					'defaults'        => $defaults,
					'preserved_keys'  => $schema->get_preserved_keys(),
				)
			);
		}

		// Always merge schema defaults and preserved keys, so active-toggle keys
		// (and any other schema-derived state) are recognized even by pre-built
		// instances.
		$settings->merge_defaults( $schema->get_defaults() );
		$settings->merge_preserved_keys( $schema->get_preserved_keys() );

		return $settings;
	}
}
