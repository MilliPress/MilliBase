<?php
/**
 * REST API controller for settings actions (reset, restore, custom).
 *
 * @package MilliSettings
 */

namespace MilliSettings;

/**
 * Registers REST endpoints for built-in actions (reset, restore)
 * and custom plugin-defined actions.
 */
final class RestController {

	/**
	 * The settings configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * The Store instance.
	 *
	 * @var Store
	 */
	private Store $store;

	/**
	 * Create a new RestController instance.
	 *
	 * @param array<string, mixed> $config The settings configuration.
	 * @param Store                $store  The store instance.
	 */
	public function __construct( array $config, Store $store ) {
		$this->config = $config;
		$this->store  = $store;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$namespace  = $this->config['rest_namespace'] ?? 'millisettings/v1';
		$capability = $this->config['capability'] ?? 'manage_options';

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

		// Status endpoint (if callback provided).
		if ( isset( $this->config['status_callback'] ) && is_callable( $this->config['status_callback'] ) ) {
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
		}

		// Custom plugin-defined action routes.
		foreach ( ( $this->config['actions'] ?? array() ) as $action ) {
			if ( empty( $action['endpoint'] ) || ! is_callable( $action['callback'] ?? null ) ) {
				continue;
			}

			$action_capability = $action['capability'] ?? $capability;
			$callback          = $action['callback'];

			register_rest_route(
				$namespace,
				'/' . ltrim( $action['endpoint'], '/' ),
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
	 * Handle built-in settings actions (reset, restore).
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function perform_settings_action( \WP_REST_Request $request ) {
		$action      = $request->get_param( 'action' );
		$slug        = $this->config['slug'] ?? 'millisettings';
		$option_name = $this->config['option_name'] ?? 'millisettings';

		/**
		 * Filters the allowed settings actions.
		 *
		 * @param string[] $allowed Array of allowed action slugs.
		 */
		$allowed = apply_filters(
			"millisettings_{$slug}_allowed_actions",
			array( 'reset', 'restore' )
		);

		if ( ! is_string( $action ) || ! in_array( $action, $allowed, true ) ) {
			return new \WP_Error(
				'invalid_settings_action',
				__( 'Invalid settings action.', 'millisettings' ),
				array( 'status' => 400 )
			);
		}

		$message = '';

		try {
			switch ( $action ) {
				case 'reset':
					$this->store->backup();
					delete_option( $option_name );
					$message = __( 'Settings reset successfully.', 'millisettings' );
					break;

				case 'restore':
					$restored = $this->store->restore_backup();
					if ( ! $restored ) {
						return new \WP_REST_Response(
							array(
								'success' => false,
								'message' => __( 'No backup of settings found or backup has expired.', 'millisettings' ),
							),
							400
						);
					}
					$message = __( 'Settings successfully restored from backup.', 'millisettings' );
					break;
			}
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'settings_action_failed',
				__( 'Failed to perform settings action: ', 'millisettings' ) . $e->getMessage(),
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
		do_action( "millisettings_{$slug}_action_performed", $action, $request->get_params(), $request );

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
	 * Get status information.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_status( \WP_REST_Request $request ): \WP_REST_Response {
		$slug     = $this->config['slug'] ?? 'millisettings';
		$callback = $this->config['status_callback'];

		try {
			$status_data = call_user_func( $callback, $request );

			// Always include settings meta.
			$status_data['settings'] = array(
				'has_defaults' => $this->store->has_default_settings(),
				'has_backup'   => $this->store->has_backup(),
			);

			/**
			 * Filters the status response.
			 *
			 * @param array            $status  The status data.
			 * @param \WP_REST_Request $request The REST request.
			 */
			$status_data = apply_filters( "millisettings_{$slug}_status_response", $status_data, $request );

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
