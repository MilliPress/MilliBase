---
title: 'Configuration'
post_excerpt: 'Full reference of the configuration array passed to the Manager constructor.'
menu_order: 10
---

# Configuration

The `Manager` constructor accepts a single configuration array. This page documents every top-level key.

> **Important:** Create the Manager on the `init` hook (or later) so that translation functions like `__()` execute after textdomains are loaded. This is required since WordPress 6.7.

## Configuration Reference

```php
add_action( 'init', function () {
$manager = new \MilliBase\Manager([
    // ─── Required ──────────────────────────────────────────
    'slug'           => 'my-plugin',           // Unique identifier, used for hooks and DOM IDs
    'tabs'           => [ /* ... */ ],         // Tab definitions (see Schema Definition)

    // ─── Admin Menu ────────────────────────────────────────
    'page_title'     => 'My Plugin Settings',  // Browser title
    'menu_title'     => 'My Plugin',           // Menu label
    'capability'     => 'manage_options',       // Required capability
    'menu_parent'    => 'options-general.php',  // Parent menu slug, or '' for top-level
    'menu_icon'      => 'dashicons-admin-generic', // Dashicon (top-level only)

    // ─── Storage ───────────────────────────────────────────
    'constant_prefix' => 'MP',                 // Prefix for wp-config.php constant overrides
    'encryption'      => true,                 // Enable sodium encryption for enc_* fields
    'config_file'     => [                     // Config file sync for pre-WordPress access
        'directory' => '/path/to/config',
    ],
    'defaults'        => [                     // Non-UI defaults (merged with schema defaults)
        'advanced' => ['debug' => false],
    ],

    // ─── Header ────────────────────────────────────────────
    'header' => [
        'title' => 'My Plugin Settings',
        'links' => [
            ['label' => 'Documentation', 'url' => 'https://example.com/docs'],
        ],
        'buttons'    => [ /* ... */ ],
        'menu_items' => [ /* ... */ ],
    ],

    // ─── Actions ───────────────────────────────────────────
    'actions'         => [ /* ... */ ],        // Custom REST action endpoints
    'status' => [                            // Optional status endpoint data
        'data'     => ['version' => '1.0'],  // Static data (merged first)
        'callback' => function ($request) {  // Dynamic data (merged on top)
            return ['healthy' => true];
        },
    ],
    'troubleshooting' => [                   // Optional link shown on connection errors
        'url'   => 'https://example.com/docs/troubleshooting',
        'label' => 'View Troubleshooting Guide',  // Optional (has default)
        'text'  => 'Need help fixing this issue?', // Optional (has default)
    ],

    // ─── Advanced ──────────────────────────────────────────
    'settings'  => $external_settings,          // Optional: inject a pre-built Settings instance
    'build_url' => 'https://...',              // Optional: explicit URL to the build/ directory
]);
} );
```

## Key Details

### `slug`

Unique identifier for this settings page. Used for:

- WordPress hook names (`{slug}_settings_schema`, `{slug}_rest_settings_action_performed`, `{slug}_rest_status_response`)
- Admin page hook suffix (`settings_page_{slug}` or `toplevel_page_{slug}`)
- DOM container ID (`{slug}-settings`)
- The `data-slug` attribute used by the React auto-mount

### `option_name`

The WordPress option name in `wp_options`. Defaults to `{slug}_settings`. All settings are stored as a single serialized array under this key. Also used for:

- `register_setting()` registration
- Backup transient key (`{option_name}_backup`)

Override only when migrating from a plugin that already stores settings under a different key.

### `rest_namespace`

The REST API namespace for action and status endpoints. Defaults to `{slug}/v1`.

### `menu_parent`

Controls where the admin page appears:

| Value | Result |
|-------|--------|
| `'options-general.php'` | Submenu under Settings (default) |
| `'tools.php'` | Submenu under Tools |
| `''` (empty string) | Top-level menu page |

When set to an empty string, the `menu_icon` property is used for the menu icon.

### `constant_prefix`

When set, MilliBase checks for PHP constants that override individual settings. The constant name follows the pattern:

```
{PREFIX}_{MODULE}_{KEY}
```

For example, with `constant_prefix => 'MP'`, a field with key `cache.ttl` can be overridden by defining:

```php
// wp-config.php
define('MP_CACHE_TTL', 7200);
```

Constants take the highest priority and make the corresponding field read-only in the UI.

> [!NOTE]
> For encrypted fields (keys starting with `enc_`), constants are also checked without the `enc_` prefix. A field `storage.enc_password` can be overridden by either `MP_STORAGE_ENC_PASSWORD` or `MP_STORAGE_PASSWORD`.

### `config_file`

When configured, settings are automatically synced to a PHP file on every save. This enables reading settings before WordPress loads (e.g. in `advanced-cache.php` or a `mu-plugin`).

The file is named by the current domain: `{directory}/{sanitized_domain}.php`.

### Build URL Resolution

MilliBase automatically resolves the URL to its `build/` directory via `plugins_url()`. This works for standard Composer installs where the package is physically inside the plugin directory.

For **symlinked packages** (e.g. Composer path repositories during development), define a `{SLUG}_BASENAME` constant in your main plugin file:

```php
define('MY_PLUGIN_BASENAME', plugin_basename(__FILE__));
```

MilliBase checks for this constant (derived from the `slug` config) and uses it to resolve the correct build URL even when `__DIR__` points outside the plugin directory.

The `build_url` config key can be used as an explicit override when neither automatic resolution nor the basename constant produce the correct URL.

### `settings`

Pass an externally created `Settings` instance to share storage across multiple settings pages or to use custom configuration. When a `Settings` instance is provided, MilliBase does not call `register_hooks()` on it — the caller manages its lifecycle.

### `header`

Configures the header section of the settings page:

- **`title`** — page heading
- **`links`** — array of `{label, url}` objects rendered as external links
- **`buttons`** — custom buttons with `{label, action, variant, component}`
- **`menu_items`** — items in the "More Actions" dropdown with `{label, action, url, icon}`

Available dropdown icons: `lifesaver`, `backup`, `flipVertical`.

### `actions`

Define custom REST endpoints that the UI can trigger:

```php
'actions' => [
    [
        'name'       => 'purge-cache',       // Action name (or array of names)
        'endpoint'   => 'purge',             // REST route (relative to rest_namespace)
        'method'     => 'POST',              // HTTP method (default: POST)
        'capability' => 'manage_options',    // Override default capability
        'callback'   => function ($request) {
            // Handle the action...
            return new \WP_REST_Response(['success' => true]);
        },
    ],
],
```

The `name` field can be a string or an array of strings. Each name registers a separate trigger in the React UI that calls the same endpoint.

### `status`

MilliBase always registers a `GET /{rest_namespace}/status` endpoint that returns settings metadata (defaults, backup availability, constant overrides). The React UI polls this endpoint every 15 seconds.

The `status` config accepts `data` (static array, merged first) and/or `callback` (called on each request, merged on top):

```php
'status' => [
    // Static data — merged as a base layer.
    'data' => [
        'version' => '1.2.3',
    ],
    // Dynamic data — called on each request, overwrites static keys.
    'callback' => function (\WP_REST_Request $request) {
        return [
            'healthy'    => true,
            'last_check' => time(),
        ];
    },
],
```

Both keys are optional. The response automatically includes `settings.has_defaults`, `settings.has_backup`, and `settings.constants` (values defined via PHP constants).

## Next Steps

- **[Schema Definition](./02-schema-definition.md)** — define tabs, sections, and fields
- **[Programmatic Access](./03-programmatic-access.md)** — use the Settings API to read/write settings
