---
title: 'Abilities API'
post_excerpt: 'Expose plugin operations to AI agents and MCP clients through the WordPress Abilities API.'
menu_order: 50
---

# Abilities API

The [WordPress Abilities API](https://developer.wordpress.org/apis/abilities-api/) is a unified registry for plugin-defined operations. It is core since [WP 6.9](https://make.wordpress.org/core/2025/11/10/abilities-api-in-wordpress-6-9/) and available as a feature plugin on earlier versions. A plugin describes an operation once — label, description, JSON schemas, callback — and WordPress exposes it through the abilities REST endpoint, the WP-Admin command palette, MCP clients, and `wp ability` on the CLI, without per-surface wiring.

MilliBase wraps the registration boilerplate so plugin authors describe abilities declaratively in their config array, parallel to how `actions` describes custom REST endpoints. Abilities are an *orthogonal* surface to REST actions — core's guidance is that they should be a curated, schema-rigorous subset shaped for AI agents, not a 1:1 mirror of the REST API.

A complete consumer plugin wires this up via `MilliBase\Manager`:

```php
new \MilliBase\Manager(
    'my-plugin',
    static fn () => [
        'tabs'     => [ /* ... */ ],
        'abilities' => [ /* ... see below ... */ ],
    ],
);
```

The constructor takes a slug string and a closure returning the config array. The closure is called on `init` so `__()` calls inside it resolve after textdomains have loaded. The Manager hooks itself into `init` priority 0 — there is no need to call `boot()` manually unless you construct the Manager *after* `init` has already fired. See `01-configuration.md` for every config key.

## Quick example

```php
'abilities' => [
    [
        'id'          => 'cache-purge',
        'label'       => __( 'Purge Cache', 'my-plugin' ),
        'description' => __( 'Clears the cache for one or more targets.', 'my-plugin' ),
        'callback'    => [ $cache, 'purge' ],
        'input_schema'  => [
            'type'       => 'object',
            'properties' => [
                'target' => [ 'type' => 'string' ],
            ],
        ],
        'output_schema' => [
            'type'       => 'object',
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'count'   => [ 'type' => 'integer' ],
            ],
            'required' => [ 'success' ],
        ],
    ],
],
```

Registers as `my-plugin/cache-purge` under the `my-plugin` ability category. Inherits `manage_options` from the plugin-default `capability` for its permission check.

## Entry fields

| Field                 | Required | Description |
|-----------------------|----------|-------------|
| `id`                  | yes      | Stable identifier. Auto-prefixed with the plugin slug to form `<slug>/<id>`. Lowercase alphanumeric and dashes only. |
| `label`               | yes      | Human-readable name shown in command palettes and docs. Non-empty string. |
| `description`         | yes      | What the ability does and when to use it. Non-empty string. AI clients read this to pick the right operation. |
| `callback`            | yes      | The PHP callable that executes the ability. Receives the validated input directly (not a `WP_REST_Request`). Returns the result or a `WP_Error`. |
| `capability`          | no       | Capability string for the auto-generated permission callback. Defaults to the plugin-default `capability`. |
| `permission_callback` | no       | Callable that decides whether the current user may execute. Receives the same input as `callback`, returns `bool` or `WP_Error`. Overrides `capability` when set. |
| `input_schema`        | no       | JSON Schema describing expected input. Core validates input against this schema *before* invoking the callback, and the callback only receives input when a schema is set — abilities that accept any input therefore must declare one. An empty array (`[]`) is treated as "omit"; pass the full empty-object schema (`['type' => 'object', 'additionalProperties' => false]`) when you want to forbid any input. |
| `output_schema`       | no       | JSON Schema describing the return shape. When set, core validates the return value and emits `ability_invalid_output` on mismatch. Recommended for non-trivial outputs. |
| `meta`                | no       | Pass-through metadata. Notable keys: `meta.show_in_rest` (bool, default false) controls visibility under `/wp-abilities/v1/`; `meta.annotations.{readonly,destructive,idempotent}` describe behaviour for tooling; `meta.mcp.public` is read by the [MCP adapter](https://github.com/WordPress/mcp-adapter) to decide MCP exposure. MilliBase passes the whole `meta` array through unchanged. |

## ID prefixing

Bare ids get the plugin slug prefixed automatically:

```
'id' => 'cache-purge'  →  'my-plugin/cache-purge'
```

Ids may not contain a forward slash. An id like `'other-plugin/something'` is skipped with a `_doing_it_wrong()` notice — a host plugin should not be able to shadow another plugin's namespace, which is what would happen by accident if MilliBase registered such ids verbatim and the foreign owner loaded later in the request.

If you genuinely need to register an ability under a different category (rare), declare that category explicitly via your own `add_action( 'wp_abilities_api_categories_init', ... )` and `wp_register_ability()` outside the MilliBase config.

## Validation rules

A registered ability name is exactly two segments separated by one slash — `<namespace>/<id>` — and each segment must match the lowercase-alphanumeric-with-dashes shape. Plugin slugs are validated against the single-segment form, ability names against the full two-segment form:

```
slug:  ^[a-z0-9]+(?:-[a-z0-9]+)*$
name:  ^[a-z0-9]+(?:-[a-z0-9]+)*\/[a-z0-9]+(?:-[a-z0-9]+)*$
```

No underscores, no dots, no uppercase. Multi-slash names like `foo/bar/baz` and foreign-namespace ids like `other-plugin/something` are both rejected up-front (see [ID prefixing](#id-prefixing) above). MilliBase validates the slug too: if the slug fails the single-segment shape, no category or abilities are registered for that plugin (silent skip — the host site keeps working). Individual entries are skipped when their resolved name fails the two-segment regex, when the entry is not an array, when `id`, `label`, or `description` is missing or empty, or when `callback` is not a callable.

Idempotent guards: MilliBase consults `wp_has_ability_category()` and `wp_has_ability()` before registering, so a Manager constructed twice in one request (test harness, plugin reactivation) does not trigger core's duplicate-registration warning. The first Manager that registers a slug "owns" the category and any ability ids it claims; later Managers using the same slug can still add net-new ids to that namespace, but cannot overwrite the originals — relevant when two plugins on the same site share a slug by accident or by design.

Type coercion: core uses the WP REST API's permissive sanitisation for `input_schema` validation. A JSON Schema `integer` field will accept the string `"42"` (coerced to `42`), but a boolean or a float fails type-check. Plugin authors can tighten validation with `additionalProperties: false` plus explicit `required` lists; AI clients sending unknown fields then receive a clear validation error instead of having those fields silently dropped.

## Permission resolution

Each ability's `permission_callback` is resolved in priority order:

1. An explicit callable in `'permission_callback'` wins.
2. Otherwise, a `'capability'` string on the entry produces a closure that runs `current_user_can($capability)`.
3. Otherwise, the plugin-default `'capability'` (top-level config) is used the same way.

The default plugin-default capability is `manage_options` when not configured.

## Ability category

Every plugin registers exactly one ability category. Slug = plugin slug. By default the label comes from `menu_title` (falling back to the slug) and the description is auto-generated:

```
"Operations exposed by {label}."
```

Override either or both via the optional `'abilities_category'` config sub-array:

```php
'abilities_category' => [
    'label'       => __( 'My Plugin', 'my-plugin' ),
    'description' => __( 'Cache, preload, and diagnostic operations exposed by My Plugin.', 'my-plugin' ),
],
```

## Built-in settings abilities

Set `'expose_settings_abilities' => true` to have MilliBase auto-register four abilities that wrap the built-in Settings operations:

| Ability id          | Operation                                    | Annotations         |
|---------------------|----------------------------------------------|---------------------|
| `settings-export`   | Export settings as a module-keyed object     | `readonly`          |
| `settings-reset`    | Reset to defaults (creates a backup first)   | `destructive`       |
| `settings-backup`   | Take a 12-hour backup of current settings    | `idempotent`        |
| `settings-restore`  | Restore from the most recent backup          | `destructive`       |

These abilities are appended to the `'abilities'` array. The registration loop runs in order, so a host-plugin entry registers first; the framework duplicate that follows is then skipped via `wp_has_ability()`. Net effect: a host plugin can override any framework ability by declaring its own entry with the same id:

```php
'abilities' => [
    // Custom export with project-specific behaviour — wins over the framework version.
    [
        'id'          => 'settings-export',
        'label'       => __( 'Export with audit log', 'my-plugin' ),
        'description' => __( 'Exports settings AND records the export in the audit log.', 'my-plugin' ),
        'callback'    => [ $exporter, 'export_with_audit' ],
        'output_schema' => [ /* ... */ ],
    ],
],
'expose_settings_abilities' => true,
```

`meta.show_in_rest` is intentionally left unset on the framework abilities — they register in PHP and CLI, but plugin authors must opt in per-ability to expose them under `/wp-abilities/v1/`.

The framework abilities pin their capability to `manage_options` (`manage_network_options` on multisite), regardless of the host plugin's default `'capability'`. Reset/restore/backup are destructive over the host's settings store. A plugin that runs its admin UI on a lower cap (for example `'capability' => 'edit_posts'`) still gets admin-only framework abilities. Override only via the same-id host-wins mechanism above; the cap on a host override applies as written.

> [!WARNING]
> **`settings-export` is a credential disclosure surface.** Opting it into REST (`meta.show_in_rest => true`) plus a call with `include_encrypted=true` returns every encrypted secret in plain text to any authenticated user holding `manage_options` — including anyone the admin has issued an Application Password to. If your plugin stores API keys, OAuth tokens, or any other credential in encrypted settings, leave `show_in_rest` off and run the ability from `wp ability` or PHP only. If you must expose `settings-export` over REST, override it with a host-defined version that rejects `include_encrypted` or audits the call.

### Multi-Manager auto-merge

When two or more `Manager` instances share the same plugin slug, they merge their `Settings` into a single `Settings\Group` for the abilities surface — same auto-merge as `wp <slug> config`. The four framework abilities register once against the Group; `settings-export` returns the modules from every backing `Settings`, and `settings-reset`/`settings-backup` fan out via the Group's per-module routing. Host plugins that split their state across (for example) a per-site option and a network option see one unified abilities surface instead of having the second Manager's Settings silently dropped via `wp_has_ability()`.

## Errors

When the `callback` returns a `WP_Error`, core relays it to the caller using its standard REST translation: HTTP status from the error's `status` data (default 500), JSON body with `code`, `message`, and `data`. Returning `WP_Error` is the contracted failure path.

Uncaught exceptions are wrapped. MilliBase catches any `Throwable` thrown by either the `callback` *or* an explicit `permission_callback` and converts it to `WP_Error('ability_callback_exception', …, ['status' => 500])` so a stray `RuntimeException` cannot leak a stack trace through the REST surface — particularly relevant on Acorn/Roots stacks where the Laravel error renderer would otherwise emit absolute filesystem paths into HTML comments. The full trace is written to `error_log()` for server-side debugging.

## HTTP method via annotations

The HTTP verb on the run endpoint is derived from `meta.annotations`, not chosen by the plugin:

| Annotation combination                  | HTTP method |
|-----------------------------------------|-------------|
| `readonly: true`                        | GET         |
| `destructive: true` + `idempotent: true`| DELETE      |
| anything else (default)                 | POST        |

Set the annotation that describes your operation; the verb follows. The framework abilities are annotated accordingly: `settings-export` is `readonly` (GET), `settings-reset` and `settings-restore` are `destructive` (POST), `settings-backup` is `idempotent` (POST).

## Registration timing

Abilities API registration is lazy by design: the registry fires its init actions on first access (a request to `/wp-abilities/v1/...`, a `wp ability` CLI invocation, or direct PHP code touching the registry). MilliBase attaches both `wp_abilities_api_categories_init` and `wp_abilities_api_init` during `Manager::boot()`, which runs on `init` priority 0 (or immediately when the Manager is constructed on/after `init`). The underlying registry orders categories before abilities.

When neither WP core 6.9+ nor the abilities-api feature plugin is available, `wp_register_ability()` is undefined. `Manager::boot()` still attaches the abilities-api hooks, but the callbacks function-exist-check and no-op when fired. The host plugin keeps working; abilities are simply not exposed. Consumer plugins gating their own UI on the API can call `Manager::abilities_active()` instead of grepping for the function name.

## Discovery vs execution

Authentication is required to reach any abilities-api endpoint, but the listing endpoint (`GET /wp-abilities/v1/abilities`) is filtered only by `meta.show_in_rest` — not by per-caller capability. A subscriber-level user can therefore enumerate every REST-exposed ability on the site, including admin-only ones, even though they cannot execute them. This is core abilities-api behaviour, not a MilliBase choice. For MCP deployments where untrusted users authenticate (membership sites, WooCommerce subscribers), treat `meta.show_in_rest = true` as "globally listable" and weigh it accordingly when adding abilities to sensitive surfaces.

## Testing

The simplest path is the [`wp-cli/ability-command`](https://github.com/wp-cli/ability-command) package, which is **not bundled with WordPress core or with MilliBase** — install it explicitly first:

```bash
composer global require wp-cli/ability-command
```

Then query and run abilities from any site that has `wp-cli` available:

```bash
wp ability list                                              # every registered ability
wp ability list --category=my-plugin                         # only this plugin's
wp ability list --show-in-rest=1                             # only REST-exposed
wp ability get my-plugin/cache-purge                         # full metadata for one
wp ability run my-plugin/cache-purge --user=1 --input='{"target":"home"}'
wp ability run my-plugin/cache-purge --user=1 --target=home  # alt: --<field>=<value> for flat inputs
```

CLI runs without an authenticated user by default, so abilities whose permission callback uses `current_user_can()` will fail with *"does not have necessary permission"*. Pass `--user=<id|login|email>` to run as a specific user.

Via REST — abilities live under `/wp-json/wp-abilities/v1/abilities/`, and the run endpoint's HTTP method is determined by the ability's annotations (`readonly` → GET, `destructive` + `idempotent` → DELETE, otherwise POST):

```bash
# List all abilities (requires authenticated user with read access).
curl -u admin:app-password https://example.com/wp-json/wp-abilities/v1/abilities

# Fetch metadata for one ability.
curl -u admin:app-password https://example.com/wp-json/wp-abilities/v1/abilities/my-plugin/cache-purge

# Execute an ability — note the `input` wrapper in the body and the `/abilities/` segment in the path.
curl -u admin:app-password https://example.com/wp-json/wp-abilities/v1/abilities/my-plugin/cache-purge/run \
    -X POST -H 'Content-Type: application/json' \
    -d '{"input":{"target":"home"}}'
```

The list endpoint paginates: `?per_page=50` is the default with `X-WP-Total` and `X-WP-TotalPages` response headers. Clients enumerating a large ability surface need to walk pages explicitly — a single `GET /wp-abilities/v1/abilities` against a busy site silently truncates. Pass `?per_page=100` (the maximum) and follow `X-WP-TotalPages`.

JSON-Schema integer fields use the loose JSON-Schema definition: any number with no fractional part validates, including values larger than `PHP_INT_MAX` that PHP coerces to float. Clients receive imprecise floats where they expected integers. For sensitive numeric fields, narrow the range with `minimum`/`maximum` or use `string` with a regex pattern.

For PHP-level checks (unit tests, conditional logic), the registry exposes:

| Function                                 | Returns                            |
|------------------------------------------|------------------------------------|
| `wp_get_ability( 'my-plugin/foo' )`      | `WP_Ability` instance, or `null`   |
| `wp_has_ability( 'my-plugin/foo' )`      | `bool`                             |
| `wp_get_abilities()`                     | `WP_Ability[]`                     |
| `wp_get_ability_category( 'my-plugin' )` | `WP_Ability_Category`, or `null`   |
| `wp_has_ability_category( 'my-plugin' )` | `bool`                             |
| `$manager->abilities_active()`           | `bool` — soft-detect from MilliBase, stable from `plugins_loaded` onwards |

## Site-level abilities (Acorn-based projects)

For *site-level* abilities outside any one plugin (e.g. Radicle/Acorn-based sites adding their own operations), [`roots/acorn-ai`](https://github.com/roots/acorn-ai) provides an OOP class-based registration with Laravel container DI and the `laravel/ai` agent framework. It targets a different audience (Acorn site authors, not distributable plugins) and is orthogonal to MilliBase's per-plugin abilities — the two can co-exist on the same site.

## Next Steps

- **[WordPress Abilities API handbook](https://developer.wordpress.org/apis/abilities-api/)** — canonical reference for the underlying API
- **[Configuration](./01-configuration.md)** — full plugin config reference
- **[Programmatic Access](./03-programmatic-access.md)** — read/write settings from your callbacks
