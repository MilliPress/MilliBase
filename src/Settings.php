<?php
/**
 * Settings facade — the main entry point for consuming plugins.
 *
 * Usage:
 *   $settings = new \MilliSettings\Settings([
 *       'option_name' => 'myplugin',
 *       'slug'        => 'myplugin',
 *       'tabs'        => [ ... ],
 *       // ... full config array
 *   ]);
 *
 *   // Programmatic access:
 *   $settings->store()->get('cache.ttl');
 *
 * @package MilliSettings
 */

namespace MilliSettings;

/**
 * Facade that wires Store + Schema + AdminPage + RestController together.
 *
 * The constructor takes the full configuration array, creates all internal
 * components, and registers all WordPress hooks directly.
 */
final class Settings {

	/**
	 * The Store instance.
	 *
	 * @var Store
	 */
	private Store $store;

	/**
	 * The Schema instance.
	 *
	 * @var Schema
	 */
	private Schema $schema;

	/**
	 * The AdminPage instance.
	 *
	 * @var AdminPage|null
	 */
	private ?AdminPage $admin_page = null;

	/**
	 * The RestController instance.
	 *
	 * @var RestController|null
	 */
	private ?RestController $rest_controller = null;

	/**
	 * The full configuration array.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Create a new Settings instance and wire all components.
	 *
	 * @param array<string, mixed> $config The full settings configuration array.
	 */
	public function __construct( array $config ) {
		$this->config = $config;
		$this->schema = new Schema( $config );

		// Apply the filter to let third-party plugins extend the schema.
		$slug = $config['slug'] ?? 'millisettings';
		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filters the settings schema before initialization.
			 *
			 * @param array $config The full settings configuration array.
			 */
			$this->config = apply_filters( "millisettings_schema_{$slug}", $this->config );
			$this->schema = new Schema( $this->config );
		}

		// Build the Store with defaults extracted from the schema.
		$this->store = new Store(
			array(
				'option_name'     => $config['option_name'] ?? 'millisettings',
				'constant_prefix' => $config['constant_prefix'] ?? '',
				'encryption'      => $config['encryption'] ?? false,
				'defaults'        => $this->schema->get_defaults(),
				'config_file'     => $config['config_file'] ?? false,
			)
		);

		// Only register WordPress integration when WordPress is loaded.
		if ( function_exists( 'add_action' ) ) {
			$this->boot();
		}
	}

	/**
	 * Boot all WordPress integrations.
	 *
	 * @return void
	 */
	private function boot(): void {
		// Store hooks (option filtering, encryption, config file sync).
		$this->store->register_hooks();

		// Register settings with the REST API.
		add_action( 'init', array( $this, 'register_settings' ) );

		// Admin page (menu + assets).
		$this->admin_page = new AdminPage( $this->config, $this->schema );
		$this->admin_page->register_hooks();

		// REST controller (settings actions + status).
		$this->rest_controller = new RestController( $this->config, $this->store );
		$this->rest_controller->register_hooks();
	}

	/**
	 * Register the option with WordPress for the REST API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		$option_name = $this->config['option_name'] ?? 'millisettings';
		$defaults    = $this->schema->get_defaults();

		register_setting(
			'options',
			$option_name,
			array(
				'type'         => 'object',
				'default'      => $defaults,
				'show_in_rest' => array(
					'schema' => $this->schema->get_rest_schema(),
				),
			)
		);
	}

	/**
	 * Get the Store instance for programmatic settings access.
	 *
	 * @return Store
	 */
	public function store(): Store {
		return $this->store;
	}

	/**
	 * Get the Schema instance.
	 *
	 * @return Schema
	 */
	public function schema(): Schema {
		return $this->schema;
	}
}
