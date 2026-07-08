---
title: 'Configuration'
post_excerpt: 'Full reference of the configuration array passed to the Manager constructor.'
menu_order: 10
---

# Configuration

The `Manager` constructor accepts a `slug`, a config `Closure`, and an optional `Settings` instance. The closure is called on `init`, so translation functions like `__()` execute after textdomains are loaded. This page documents every key in the config array returned by the closure.

## Configuration Reference

```php
$manager = new \MilliBase\Manager(
    slug: 'my-plugin',
    config: fn() => [
        // ─── Required ──────────────────────────────────────────
        'tabs'           => [ /* ... */ ],         // Tab definitions (see Schema Definition)

        // ─── Admin Menu ────────────────────────────────────────
        'page_title'     => __( 'My Plugin Settings', 'my-plugin' ),
        'menu_title'     => __( 'My Plugin', 'my-plugin' ),
        'capability'     => 'manage_options',       // Required capability
        'menu_parent'    => 'options-general.php',  // Parent menu slug, or '' for top-level
        'menu_icon'      => 'dashicons-admin-generic', // Dashicon (top-level only)
        'network'        => false,                  // Route storage to site-options and menu to Network Admin

        // ─── Storage ───────────────────────────────────────────
        'constant_prefix' => 'MP',                 // Prefix for wp-config.php constant overrides
        'encryption'      => true,                 // Enable sodium encryption for enc_* fields
        'config_file'     => [                     // Config file sync for pre-WordPress access
            'directory' => '/path/to/config',
        ],
        'defaults'        => [                     // Non-UI defaults (merged with schema defaults)
            'advanced' => ['debug' => false],
        ],
        'migrations'      => [ /* ... */ ],        // Declarative migrations (see Migrations)

        // ─── Header ────────────────────────────────────────────
        'header' => [
            'title' => __( 'My Plugin Settings', 'my-plugin' ),
            'links' => [
                ['label' => __( 'Documentation', 'my-plugin' ), 'url' => 'https://example.com/docs'],
            ],
            'buttons'    => [ /* ... */ ],
            'menu_items' => [ /* ... */ ],
        ],

        // ─── Actions ───────────────────────────────────────────
        'actions'         => [ /* ... */ ],        // Custom REST action endpoints
        'abilities'       => [                     // WP Abilities API config — see 05-abilities.md
            'expose' => true,                    // Opt-in: MilliBase wraps Settings export/reset/backup/restore as abilities.
            'category' => [ /* ... */ ],           // Optional override for the auto-registered ability category.
            'extend'   => [ /* ... */ ],           // Plugin-defined ability entries.
        ],
        'status' => [                            // Optional status endpoint data
            'data'     => ['version' => '1.0'],  // Static data (merged first)
            'callback' => function ($request) {  // Dynamic data (merged on top)
                return ['healthy' => true];
            },
        ],
        'troubleshooting' => [                   // Optional link shown on connection errors
            'url'   => 'https://example.com/docs/troubleshooting',
            'label' => __( 'View Troubleshooting Guide', 'my-plugin' ),
            'text'  => __( 'Need help fixing this issue?', 'my-plugin' ),
        ],

        // ─── Advanced ──────────────────────────────────────────
        'build_url' => 'https://...',              // Optional: explicit URL to the build/ directory
        'cli'       => true,                       // WP-CLI registration: true | false
    ],
    settings: $external_settings,  // Optional: pre-built Settings instance
);
```

> **Note:** The `slug` is passed as a named constructor parameter, not inside the config array. `option_name` and `rest_namespace` are auto-derived from the slug but can be overridden in the config array.

## Key Details

### `slug` (constructor parameter)

Unique identifier for this settings page. Passed as the first argument to the `Manager` constructor (not inside the config array). Used for:

- WordPress hook names (`{slug}_settings_schema`, `{slug}_rest_settings_action_performed`, `{slug}_rest_status_response`)
- Admin page hook suffix (`settings_page_{slug}` or `toplevel_page_{slug}`)
- DOM container ID (`{slug}-settings`)
- The `data-slug` attribute used by the React auto-mount
- Auto-deriving `option_name` and `rest_namespace` when not explicitly set

### `option_name`

The WordPress option name in `wp_options`. Defaults to `{slug}`. All settings are stored as a single serialized array under this key. Also used for:

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

The filename is computed per-operation from the current blog (it follows `switch_to_blog()` correctly, so a single WP-CLI process iterating `get_sites()` writes to each subsite's own file):

| Mode                               | Filename pattern             |
|------------------------------------|------------------------------|
| Single-site                        | `{host}.php`                 |
| Subdomain multisite                | `{host}.php` (per subdomain) |
| Subdirectory multisite             | `{host}_{blog_path}.php`     |
| Network mode (`'network' => true`) | `_network-{network_id}.php`  |

Non-alphanumeric characters in the resolved identifier are replaced with `_`. The leading underscore on `_network-*.php` is intentional — network-scoped config files sort to the top of a directory listing alongside per-site files.

### `network`

Setting `'network' => true` on a Manager flips a few things at once:

- **Storage backend** — Settings reads and writes route through `get_site_option` / `update_site_option`, so the data lands in `wp_sitemeta` instead of `wp_options`. Backups use per-network site transients; the sanitize callback hooks `pre_update_site_option_<name>` at priority `-100`.
- **Admin menu placement** — On multisite the page is registered via `network_admin_menu` and appears under Network Admin. On single-site the flag is silently ignored for the menu placement, so a stray `'network' => true` on a non-multisite install doesn't hide the page.
- **REST route prefix** — All routes registered by this Manager are prefixed with `/network` inside the shared namespace. A network Manager exposes `/<rest_namespace>/network/settings`, `/<rest_namespace>/network/status`, etc. The site Manager (same plugin, same `rest_namespace`) keeps the unprefixed paths. This lets two Managers coexist on a shared namespace without overwriting each other's route handlers.
- **Scope-aware filters** — When two Managers share a slug, the orchestrator's filters pass `$is_network` so hooks can branch on which Manager fired. The argument is the **last** position in each signature:
  - `{slug}_settings_schema` — `(array $config, bool $is_network)`
  - `{slug}_settings_defaults` — `(array $defaults, bool $is_network)`
  - `{slug}_rest_status_response` — `(array $status, WP_REST_Request $request, bool $is_network)`

  ```php
  add_filter( 'my-plugin_settings_schema', function ( $config, $is_network ) {
      if ( $is_network ) {
          $config['tabs'][] = [ /* network-only tab */ ];
      }
      return $config;
  }, 10, 2 );
  ```

  Existing 1- or 2-argument callbacks keep working — PHP drops surplus arguments at the callable boundary, so the extra `$is_network` only reaches callbacks that declare it.

A typical pattern is to run two Managers side-by-side under the same plugin slug — one with `'network' => false` for per-site settings (e.g. cache rules), one with `'network' => true` for network-wide settings (e.g. shared storage credentials). They share `option_name`-key strings safely (different DB tables) and share the REST namespace via the `/network` route prefix. CLI commands and abilities auto-merge across them.

MilliBase emits a `_doing_it_wrong()` notice when two Managers register the same slug + network combination (i.e. two per-site Managers or two network Managers under one slug) — that case collides on the option key, the REST routes, and the admin menu slug. Either use distinct slugs or distinguish them by `network` mode.

### `migrations`

Declarative migrations that run once per `name@version` identity at `init` priority 5. State is recorded in `<slug>_migration_state` (`wp_options` for site-scope, `wp_sitemeta` for network-scope).

```php
'migrations' => [
    [
        'name'     => 'rename-cache-ttl-key',
        'version'  => '2.5.0',
        'scope'    => 'site',          // 'site' or 'network'
        'callback' => function (\MilliBase\Manager $manager): void {
            $settings = $manager->settings();
            $legacy   = $settings->get('cache.legacy_ttl');
            if ( null !== $legacy ) {
                $settings->set('cache.ttl', (int) $legacy);
            }
        },
    ],
],
```

See [Migrations](./05-migrations.md) for the full contract (identity, ordering, failure recording, multisite behavior).

### `cli`

Controls WP-CLI registration for this Manager:

| Value               | Behaviour                                      |
|---------------------|------------------------------------------------|
| `true` (or omitted) | Register under `wp <slug> config <subcommand>` |
| `false`             | Skip CLI registration entirely                 |

When two Managers share the same primary slug (typically a site + network split), they coexist under one `wp <slug> config` command tree. Each subcommand accepts `[--network]`; operators pick the scope per call.

See [WP-CLI Commands](./04-wp-cli.md) for the full command reference and the `--network` flag semantics.

### Build URL Resolution

MilliBase automatically resolves the URL to its `build/` directory via `plugins_url()`. This works for standard Composer installs where the package is physically inside the plugin directory.

For **symlinked packages** (e.g. Composer path repositories during development), define a `{SLUG}_BASENAME` constant in your main plugin file:

```php
define('MY_PLUGIN_BASENAME', plugin_basename(__FILE__));
```

MilliBase checks for this constant (derived from the `slug` config) and uses it to resolve the correct build URL even when `__DIR__` points outside the plugin directory.

The `build_url` config key can be used as an explicit override when neither automatic resolution nor the basename constant produce the correct URL.

### `settings` (constructor parameter)

Pass an externally created `Settings` instance as the third constructor argument to share storage across multiple settings pages or to use custom configuration. When a `Settings` instance is provided:

- Schema-derived defaults are merged into it at construction time (before `init`)
- The instance is reused instead of creating a new one during initialization
- The caller manages its lifecycle

This is the recommended pattern for plugins that need settings access before `init`:

```php
$settings = new \MilliBase\Settings([...]);
$manager  = new \MilliBase\Manager(
    slug: 'my-plugin',
    config: fn() => $this->get_ui_config(),
    settings: $settings,
);

// $settings->get('cache.ttl') works immediately — no need to wait for init.
```

### `header`

Configures the header section of the settings page:

- **`title`** — page heading
- **`links`** — array of `{label, url}` objects rendered as external links
- **`buttons`** — custom buttons with `{label, action, variant, component}`
- **`menu_items`** — items in the "More Actions" dropdown with `{label, action, url, icon, position}`

Available dropdown icons: `lifesaver`, `backup`, `flipVertical`.

Menu items are ordered by `position` (lower = higher up, like hook priorities).
Custom items default to `10`; the built-in Reset and Restore (`100`)
actions sit at the bottom. Items sharing a position keep their definition order.

### `footer`

Overrides WordPress's admin-footer text on the settings page only. Both keys
are optional. Each slot fully replaces its side of the footer when set, so the
consumer owns both — including whether to show any branding.

```php
'footer' => [
    'left'  => __( 'Thanks for using <a href="https://my-plugin.com">My Plugin</a>.', 'my-plugin' ),
    'right' => 'My Plugin ' . MY_PLUGIN_VERSION,
],
```

Rendered (with both keys set):
- **Left:** `Thanks for using My Plugin.` (with the anchor wired)
- **Right:** `My Plugin 1.2.3`

String values go through `wp_kses_post()`, so anchors, `<strong>`, `<em>`,
`<span>`, and other post-safe HTML survive while `<script>` / `<style>` and
other dangerous tags are stripped.

MilliBase only fills the right slot when `right` is unset/empty, falling back to
`MilliBase 2.5.3` so the framework version stays visible for support when the
consumer hasn't supplied their own.

#### Custom React component in a footer slot

Either slot can also be a `['component' => 'Name']` reference to a React
component the consumer has registered via
`window.MilliBase.registerCustomComponent()` — same registry header buttons
and custom tabs use.

```php
'footer' => [
    'right' => [ 'component' => 'MyPluginFooterStatus' ],
],
```

```jsx
window.MilliBase.registerCustomComponent( 'MyPluginFooterStatus', ( { status } ) => (
    <span>
        { status?.license?.is_licensed
            ? 'Pro · Active'
            : 'Pro · Not active' }
    </span>
) );
```

The component receives the same standard prop set custom tabs already get:
`{ status, settings, triggerAction, isLoading }`. It renders via a React
portal from inside `SettingsApp`'s tree, so it has full access to
`useSettings()`-style state without re-fetching anything. Placeholders for
unregistered names render as an empty span — degrades gracefully if the
JS registry hasn't been populated yet.

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

#### Action response contract

The callback returns a `WP_REST_Response` (or array). After a successful action the React client shows the response body's `message` as a snackbar, then refetches settings and status. If the body includes `'reload' => true`, the client performs a full page reload after the snackbar **instead of** the settings/status refetch:

```php
'callback' => function ($request) {
    // ...changed something the schema is derived from...
    return new \WP_REST_Response([
        'success' => true,
        'message' => __('License activated.', 'my-plugin'),
        'reload'  => true,
    ]);
},
```

Use `reload` when the action changes schema-derived output that a settings/status refetch alone would not pick up — section intros, `status` badge labels, field placeholders, or section `capability` visibility. Plain actions omit it and behave as before.

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
