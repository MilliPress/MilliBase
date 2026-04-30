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

use MilliBase\CLI\Controller as CliController;
use MilliBase\REST\Controller as RestController;

/**
 * Orchestrator that wires Settings + Schema + AdminPage + REST\Controller + CLI\Controller together.
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

	/**
	 * The plugin slug, used for filters and auto-derived config keys.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $slug;

	/**
	 * The Settings instance.
	 *
	 * Available immediately when passed via constructor; otherwise created
	 * during initialize().
	 *
	 * Untyped at runtime to tolerate cross-prefix instances during the brief
	 * window when both Strauss-prefixed and unprefixed copies are autoloadable
	 * (plugin activation switch). PHPStan still type-checks via `@var`.
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
	 * Whether initialize() has run.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	private bool $initialized = false;

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
		$settings = null,
	) {
		$this->slug     = $slug;
		$this->settings = $settings;

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

		$this->register_settings();

		$this->admin_page = new AdminPage( $this->config, $schema );
		$this->admin_page->register_hooks();

		$this->rest_controller = new RestController( $this->config, $settings );
		$this->rest_controller->register_hooks();

		$this->cli_controller = new CliController( $this->config, $settings );
		$this->cli_controller->register_hooks();
	}

	/**
	 * Register the option with WordPress for the REST API.
	 *
	 * Uses the Settings' full defaults (including non-UI fields) so the REST
	 * schema covers every setting key, not just those with UI fields.
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

		register_setting(
			'options',
			$option_name,
			array(
				'type'         => 'object',
				'default'      => $defaults,
				'show_in_rest' => array(
					'schema' => $schema->get_rest_schema( $defaults ),
				),
			)
		);
	}

	// ─── Accessors ──────────────────────────────────────────────────────

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

	// ─── Helpers ────────────────────────────────────────────────────────

	/**
	 * Get a string value from the config array.
	 *
	 * @param string $key      The config key.
	 * @param string $fallback The fallback value.
	 *
	 * @return string
	 */
	private function config_string( string $key, string $fallback = '' ): string {
		$value = $this->config[ $key ] ?? $fallback;
		return is_string( $value ) ? $value : $fallback;
	}

	// ─── Private resolvers ──────────────────────────────────────────────

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
			? apply_filters( "{$this->slug}_settings_schema", array( 'tabs' => array() ) )
			: array( 'tabs' => array() );

		$defaults = ( new Schema( $config ) )->get_defaults();

		if ( ! empty( $defaults ) ) {
			$this->settings->merge_defaults( $defaults );
		}
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

		// Auto-derive defaults from slug.
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
			 * @param array<string, mixed> $config The full settings configuration array.
			 */
			$this->config = apply_filters( "{$this->slug}_settings_schema", $this->config );
		}

		return new Schema( $this->config );
	}

	/**
	 * Resolve the Settings: use an existing instance or build one from the schema.
	 *
	 * When an external Settings instance is provided (via the constructor),
	 * it is reused. Otherwise, a new instance is created from the config.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Accepts an explicit Settings instance parameter.
	 *
	 * @param Schema        $schema   The resolved Schema instance.
	 * @param Settings|null $existing Pre-built Settings instance, or null to create one.
	 *
	 * @return Settings
	 */
	private function resolve_settings( Schema $schema, ?Settings $existing = null ): Settings {
		$config = $this->config;

		if ( null !== $existing ) {
			$settings = $existing;
		} else {
			// Merge explicit defaults (non-UI fields) with schema-extracted defaults.
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
					'defaults'        => $defaults,
				)
			);
		}

		// Always merge schema defaults, so active-toggle keys (and any other
		// schema-derived defaults) are recognized even by pre-built instances.
		$settings->merge_defaults( $schema->get_defaults() );

		return $settings;
	}
}
