---
title: 'Network Settings'
post_excerpt: 'Network-scoped settings on multisite: storage routing, admin placement, and the two-Manager pattern.'
menu_order: 60
---

# Network Settings

On WordPress multisite some plugin settings belong to the *network*, not to an individual site — shared storage credentials, an API token used by every site, infrastructure-level rules. MilliBase models this with a single config flag.

## The `'network'` Flag

Set `'network' => true` on a Manager and two things flip together:

| What changes | Without `network` | With `network` |
|---|---|---|
| Storage backend | `wp_options` via `get_option` / `update_option` | `wp_sitemeta` via `get_site_option` / `update_site_option` |
| Sanitize hook | `pre_update_option_<name>` | `pre_update_site_option_<name>` |
| Read filter | `option_<name>` | `site_option_<name>` |
| Default filter | `default_option_<name>` | `default_site_option_<name>` |
| Backups | `set_transient` | `set_site_transient` |
| Admin menu | `admin_menu` | `network_admin_menu` (multisite only) |
| Config-file mirror | `{host}.php` | `_network-{network_id}.php` |

The Settings API surface is unchanged — `$settings->get('foo.bar')`, `set()`, `reset()`, `update()`, `delete()` all work identically. Callers don't need to know which backend the data lives in.

On a non-multisite install the `network` flag still routes storage to the network functions (which WordPress polyfills to call `get_option` / `update_option`), but the admin menu placement is silently dropped — a stray `'network' => true` on single-site doesn't hide the page.

## The Two-Manager Pattern

The cleanest pattern for "some settings per-site, some network-wide" is **two Managers in the same plugin**, each owning a distinct option name and module set:

```php
// Per-site settings — cache rules, debug toggles, etc.
$per_site = new \MilliBase\Manager(
    slug: 'my-plugin',
    config: fn() => [
        'tabs' => [ /* rules tab, etc. */ ],
        // 'network' => false (default)
    ],
);

// Network settings — shared storage credentials, etc.
$network = new \MilliBase\Manager(
    slug: 'my-plugin',                  // ← same primary slug
    config: fn() => [
        'network' => true,
        'tabs'    => [ /* storage tab, etc. */ ],
    ],
);
```

The two Managers share the same primary slug. MilliBase auto-disambiguates them per surface:

| Surface | Per-site Manager | Network Manager |
|---|---|---|
| Settings storage | `wp_options[my-plugin]` | `wp_sitemeta[my-plugin]` (different table — no row collision) |
| Admin menu | `admin_menu` hook | `network_admin_menu` hook (separate menu trees in WP core) |
| REST routes | `/wp-json/my-plugin/v1/…` | `/wp-json/my-plugin/v1/network/…` (route prefix) |
| Abilities | `my-plugin/settings-export`, `-reset`, `-backup`, `-restore` | `my-plugin/settings-export-network`, `-reset-network`, `-backup-network`, `-restore-network` (id suffix) |
| WP-CLI | `wp my-plugin config …` (single tree) | `wp my-plugin config … --network` (same tree, `--network` flag) |

One principle (`network=true` is the disambiguator), three idiomatic mechanisms (route prefix for REST, id suffix for abilities, flag for CLI). See [WP-CLI Commands](./04-wp-cli.md#network-scope) and [Abilities API](./05-abilities.md#multi-manager-network-scope) for the surface-specific details.

> **Why two Managers and not one?** The original temptation is a single Manager with mixed-scope modules. That conflicts with WordPress: an option lives in exactly one table (`wp_options` or `wp_sitemeta`), and the sanitize / filter / cap-check chains differ per table. Splitting into two Managers keeps each option's lifecycle clean and makes capability boundaries explicit (typically `manage_options` for per-site, `manage_network_options` for network).

## Config-File Mirror

When `'config_file'` is also configured, the network-mode Manager writes to `{directory}/_network-<network_id>.php`. The `<network_id>` comes from `get_current_network_id()` (or `SITE_ID_CURRENT_SITE`, or `1` as a final fallback), so it lines up exactly with the `site_id` column in `wp_sitemeta`.

The leading underscore is intentional: in a directory holding both per-site files (`example_com.php`, `example_com_blog2.php`) and network files (`_network-1.php`), the network files sort to the top alphabetically. This matters for `advanced-cache.php` reads that iterate the directory.

Single-site reads from `advanced-cache.php` resolve their own filename via `Settings::standalone()` and don't see network files. Multisite reads use the same `resolve_domain()` logic on both sides of the WP boundary, so the filename a writer produces matches what a pre-WP reader looks for.

## Backups

Network-mode backups use `set_site_transient` / `get_site_transient`, so a backup created on one site in the network is visible to every site. Restore from any site rolls the entire network back to that snapshot.

## Migrations

Pair network-mode settings with `'scope' => 'network'` migrations — the migration state row also lives in `wp_sitemeta`, so each network-wide migration runs exactly once per network instead of once per site. See [Migrations](./05-migrations.md#scopes).

## REST API

The namespaced REST endpoints (`GET/POST /{namespace}/v1/settings`) work identically against a network-mode Manager — the controller calls `Settings::get()` and `Settings::update()`, which auto-route based on the flag. The built-in `__reset` action also routes to `delete_site_option` in network mode, so the right table gets cleared.

Capability and nonce checks still apply per request. A network admin endpoint typically wants `capability => 'manage_network_options'` instead of the default `manage_options`.

## What `'network'` Does **Not** Do

- **It does not gate fields by capability.** Every field exposed on a network-mode page is editable by anyone passing the page's `capability` check. If you need finer-grained gating (e.g., "only super-admins can change `storage.host`"), do that in the field's schema or via a `pre_update_site_option_<name>` filter at a priority higher than `-100`.
- **It does not auto-create a second admin page.** You need two Managers (or `$manager->add_page()` with an additional config) if you want both a per-site page and a network-admin page.
- **It is not a multisite synchronisation mechanism.** Per-site Settings stay per-site; this flag just relocates the storage of *this* Manager's option.

## Next Steps

- **[WP-CLI Commands](./04-wp-cli.md)** — auto-merge of two Managers into one CLI tree
- **[Migrations](./05-migrations.md)** — `scope: 'network'` for network-wide one-shot fixes
- **[Configuration](./01-configuration.md#network)** — the `network` config key reference
