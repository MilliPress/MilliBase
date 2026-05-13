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

| Value               | Behaviour                                      |
|---------------------|------------------------------------------------|
| `true` (or omitted) | Register under `wp <slug> config <subcommand>` |
| `false`             | Skip CLI registration entirely                 |

```php
$manager = new \MilliBase\Manager(
    slug: 'my-plugin',
    config: fn() => [
        'tabs' => [ /* ... */ ],
        'cli'  => false,   // disable WP-CLI registration for this Manager
    ],
);
```

When two Managers share the same plugin slug — the standard site + network split — they auto-merge into one command tree (see [Network scope](#network-scope) below).

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

## Network scope

Every subcommand accepts `[--network]`. The flag picks which Settings instance the call operates on when a plugin runs both per-site and network Managers under the same primary slug:

```bash
# Default: operate on the per-site Settings.
wp myplugin config get rules

# With --network: operate on the network-scoped Settings.
wp myplugin config get rules --network

# Same for every other subcommand.
wp myplugin config set rules.exclude /admin --network
wp myplugin config reset --network --yes
wp myplugin config export --network --file=network.json
```

The typical case is a plugin running per-site Settings and network-wide Settings side-by-side (see [Network Settings](./06-network-settings.md#the-two-manager-pattern)):

```php
new \MilliBase\Manager(
    slug: 'my-plugin',
    config: fn() => [
        'tabs' => [ /* … */ ],         // per-site Settings — modules: 'cache', 'rules'
    ],
);

new \MilliBase\Manager(
    slug: 'my-plugin',                  // ← same primary slug
    config: fn() => [
        'network' => true,              // ← network-scoped storage
        'tabs'    => [ /* … */ ],       // network Settings — modules: 'storage', 'auth'
    ],
);
```

Both Managers register `wp my-plugin config …` against the same command tree; each subcommand picks the right Settings via `--network` at call time. There's no shared-module routing magic — the operator picks the scope explicitly, so `--module=rules` on a plugin where both Settings declare a `rules` module reads or writes exactly the scope the flag selects.

### Resolution rules

The flag's behaviour depends on what the plugin actually registered:

| Plugin config | Default (no `--network`) | `--network` |
|---|---|---|
| Per-site only Manager | Per-site Settings | **Error** — no network Settings registered |
| Network-only Manager | Network Settings (fall-through) | Network Settings (redundant but works) |
| Both (site + network) | Per-site Settings | Network Settings |
| Single-site WordPress | Per-site Settings | **Error** — same path as "Per-site only" |

The `--network` flag is always declared in every subcommand's synopsis, regardless of what the plugin registered. Operators see it in `--help` and learn one pattern across every MilliBase-based plugin.

### Capability boundary

WP-CLI runs as the invoking user (set via `--user=<id>`), but the regular `wp <slug> config …` commands are operator-level — they don't enforce caps the way REST/abilities surfaces do. If you have differential trust requirements between site and network operators, the CLI is not your gate; rely on filesystem/SSH access control instead.

## Next Steps

- **[Programmatic Access](./03-programmatic-access.md)** — the PHP API behind these commands
- **[Settings API Reference](../04-reference/02-settings-api.md)** — full method documentation
- **[Hooks and Filters](../04-reference/03-hooks-and-filters.md)** — hooks fired on setting changes
