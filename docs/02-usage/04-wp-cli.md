---
title: 'WP-CLI Commands'
post_excerpt: 'Manage settings from the command line — get, set, reset, backup, restore, export, and import.'
menu_order: 40
---

# WP-CLI Commands

Every plugin built on MilliBase automatically gets a full set of WP-CLI commands for managing settings. Commands are registered under `config` to avoid conflicts with plugin-specific commands.

```bash
wp <slug> config <subcommand> [options]
```

## Configuration

The `'cli'` key on the Manager config controls registration:

| Value                 | Behaviour                                      |
|-----------------------|------------------------------------------------|
| `true` (or omitted)   | Register under `wp <slug> config <subcommand>` |
| `false`               | Skip CLI registration entirely                 |
| `['slug' => 'other']` | Register under `wp other config <subcommand>`  |

```php
$manager = new \MilliBase\Manager(
    slug: 'my-plugin',
    config: fn() => [
        'tabs' => [ /* ... */ ],
        'cli'  => [ 'slug' => 'mp' ],   // wp mp config get / set / ...
    ],
);
```

The explicit `cli.slug` form exists for two reasons: shorter operator-facing command names, and **auto-merge** across multiple Managers (see [below](#auto-merge)).

## Available Commands

| Command   | Description                         |
|-----------|-------------------------------------|
| `get`     | Read one or all settings            |
| `set`     | Update a single setting             |
| `reset`   | Reset settings to defaults          |
| `backup`  | Create a backup of current settings |
| `restore` | Restore from the most recent backup |
| `export`  | Export settings as JSON             |
| `import`  | Import settings from a JSON file    |

## Reading Settings

### Get all settings

```bash
wp myplugin config get
```

Outputs a table with all settings using dot-notation keys:

```
+------------------+-------+
| key              | value |
+------------------+-------+
| cache.enabled    | true  |
| cache.ttl        | 3600  |
| storage.host     | redis |
| storage.port     | 6379  |
+------------------+-------+
```

### Get a module

```bash
wp myplugin config get cache
```

```
+-----------------+-------+
| key             | value |
+-----------------+-------+
| cache.enabled   | true  |
| cache.ttl       | 3600  |
+-----------------+-------+
```

### Get a single value

```bash
wp myplugin config get cache.ttl
# 3600
```

Returns the raw value — useful for quick checks and scripting:

```bash
TTL=$(wp myplugin config get cache.ttl --format=json)
```

### Show setting sources

Add `--show-source` to see where each value comes from:

```bash
wp myplugin config get --show-source
```

```
+------------------+-------+----------+
| key              | value | source   |
+------------------+-------+----------+
| cache.enabled    | true  | db       |
| cache.ttl        | 3600  | default  |
| storage.host     | redis | constant |
| storage.port     | 6379  | default  |
+------------------+-------+----------+
```

### Output formats

All read commands support `--format=json|table|yaml|csv`:

```bash
wp myplugin config get --format=json
wp myplugin config get cache --format=yaml
```

When using `--format=json`, the raw nested JSON structure is output (not table row objects).

## Writing Settings

### Set a single value

```bash
wp myplugin config set cache.ttl 7200
# Success: Set 'cache.ttl' to "7200".
```

Values are automatically coerced:
- `true` / `false` → boolean
- `null` → null
- Numeric strings → int or float
- Everything else → string

Settings defined as constants cannot be set:

```bash
wp myplugin config set storage.host myhost
# Error: Cannot set 'storage.host' because it is defined as a constant.
```

Encrypted field values (`enc_*`) are masked in output for security:

```bash
wp myplugin config set storage.enc_password mysecret
# Success: Set 'storage.enc_password' to "***".
```

## Reset

```bash
# Reset all settings (creates automatic backup).
wp myplugin config reset

# Reset a specific module.
wp myplugin config reset --module=cache

# Skip the confirmation prompt.
wp myplugin config reset --yes
```

## Backup and Restore

```bash
# Create a backup (expires in 12 hours).
wp myplugin config backup

# Restore from the most recent backup.
wp myplugin config restore
```

## Export and Import

### Export to a file

```bash
# Export to stdout.
wp myplugin config export

# Export directly to a file.
wp myplugin config export --file=settings.json

# Export a single module.
wp myplugin config export --module=cache

# Include decrypted values of encrypted fields.
wp myplugin config export --include-encrypted --file=full-backup.json
```

### Import from a file

A backup is created automatically before every import.

```bash
# Import and merge with existing settings.
wp myplugin config import --file=settings.json

# Import and replace all settings.
wp myplugin config import --file=settings.json --no-merge --yes
```

### Migrate between environments

```bash
# On staging:
wp myplugin config export --file=settings.json

# On production:
wp myplugin config import --file=settings.json
```

## Auto-Merge

When two Managers in the same plugin resolve to the **same CLI command name** — either by sharing a default slug or by setting an explicit `cli.slug` — MilliBase auto-merges them into a single command tree. Operators see one `wp <slug> config` command that transparently routes by module across both backends.

The typical case is a plugin running per-site Settings and network-wide Settings side-by-side (see [Network Settings](./06-network-settings.md#the-two-manager-pattern)):

```php
new \MilliBase\Manager(
    slug: 'my-plugin',
    config: fn() => [
        // network: false (default) — wp_options
        'tabs' => [ /* cache, rules */ ],   // modules: 'cache', 'rules'
    ],
);

new \MilliBase\Manager(
    slug: 'my-plugin-network',
    config: fn() => [
        'network' => true,                    // wp_sitemeta
        'tabs'    => [ /* storage, auth */ ], // modules: 'storage', 'auth'
        'cli'     => [ 'slug' => 'my-plugin' ],
    ],
);
```

Operator UX:

```bash
wp my-plugin config get cache.ttl       # routes to the per-site Settings
wp my-plugin config get storage.host    # routes to the network Settings
wp my-plugin config get                 # merged tree from both
wp my-plugin config set storage.host redis.internal
wp my-plugin config reset --module=auth
```

### Routing Semantics

Each Settings instance declares its modules via the schema defaults. The merged command (technically a `MilliBase\Settings\Group` wrapping both Settings) dispatches based on the leading module of a dot-notation key:

| Subcommand | Behaviour |
|---|---|
| `get <module>.<key>` | Routes to the Settings that owns `<module>`. Falls back to the primary (first-registered) Settings if no owner. |
| `get` (no key) | Merges full trees from every wrapped Settings. |
| `set <module>.<key> <value>` | Routes to the owning Settings. Returns nothing-to-set if no Settings owns the module. |
| `reset` (no module) | Resets every wrapped Settings. |
| `reset --module=<module>` | Routes to the owner only. |
| `backup` / `restore` | Fan-out across every wrapped Settings; restore succeeds if any wrapped Settings restored successfully. |
| `export` / `import` | `export` merges across all; `import` buckets each top-level module by its owner. Unknown modules are silently skipped. |

The first Manager to register the command wins the role of "primary" — subsequent Managers append into the existing group. Order of Manager construction is the order of `add()` calls.

### When **Not** To Auto-Merge

Each Settings backend has its own capability boundary (typically `manage_options` per-site vs `manage_network_options` network-wide), but a single WP-CLI command runs with the invoking user's capabilities. If you have differential trust requirements between the two backends, **don't share a `cli.slug`** — keep them as `wp my-plugin config` and `wp my-plugin-network config` so the command names reflect the capability boundary.

## Next Steps

- **[Programmatic Access](./03-programmatic-access.md)** — the PHP API behind these commands
- **[Settings API Reference](../04-reference/02-settings-api.md)** — full method documentation
- **[Hooks and Filters](../04-reference/03-hooks-and-filters.md)** — hooks fired on setting changes
