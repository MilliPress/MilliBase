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
| `accordion` | `bool` | No | When `true`, only one section can be open at a time (see [Accordion Mode](#accordion-mode)) |

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
| `group`   | `string`        | No        | Group label — consecutive sections with the same group are visually batched (see [Section Groups](#section-groups)) |
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

// Array form — read-only when a condition matches
'active' => ['key' => 'pro.enabled', 'default' => false, 'lock' => ['status.license.is_licensed', false]],
```

| Property   | Type     | Default  | Description                                                                          |
|------------|----------|----------|--------------------------------------------------------------------------------------|
| `key`      | `string` | —        | Dot-notation setting key (`module.setting`)                                          |
| `default`  | `bool`   | `false`  | Default toggle state                                                                 |
| `lock`     | `array`  | —        | Conditionally render the toggle read-only — same condition syntax as a field `lock`  |

The string shorthand `'cache.enabled'` is equivalent to `['key' => 'cache.enabled', 'default' => false]`.

When `lock` evaluates to `true`, the header toggle is rendered disabled and cannot be switched; its stored value is unchanged. The condition is evaluated against the same **effective** settings as field conditions and may reference `status.*` values. With no `lock`, the toggle is always editable.

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

### Section Groups

The `group` property lets you batch related sections under a shared heading. Consecutive sections with the same `group` value are grouped together visually, with the group label rendered as a heading above them.

```php
'tabs' => [
    [
        'name'      => 'performance',
        'title'     => 'Performance',
        'accordion' => true,
        'sections'  => [
            ['id' => 'editor-assets', 'group' => 'Block Editor', 'title' => 'Assets',  'fields' => [...]],
            ['id' => 'block-styles',  'group' => 'Block Editor', 'title' => 'Styles',  'fields' => [...]],
            ['id' => 'dns-prefetch',  'group' => 'Prefetching',  'title' => 'DNS',     'fields' => [...]],
            ['id' => 'preconnect',    'group' => 'Prefetching',  'title' => 'Origins',  'fields' => [...]],
        ],
    ],
],
```

This renders as:

```
Block Editor                    ← group heading
┌─ Assets ─────────────────┐
│  ...fields...            │   ← 5px gap between sections
├─ Styles ─────────────────┤
│  ...fields...            │
└──────────────────────────┘
                                ← 16px gap between groups
Prefetching                     ← group heading
┌─ DNS ────────────────────┐
│  ...fields...            │
├─ Origins ────────────────┤
│  ...fields...            │
└──────────────────────────┘
```

Sections without a `group` property are collected into a single implicit group (no heading). Groups must be formed by consecutive sections — sections with the same group label that are not adjacent will form separate groups.

### Accordion Mode

When `accordion` is set to `true` on a tab, only one section can be open at a time within each group. Opening a section automatically closes the previously open one.

```php
[
    'name'      => 'modules',
    'title'     => 'Modules',
    'accordion' => true,        // enable accordion behavior
    'sections'  => [ /* ... */ ],
],
```

When sections use `group`, accordion state is scoped per group — opening a section in one group does not affect sections in another group. When no groups are defined, accordion applies across all sections in the tab.

Accordion mode works with all section features including active toggles and status badges. When an active toggle is switched on, the section automatically opens (closing others in the same group).

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
        'lock'        => ['license.is_valid', '=', true], // Read-only condition
    ],
],
```

### Required Properties

| Property   | Type     | Description                                                                                                                                                                             |
|------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `key`      | `string` | Dot-notation key in `module.setting` format. The module (before the dot) groups settings in the stored option. For `button` fields the key is a render-only identifier (not persisted). |
| `type`     | `string` | Field type: `text`, `number`, `password`, `key`, `toggle`, `select`, `unit`, `token-list`, `color`, `code`, `button`                                                                    |

### Common Properties

| Property      | Type     | Default   | Description                                                |
|---------------|----------|-----------|------------------------------------------------------------|
| `label`       | `string` | `''`      | Display label shown above the field                        |
| `default`     | `mixed`  | `null`    | Default value extracted by the Schema                      |
| `tooltip`     | `string` | —         | Help text shown in an info icon tooltip                    |
| `placeholder` | `string` | —         | Placeholder text (text, password, key, token-list)         |
| `disabled`    | `bool`   | `false`   | Render field as read-only (unconditional)                  |
| `lock`        | `[field, op, value]` | — | Conditionally render the field read-only — same condition syntax as `show`/`hide`. See [Conditional Display](#conditional-display). |

### Layout Properties

| Property   | Type     | Default   | Description                                 |
|------------|----------|-----------|---------------------------------------------|
| `inline`   | `bool`   | `false`   | Join this field to the previous field's row |
| `width`    | `string` | —         | CSS width when inline (e.g. `'200px'`)      |

### Type-Specific Properties

| Property        | Types            | Description                                                       |
|-----------------|------------------|-------------------------------------------------------------------|
| `min`           | `number`, `unit` | Minimum value                                                     |
| `max`           | `number`, `unit` | Maximum value                                                     |
| `options`       | `select`         | Array of `{label, value}` objects                                 |
| `units`         | `unit`           | Array of `{label, value}` unit options                            |
| `save`          | `unit`           | Storage base for automatic time-unit conversion: `'seconds'`, `'minutes'`, `'hours'`, `'days'`, `'weeks'`, `'months'`, or `'years'`. The value is stored in that base unit while the user picks any unit from `units`. See [Field Types → unit](../04-reference/01-field-types.md#unit). |
| `rows`          | `code`           | Number of textarea rows (default: 6)                              |
| `language`      | `code`           | Syntax language hint                                              |
| `mask`          | `key`            | Controls how an `enc_`-stored value reads back: `'full'`, `'structured'`, or `array{first, last, structured}`. Default reveals first 4 / last 4 around an all-bullets middle at input length. See [Field Types → key](../04-reference/01-field-types.md#key). |
| `action`        | `button`         | Required. Name (or ordered list of names) of an `actions` entry — or a reserved built-in (`__save`, `__reset`, `__restore`). |
| `variant`       | `button`         | `'primary' \| 'secondary' \| 'tertiary' \| 'link'`.               |
| `size`          | `button`         | `'default' \| 'compact' \| 'small'`.                              |
| `isDestructive` | `button`         | Renders the button in destructive (red) styling.                  |
| `icon`          | `button`         | Icon name from `@wordpress/icons`.                                |
| `confirm`       | `button`         | Optional confirm-modal prompt shown before triggering the action. |

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

Fields can be conditionally shown, hidden, or made read-only based on other settings values using `show`, `hide`, and `lock` conditions. All three use the identical condition syntax described below — they differ only in effect: `show`/`hide` control visibility, `lock` controls editability.

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

- `show`, `hide`, and `lock` are all evaluated against the **effective** settings (stored values merged with constant overrides), and may reference `status.*` values
- When `show` is defined and evaluates to `false`, the field is hidden
- When `hide` is defined and evaluates to `true`, the field is hidden
- `show` and `hide` can be used on the same field — `hide` takes precedence
- When `lock` evaluates to `true`, the field is rendered **read-only** rather than hidden; its stored value is still submitted, it just cannot be edited. `lock` is independent of `show`/`hide` and applies to every field type, including `button`

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
