---
title: 'Migrations'
post_excerpt: 'Declarative one-shot migrations attached to a Manager — identity, scopes, ordering, and failure handling.'
menu_order: 50
---

# Migrations

MilliBase ships a small declarative migration runner that's wired into the `Manager` config. Each migration is an entry in the `'migrations'` array; the runner executes any entries that haven't run yet on `init` (priority 5), records the outcome, and skips them on subsequent loads.

This is intentionally minimal — there's no schema introspection, no rollback, no "down" step. The runner exists for one job: ship a one-shot data fix-up alongside a plugin release and trust that it ran exactly once per identity.

## Quick Example

```php
$manager = new \MilliBase\Manager(
    slug: 'my-plugin',
    config: fn() => [
        'tabs'       => [ /* ... */ ],
        'migrations' => [
            [
                'name'     => 'rename-cache-ttl-key',
                'version'  => '2.5.0',
                'scope'    => 'site',
                'callback' => function (\MilliBase\Manager $manager): void {
                    $settings = $manager->settings();
                    $legacy   = $settings->get('cache.legacy_ttl');
                    if ( null !== $legacy ) {
                        $settings->set('cache.ttl', (int) $legacy);
                    }
                },
            ],
        ],
    ],
);
```

## Entry Shape

| Key | Type | Description |
|---|---|---|
| `name` | `string` | Stable identifier — names a logical migration. Required. |
| `version` | `string` | Version stamp. Combined with `name` to form the run-once identity. Required. |
| `scope` | `'site' \| 'network'` | Which storage table holds the state row. Defaults to `'site'`. |
| `callback` | `callable(Manager): void` | Runs the migration. Receives the Manager instance. |

Entries that fail any of these checks are **silently skipped** (no error, no state row written) — keep them out of your config unless every field is set:

- `name` or `version` missing / not a string / empty
- `scope` is not `'site'` or `'network'`
- `callback` is not callable

## Identity: `name@version`

Each migration's identity is `name@version`. The runner records the outcome under that key — so:

- **Bump the version to re-run.** This is the intended escape hatch for "I shipped the migration wrong." Changing the version produces a new identity and the runner treats it as a fresh entry.
- **Don't rename a `name` to "force a re-run"** — the state row from the old name will linger forever. Bump the `version` instead.
- **Don't reuse a `name@version` for a different operation.** If you ever re-released the same identity with different code, sites that ran v1 will skip v2 silently.

## Scopes

`scope` decides which table the state row lives in:

| Scope | State row location | Use when |
|---|---|---|
| `'site'` (default) | `wp_options['<slug>_migration_state']` | Per-site data — runs once per blog on multisite. |
| `'network'` | `wp_sitemeta['<slug>_migration_state']` | Network-wide data — runs once per network. |

On a non-multisite install, **network-scoped migrations are silently skipped and re-evaluated on every load.** That's intentional: if the install later becomes multisite (or the migration was added pre-multisite-conversion), the runner picks it up automatically without operator action.

A single migration list can mix scopes freely. The runner reads each scope's state row lazily (only when it hits the first migration of that scope) and writes it once at the end of the run.

## Ordering

**Array order is the source of truth.** Migrations run top-to-bottom in the order they appear in your config, across both scopes. If `A` (site) appears before `B` (network) in the array, `A` runs first.

This means: when adding a new migration, **append to the end of the array.** Inserting a new entry in the middle doesn't change anything about already-completed migrations (their identity is unchanged), but it does break the mental model that earlier-in-the-array means earlier-in-history.

## Failure Handling

The runner catches `\Exception` thrown from the callback. The state row for that identity becomes:

```php
[ 'failed', $exception->getMessage(), $timestamp ]
```

On the next load, the runner sees the state row exists and **skips the migration** — failed migrations do not auto-retry. To retry, an operator (or a follow-up migration) must remove the entry from the state row.

`\Error` (programmer bugs — typos, missing classes) is **not caught** — it bubbles normally, so the failure surfaces during development instead of being silently buried in a state row.

## Reading and Clearing State

The state row is just a regular option keyed by `<slug>_migration_state`:

```php
// Inspect.
$state = get_option('my-plugin_migration_state', []);
// or, on multisite for network-scope:
$state = get_site_option('my-plugin_migration_state', []);

// Clear a single failed migration to allow retry on next load.
unset($state['rename-cache-ttl-key@2.5.0']);
update_option('my-plugin_migration_state', $state);
```

There's no built-in CLI command for this — the runner is intentionally small. If you need to retry a failed migration in production, edit the state row directly or ship a follow-up migration with a bumped version.

## Timing

The runner is scheduled on `init` priority `5` so it lands after MilliBase's own setup (priority `0`) and before most plugin code (default priority `10`). Downstream code that reads settings on `init` priority `10` sees already-migrated state.

If the Manager is constructed after `init` has already fired (rare — typically only in test bootstrap), the runner is invoked immediately rather than hooked.

## Next Steps

- **[Network Settings](./06-network-settings.md)** — when to use `scope: 'network'` and how it interacts with network-mode Settings
- **[Configuration](./01-configuration.md#migrations)** — the `migrations` config key in the wider Manager reference
