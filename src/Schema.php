<?php
/**
 * Parses a declarative PHP settings array into defaults, JSON schema, and client config.
 *
 * @package MilliSettings
 */

namespace MilliSettings;

/**
 * Handles the declarative schema: extracts defaults from field definitions,
 * generates JSON schema for show_in_rest, and generates client-safe config.
 */
final class Schema {

	/**
	 * The full configuration array.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Cached defaults extracted from field definitions.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private ?array $defaults = null;

	/**
	 * Create a new Schema instance.
	 *
	 * @param array<string, mixed> $config The full settings configuration array.
	 */
	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * Extract default settings from all field definitions in the schema.
	 *
	 * Returns a nested array keyed by module (from dot-notation field keys).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_defaults(): array {
		if ( null !== $this->defaults ) {
			return $this->defaults;
		}

		$defaults = array();

		foreach ( $this->get_all_fields() as $field ) {
			if ( ! isset( $field['key'] ) ) {
				continue;
			}

			$parts = explode( '.', $field['key'] );
			if ( count( $parts ) < 2 ) {
				continue;
			}

			$module = $parts[0];
			$key    = $parts[1];

			if ( ! isset( $defaults[ $module ] ) ) {
				$defaults[ $module ] = array();
			}

			$defaults[ $module ][ $key ] = $field['default'] ?? null;
		}

		$this->defaults = $defaults;
		return $defaults;
	}

	/**
	 * Generate a JSON schema for WordPress register_setting() show_in_rest.
	 *
	 * @return array<string, mixed>
	 */
	public function get_rest_schema(): array {
		$defaults = $this->get_defaults();
		$schema   = array(
			'type'       => 'object',
			'properties' => array(),
		);

		foreach ( $defaults as $module_key => $module_settings ) {
			$module_schema = array(
				'type'       => 'object',
				'properties' => array(),
			);

			foreach ( $module_settings as $key => $value ) {
				$module_schema['properties'][ $key ] = array( 'type' => $this->php_type_to_json( $value ) );
			}

			$schema['properties'][ $module_key ] = $module_schema;
		}

		return $schema;
	}

	/**
	 * Generate the client-safe configuration for the React UI.
	 *
	 * Strips PHP callbacks and server-only properties.
	 *
	 * @return array<string, mixed>
	 */
	public function to_client_array(): array {
		$tabs = array();

		foreach ( ( $this->config['tabs'] ?? array() ) as $tab ) {
			$client_tab = array(
				'name'  => $tab['name'] ?? '',
				'title' => $tab['title'] ?? '',
			);

			if ( isset( $tab['type'] ) ) {
				$client_tab['type'] = $tab['type'];
			}

			if ( isset( $tab['component'] ) ) {
				$client_tab['component'] = $tab['component'];
			}

			if ( isset( $tab['sections'] ) ) {
				$client_tab['sections'] = array();

				foreach ( $tab['sections'] as $section ) {
					$client_section = array(
						'id'           => $section['id'] ?? '',
						'title'        => $section['title'] ?? '',
						'initial_open' => $section['initial_open'] ?? true,
					);

					if ( isset( $section['icon'] ) ) {
						$client_section['icon'] = $section['icon'];
					}

					if ( isset( $section['fields'] ) ) {
						$client_section['fields'] = array();

						foreach ( $section['fields'] as $field ) {
							$client_field = $this->field_to_client( $field );
							if ( $client_field ) {
								$client_section['fields'][] = $client_field;
							}
						}
					}

					$client_tab['sections'][] = $client_section;
				}
			}

			$tabs[] = $client_tab;
		}

		return array(
			'tabs'     => $tabs,
			'defaults' => $this->get_defaults(),
		);
	}

	/**
	 * Get all fields from all tabs and sections (flattened).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all_fields(): array {
		$fields = array();

		foreach ( ( $this->config['tabs'] ?? array() ) as $tab ) {
			foreach ( ( $tab['sections'] ?? array() ) as $section ) {
				foreach ( ( $section['fields'] ?? array() ) as $field ) {
					$fields[] = $field;
				}
			}
		}

		return $fields;
	}

	/**
	 * Convert a field definition to a client-safe array.
	 *
	 * @param array<string, mixed> $field The field definition.
	 *
	 * @return array<string, mixed>|null
	 */
	private function field_to_client( array $field ): ?array {
		if ( ! isset( $field['key'], $field['type'] ) ) {
			return null;
		}

		$client = array(
			'key'   => $field['key'],
			'type'  => $field['type'],
			'label' => $field['label'] ?? '',
		);

		// Copy through safe properties.
		$safe_keys = array(
			'tooltip',
			'placeholder',
			'default',
			'min',
			'max',
			'units',
			'store_as',
			'options',
			'encrypted',
			'disabled',
			'rows',
			'language',
		);

		foreach ( $safe_keys as $safe_key ) {
			if ( isset( $field[ $safe_key ] ) ) {
				$client[ $safe_key ] = $field[ $safe_key ];
			}
		}

		return $client;
	}

	/**
	 * Map a PHP value to a JSON schema type string.
	 *
	 * @param mixed $value The PHP value.
	 *
	 * @return string The JSON schema type.
	 */
	private function php_type_to_json( $value ): string {
		if ( is_bool( $value ) ) {
			return 'boolean';
		}
		if ( is_int( $value ) ) {
			return 'integer';
		}
		if ( is_float( $value ) ) {
			return 'number';
		}
		if ( is_array( $value ) ) {
			return 'array';
		}
		return 'string';
	}
}
