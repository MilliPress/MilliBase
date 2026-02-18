<?php
/**
 * Handles admin menu registration and asset enqueuing.
 *
 * @package MilliSettings
 */

namespace MilliSettings;

/**
 * Registers the admin menu page and enqueues the pre-built React JS bundle.
 */
final class AdminPage {

	/**
	 * The full settings configuration array.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * The Schema instance.
	 *
	 * @var Schema
	 */
	private Schema $schema;

	/**
	 * Create a new AdminPage instance.
	 *
	 * @param array<string, mixed> $config The settings configuration.
	 * @param Schema               $schema The schema instance.
	 */
	public function __construct( array $config, Schema $schema ) {
		$this->config = $config;
		$this->schema = $schema;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
	}

	/**
	 * Add the admin menu item.
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		$slug        = $this->config['slug'] ?? 'millisettings';
		$page_title  = $this->config['page_title'] ?? 'Settings';
		$menu_title  = $this->config['menu_title'] ?? 'Settings';
		$capability  = $this->config['capability'] ?? 'manage_options';
		$menu_parent = $this->config['menu_parent'] ?? 'options-general.php';
		$menu_icon   = $this->config['menu_icon'] ?? '';

		$render_callback = function () use ( $slug ) {
			printf( '<div class="wrap millisettings-page" id="%s-settings" data-slug="%s"></div>', esc_attr( $slug ), esc_attr( $slug ) );
		};

		if ( $menu_parent ) {
			add_submenu_page(
				$menu_parent,
				$page_title,
				$menu_title,
				$capability,
				$slug,
				$render_callback
			);
		} else {
			add_menu_page(
				$page_title,
				$menu_title,
				$capability,
				$slug,
				$render_callback,
				$menu_icon
			);
		}
	}

	/**
	 * Enqueue the pre-built JS bundle and CSS on the settings page.
	 *
	 * @param string $admin_page The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_settings_assets( string $admin_page ): void {
		$slug        = $this->config['slug'] ?? 'millisettings';
		$menu_parent = $this->config['menu_parent'] ?? 'options-general.php';

		// Determine the expected hook suffix.
		$expected_suffix = $menu_parent
			? 'settings_page_' . $slug
			: 'toplevel_page_' . $slug;

		if ( $admin_page !== $expected_suffix ) {
			return;
		}

		$this->enqueue_bundle();
		$this->inject_config();

		// WordPress components styles.
		wp_enqueue_style( 'wp-components' );
	}

	/**
	 * Enqueue the pre-built millisettings bundle.
	 *
	 * @return void
	 */
	private function enqueue_bundle(): void {
		$package_dir = $this->resolve_package_dir();
		$asset_file  = $package_dir . '/build/millisettings.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset      = include $asset_file;
		$basename   = $this->config['basename'] ?? '';
		$plugin_dir = $basename ? plugin_dir_path( WP_PLUGIN_DIR . '/' . $basename ) : $package_dir;

		// Use plugin_dir_url if basename is available, otherwise construct from package dir.
		if ( $basename ) {
			$base_url = plugins_url( '', WP_PLUGIN_DIR . '/' . $basename );
		} else {
			$base_url = plugins_url( '', $package_dir . '/millisettings.php' );
		}

		$build_url = $this->resolve_build_url();

		wp_enqueue_style(
			'millisettings',
			$build_url . '/millisettings.css',
			array(),
			$asset['version']
		);

		wp_enqueue_script(
			'millisettings',
			$build_url . '/millisettings.js',
			array_merge( $asset['dependencies'], array( 'wp-api-fetch' ) ),
			$asset['version'],
			array( 'in_footer' => true )
		);
	}

	/**
	 * Inject the settings schema configuration via inline script.
	 *
	 * @return void
	 */
	private function inject_config(): void {
		$slug           = $this->config['slug'] ?? 'millisettings';
		$option_name    = $this->config['option_name'] ?? 'millisettings';
		$rest_namespace = $this->config['rest_namespace'] ?? 'millisettings/v1';

		// Build the client-safe actions list.
		$client_actions = array();
		foreach ( ( $this->config['actions'] ?? array() ) as $action ) {
			$client_actions[] = array(
				'name'     => $action['name'] ?? '',
				'endpoint' => $action['endpoint'] ?? '',
				'method'   => $action['method'] ?? 'POST',
			);
		}

		$config_json = wp_json_encode(
			array(
				'slug'          => $slug,
				'optionName'    => $option_name,
				'restNamespace' => $rest_namespace,
				'containerId'   => $slug . '-settings',
				'schema'        => $this->schema->to_client_array(),
				'header'        => $this->config['header'] ?? array(),
				'actions'       => $client_actions,
			)
		);

		$escaped_slug = esc_js( $slug );

		wp_add_inline_script(
			'millisettings',
			"window.MilliSettings = window.MilliSettings || {}; window.MilliSettings.init = window.MilliSettings.init || function(s,c){ window.MilliSettings.configs = window.MilliSettings.configs || {}; window.MilliSettings.configs[s] = c; }; window.MilliSettings.init('{$escaped_slug}', {$config_json});",
			'before'
		);
	}

	/**
	 * Resolve the package directory (where build/ lives).
	 *
	 * @return string
	 */
	private function resolve_package_dir(): string {
		// The package's src/ is one level up from this file.
		return dirname( __DIR__ );
	}

	/**
	 * Resolve the URL to the package's build/ directory.
	 *
	 * @return string
	 */
	private function resolve_build_url(): string {
		$package_dir = $this->resolve_package_dir();

		// If the package is inside a plugin's vendor/ or deps/ directory,
		// construct the URL relative to the consuming plugin.
		$basename = $this->config['basename'] ?? '';

		if ( $basename ) {
			$plugin_dir = plugin_dir_path( WP_PLUGIN_DIR . '/' . $basename );
			$relative   = str_replace( $plugin_dir, '', $package_dir . '/' );

			return plugins_url( $relative . 'build', WP_PLUGIN_DIR . '/' . $basename );
		}

		// Fallback: assume the package is inside wp-content somewhere.
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$relative = str_replace( WP_CONTENT_DIR, '', $package_dir );
			return WP_CONTENT_URL . $relative . '/build';
		}

		return '';
	}
}
