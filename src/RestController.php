<?php
/**
 * REST API controller for settings actions (reset, restore, custom).
 *
 * @package MilliBase
 * @author  Philipp Wellmer <hello@millipress.com>
 */

namespace MilliBase;

/**
 * Registers REST endpoints for built-in actions (reset, restore)
 * and custom plugin-defined actions.
 *
 * @since 1.0.0
 */
final class RestController {

	/**
	 * The settings configuration.
	 *
	 * @since 1.0.0
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * The Store instance.
	 *
	 * @since 1.0.0
	 * @var Store
	 */
	private Store $store;

	/**
	 * Create a new RestController instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config The settings configuration.
	 * @param Store                $store  The store instance.
	 */
	public function __construct( array $config, Store $store ) {
		$this->config = $config;
		$this->store  = $store;
	}

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

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'rest_authentication_errors', array( $this, 'verify_rest_nonce' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * Registers the built-in settings endpoint, the status endpoint,
	 * and any custom action routes defined in the config's `actions` array.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$namespace  = $this->config_string( 'rest_namespace', 'millibase/v1' );
		$capability = $this->config_string( 'capability', 'manage_options' );

		// Built-in settings actions (reset, restore).
		register_rest_route(
			$namespace,
			'/settings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'perform_settings_action' ),
				'permission_callback' => function () use ( $capability ) {
					return current_user_can( $capability );
				},
			)
		);

		// Status endpoint — always registered for constants/metadata;
		// optionally enriched by status.callback and/or status.data.
		register_rest_route(
			$namespace,
			'/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => function () use ( $capability ) {
					return current_user_can( $capability );
				},
			)
		);

		// Custom plugin-defined action routes.
		$actions = is_array( $this->config['actions'] ?? null ) ? $this->config['actions'] : array();
		foreach ( $actions as $action ) {
			if ( empty( $action['endpoint'] ) || ! is_callable( $action['callback'] ?? null ) ) {
				continue;
			}

			$action_capability = is_string( $action['capability'] ?? null ) ? $action['capability'] : $capability;
			$callback          = $action['callback'];

			register_rest_route(
				$namespace,
				'/' . ltrim( is_string( $action['endpoint'] ) ? $action['endpoint'] : '', '/' ),
				array(
					'methods'             => $action['method'] ?? \WP_REST_Server::CREATABLE,
					'callback'            => $callback,
					'permission_callback' => function () use ( $action_capability ) {
						return current_user_can( $action_capability );
					},
				)
			);
		}
	}

	/**
	 * Verify the REST API nonce for this controller's namespace.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Error|null|true $result The current authentication result.
	 * @return \WP_Error|null|true
	 */
	public function verify_rest_nonce( $result ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$namespace   = $this->config_string( 'rest_namespace', 'millibase/v1' );
		$request_uri = ServerVars::get( 'REQUEST_URI' );

		if ( ! empty( $request_uri ) && strpos( $request_uri, "/$namespace/" ) !== false ) {
			$method = ServerVars::get( 'REQUEST_METHOD' );
			if ( in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
				return $result;
			}

			$nonce = ServerVars::get( 'HTTP_X_WP_NONCE' );
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new \WP_Error(
					'invalid_nonce',
					__( 'Invalid nonce.', 'millibase' ),
					array( 'status' => 403 )
				);
			}
		}

		return $result;
	}

	/**
	 * Handle built-in settings actions (reset, restore).
	 *
	 * Validates the requested action against a filterable allow-list, executes
	 * it, and returns a standardised JSON response. A backup is created
	 * automatically before a reset.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function perform_settings_action( \WP_REST_Request $request ) {
		$action      = $request->get_param( 'action' );
		$slug        = $this->config_string( 'slug', 'millibase' );
		$option_name = $this->config_string( 'option_name', 'millibase' );

		/**
		 * Filters the allowed settings actions.
		 *
		 * @param string[] $allowed Array of allowed action slugs.
		 */
		$allowed = apply_filters(
			"{$slug}_rest_settings_allowed_actions",
			array( 'reset', 'restore' )
		);

		if ( ! is_string( $action ) || ! in_array( $action, $allowed, true ) ) {
			return new \WP_Error(
				'invalid_settings_action',
				__( 'Invalid settings action.', 'millibase' ),
				array( 'status' => 400 )
			);
		}

		$message = '';

		try {
			switch ( $action ) {
				case 'reset':
					$this->store->backup();
					delete_option( $option_name );
					$message = __( 'Settings reset successfully.', 'millibase' );
					break;

				case 'restore':
					$restored = $this->store->restore_backup();
					if ( ! $restored ) {
						return new \WP_REST_Response(
							array(
								'success' => false,
								'message' => __( 'No backup of settings found or backup has expired.', 'millibase' ),
							),
							400
						);
					}
					$message = __( 'Settings successfully restored from backup.', 'millibase' );
					break;
			}
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'settings_action_failed',
				__( 'Failed to perform settings action: ', 'millibase' ) . $e->getMessage(),
				array( 'status' => 500 )
			);
		}

		/**
		 * Fires after a settings action has been performed.
		 *
		 * @param string           $action  The action performed.
		 * @param array            $params  The request parameters.
		 * @param \WP_REST_Request $request The REST request.
		 */
		do_action( "{$slug}_rest_settings_action_performed", $action, $request->get_params(), $request );

		return rest_ensure_response(
			array(
				'success'   => true,
				'message'   => $message,
				'action'    => $action,
				'timestamp' => time(),
			)
		);
	}

	/**
	 * Return status information.
	 *
	 * Always includes settings metadata (defaults, backup, constants).
	 * When `status.data` is configured, it is merged as a static base.
	 * When `status.callback` is configured, its output is merged on top.
	 * The result is passed through the `{$slug}_rest_status_response` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_status( \WP_REST_Request $request ): \WP_REST_Response {
		$slug     = $this->config_string( 'slug', 'millibase' );
		$status   = is_array( $this->config['status'] ?? null ) ? $this->config['status'] : array();
		$data     = is_array( $status['data'] ?? null ) ? $status['data'] : array();
		$callback = $status['callback'] ?? null;

		try {
			$status_data = array_merge(
				$data,
				is_callable( $callback ) ? (array) call_user_func( $callback, $request ) : array()
			);

			$status_data['settings'] = array(
				'has_defaults' => $this->store->has_default_settings(),
				'has_backup'   => $this->store->has_backup(),
				'constants'    => $this->store->get_settings_from_constants(),
			);

			/**
			 * Filters the status response.
			 *
			 * @param array            $status  The status data.
			 * @param \WP_REST_Request $request The REST request.
			 */
			$status_data = apply_filters( "{$slug}_rest_status_response", $status_data, $request );

			return new \WP_REST_Response( $status_data );
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}
}
