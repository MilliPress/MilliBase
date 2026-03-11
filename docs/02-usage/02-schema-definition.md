---
title: 'Schema Definition'
post_excerpt: 'Define tabs, sections, fields, and conditional display rules in your settings schema.'
menu_order: 20
---

# Schema Definition

The `tabs` array in the configuration defines the structure of your settings page. Each tab contains sections, and each section contains fields.

## Tab Structure

```php
'tabs' => [
    [
        'name'     => 'general',      // Unique tab identifier (required)
        'title'    => 'General',      // Display label
        'sections' => [ /* ... */ ],  // Array of section definitions
    ],
    [
        'name'      => 'advanced',
        'title'     => 'Advanced',
        'type'      => 'custom',         // Render a custom component instead of sections
        'component' => 'AdvancedTab',    // Registered component name
        'intro'     => 'Advanced configuration options.',
    ],
],
```

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | `string` | Yes | Unique identifier, used for tab keying and overrides |
| `title` | `string` | Yes | Display label shown in the tab bar |
| `sections` | `array` | No | Section definitions (for standard tabs) |
| `type` | `string` | No | Set to `'custom'` to render a custom component |
| `component` | `string` | No | Name of a registered custom component |
| `intro` | `string` | No | Introductory text shown above sections |

## Section Structure

```php
'sections' => [
    [
        'id'           => 'cache',          // Unique section identifier (required)
        'title'        => 'Cache Settings', // Collapsible panel title
        'icon'         => 'settings',       // Optional icon name
        'intro'        => 'Configure caching behavior.', // Optional intro text
        'open' => true,             // Whether panel starts open (default: true)
        'status'       => [                 // Optional runtime status badge
            'key'   => 'storage.connected',
            'ok'    => true,
            'badge' => ['ok' => 'Connected', 'error' => 'Disconnected'],
        ],
        'fields'       => [ /* ... */ ],    // Array of field definitions
    ],
],
```

| Property  | Type            | Required  | Description                                                                                             |
|-----------|-----------------|-----------|---------------------------------------------------------------------------------------------------------|
| `id`      | `string`        | Yes       | Unique identifier within the tab, used for overrides                                                    |
| `title`   | `string`        | Yes       | Panel heading                                                                                           |
| `icon`    | `string`        | No        | Icon name                                                                                               |
| `intro`   | `string`        | No        | Intro text or registered component name                                                                 |
| `open`    | `bool\|string`  | No        | Start expanded: `true`, `false`, `'ok'`, or `'error'` (default: `true`; `'error'` when `status` is set) |
| `active`  | `string\|array` | No        | Active toggle config — adds an on/off toggle to the section header (see below)                          |
| `status`  | `array`         | No        | Runtime status badge config (see below)                                                                 |
| `fields`  | `array`         | Yes       | Field definitions                                                                                       |

> [!TIP]
> The `intro` property can reference a registered custom component name. If `window.MilliBase.customComponents` contains a matching entry, it renders the component instead of plain text. This is useful for dynamic section descriptions.

### Section Active Toggle

The `active` property adds a `FormToggle` to the section header, letting users enable or disable an entire module. When toggled off, the section remains collapsible but all fields inside are disabled.

The toggle value is stored as a regular setting using the same dot-notation as fields.

```php
// String shorthand — defaults to false
'active' => 'cache.enabled',

// Array form — custom default
'active' => ['key' => 'minify.enabled', 'default' => true],
```

| Property   | Type     | Default  | Description                                 |
|------------|----------|----------|---------------------------------------------|
| `key`      | `string` | —        | Dot-notation setting key (`module.setting`) |
| `default`  | `bool`   | `false`  | Default toggle state                        |

The string shorthand `'cache.enabled'` is equivalent to `['key' => 'cache.enabled', 'default' => false]`.

Active-toggle defaults are extracted automatically by the Schema — no need to duplicate them in a defaults filter. Field defaults take precedence if the same key is defined both as a field and as an active toggle.

```php
// Module with toggle + fields: toggle in header, fields disabled when off
[
    'id'     => 'page-cache',
    'title'  => 'Page Cache',
    'active' => 'cache.enabled',
    'fields' => [
        ['key' => 'cache.ttl', 'type' => 'number', 'label' => 'TTL', 'default' => 3600],
    ],
],

// Module with toggle + status: both render in the header
[
    'id'     => 'redis',
    'title'  => 'Redis Object Cache',
    'active' => 'redis.enabled',
    'status' => [
        'key'   => 'redis.connected',
        'ok'    => true,
        'badge' => ['ok' => 'Connected', 'error' => 'Disconnected'],
    ],
    'fields' => [
        ['key' => 'redis.host', 'type' => 'text', 'label' => 'Host', 'default' => '127.0.0.1'],
    ],
],
```

### Section Status Badge

The `status` property ties a section to a runtime value from the status API, enabling a text badge in the panel header.

```php
'status' => [
    'key'   => 'storage.connected',   // Dot-path into the status object (required)
    'ok'    => true,                  // Value that means "all good" (required)
    'badge' => [                      // Text pill (ok/error labels)
        'ok'    => 'Connected',
        'error' => 'Disconnected',
    ],
],
```

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `key` | `string` | — | Dot-path into the runtime status object |
| `ok` | `mixed` | — | The value that indicates a healthy state |
| `badge` | `array` | — | Text pill with `ok` and `error` labels |

When `status` is configured, `open` defaults to `'error'` (auto-open when there's a problem). You can set `open` to `'ok'` to open only when the status is healthy — useful for sections whose fields are irrelevant while disconnected:

```php
// Connection section: opens when disconnected, shows badge
[
    'id'     => 'connection',
    'title'  => 'Storage Server',
    'status' => [
        'key'   => 'storage.connected',
        'ok'    => true,
        'badge' => ['ok' => 'Connected', 'error' => 'Disconnected'],
    ],
    // open defaults to 'error' — opens when disconnected
    'fields' => [ /* ... */ ],
],

// General section: opens only when connected
[
    'id'    => 'general',
    'title' => 'General Settings',
    'open'  => 'ok',
    'status' => [
        'key' => 'storage.connected',
        'ok'  => true,
    ],
    'fields' => [ /* ... */ ],
],
```

## Field Structure

```php
'fields' => [
    [
        'key'         => 'cache.ttl',       // Dot-notation: module.setting (required)
        'type'        => 'number',          // Field type (required)
        'label'       => 'Cache TTL',       // Display label
        'default'     => 3600,              // Default value
        'tooltip'     => 'Time-to-live in seconds.',
        'placeholder' => '3600',
        'min'         => 60,                // Type-specific (number, unit)
        'max'         => 86400,             // Type-specific (number, unit)
        'inline'      => true,              // Render on same row as previous field
        'width'       => '200px',           // Fixed width when inline
        'show'        => ['advanced.expert_mode', true],  // Show condition
        'hide'        => ['cache.disabled', true],        // Hide condition
    ],
],
```

### Required Properties

| Property | Type | Description |
|----------|------|-------------|
| `key` | `string` | Dot-notation key in `module.setting` format. The module (before the dot) groups settings in the stored option. |
| `type` | `string` | Field type: `text`, `number`, `password`, `toggle`, `select`, `unit`, `token-list`, `color`, `code` |

### Common Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `label` | `string` | `''` | Display label shown above the field |
| `default` | `mixed` | `null` | Default value extracted by the Schema |
| `tooltip` | `string` | — | Help text shown in an info icon tooltip |
| `placeholder` | `string` | — | Placeholder text (text, password, token-list) |
| `disabled` | `bool` | `false` | Render field as read-only |
| `encrypted` | `bool` | `false` | Hint for the UI (actual encryption uses `enc_` key prefix) |

### Layout Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `inline` | `bool` | `false` | Join this field to the previous field's row |
| `width` | `string` | — | CSS width when inline (e.g. `'200px'`) |

### Type-Specific Properties

| Property | Types | Description |
|----------|-------|-------------|
| `min` | `number`, `unit` | Minimum value |
| `max` | `number`, `unit` | Maximum value |
| `options` | `select` | Array of `{label, value}` objects |
| `units` | `unit` | Array of `{label, value}` unit options |
| `save` | `unit` | Set to `'seconds'` for automatic time unit conversion |
| `rows` | `code` | Number of textarea rows (default: 6) |
| `language` | `code` | Syntax language hint |

## Field Key Convention

Field keys use dot notation: `module.setting`. The part before the dot is the **module** — it groups settings in the stored option array:

```php
// Field key: 'cache.ttl'
// Stored as: ['cache' => ['ttl' => 3600]]

// Field key: 'storage.enc_password'
// Stored as: ['storage' => ['enc_password' => 'ENC:...']]
```

> [!IMPORTANT]
> Fields with keys starting with `enc_` are automatically encrypted when `encryption` is enabled in the config. The `enc_` prefix triggers sodium encryption on save and decryption on read.

## Conditional Display

Fields can be conditionally shown or hidden based on other settings values using `show` and `hide` conditions.

### 2-Tuple: Equality / Glob Match

```php
// Show when advanced.expert_mode equals true
'show' => ['advanced.expert_mode', true]

// Show when general.mode matches a glob pattern
'show' => ['general.mode', 'prod*']
```

### 3-Tuple: Operator Comparison

```php
// Show when cache.ttl is greater than 3600
'show' => ['cache.ttl', '>', 3600]

// Hide when general.max_retries is less than or equal to 0
'hide' => ['general.max_retries', '<=', 0]
```

**Supported operators:** `=`, `!=`, `>`, `>=`, `<`, `<=`

### Glob Patterns

When the expected value is a string containing `*`, MilliBase uses glob matching:

- `'prod*'` — starts with "prod"
- `'*-cache'` — ends with "-cache"
- `'v*-beta'` — starts with "v" and ends with "-beta"

### Evaluation Rules

- `show` and `hide` are evaluated against the **effective** settings (stored values merged with constant overrides)
- When `show` is defined and evaluates to `false`, the field is hidden
- When `hide` is defined and evaluates to `true`, the field is hidden
- Both can be used on the same field — `hide` takes precedence

## Tab and Section Overrides

Tabs are keyed by `name` and sections by `id`. When multiple tabs or sections share the same identifier, they are merged (last wins). This allows add-on plugins to extend settings pages via the `{slug}_settings_schema` filter:

```php
add_filter('my_plugin_settings_schema', function ($config) {
    // Add a new section to the existing 'general' tab.
    $config['tabs'][] = [
        'name'     => 'general',
        'sections' => [
            [
                'id'     => 'addon-settings',
                'title'  => 'Add-on Settings',
                'fields' => [
                    [
                        'key'     => 'addon.enabled',
                        'type'    => 'toggle',
                        'label'   => 'Enable Add-on',
                        'default' => false,
                    ],
                ],
            ],
        ],
    ];
    return $config;
});
```

Merging behavior:

- **Same tab name, no `replace` flag** — sections are merged by `id`, other tab properties are overwritten
- **Same tab name, `'replace' => true`** — the entire tab is replaced (existing sections are discarded)

## Next Steps

- **[Programmatic Access](./03-programmatic-access.md)** — read and write settings from PHP
- **[Field Types](../04-reference/01-field-types.md)** — detailed reference for each field type
- **[Custom Field Types](../03-customization/01-custom-field-types.md)** — register your own field types
