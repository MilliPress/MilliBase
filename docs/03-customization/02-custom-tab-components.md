---
title: 'Custom Tab Components'
post_excerpt: 'Render fully custom React content inside a settings tab.'
menu_order: 20
---

# Custom Tab Components

Instead of the standard sections-and-fields layout, a tab can render a fully custom React component. This is useful for status dashboards, complex multi-step wizards, or any UI that doesn't fit the declarative field model.

## Registering a Custom Component

Use `window.MilliBase.registerComponent()`:

```javascript
window.MilliBase.registerComponent('StatusDashboard', function (props) {
    const { status, settings, updateSetting, triggerAction, isSaving } = props;

    return React.createElement('div', null,
        React.createElement('h2', null, 'Status Dashboard'),
        React.createElement('p', null, status?.healthy ? 'All systems operational' : 'Issues detected')
    );
});
```

## Component Props

Custom tab components receive the full settings context:

| Prop | Type | Description |
|------|------|-------------|
| `settings` | `object` | Current settings values |
| `status` | `object` | Response from the status endpoint |
| `updateSetting` | `function` | `(module, key, value)` — update a setting |
| `triggerAction` | `function` | `(actionName, data)` — trigger a REST action |
| `saveSettings` | `function` | Save current settings to the server |
| `isSaving` | `bool` | Whether a save is in progress |
| `isLoading` | `bool` | Whether settings are loading |
| `hasChanges` | `bool` | Whether there are unsaved changes |
| `config` | `object` | The full client config object |

## Using in the Schema

Reference your component in a tab definition:

```php
'tabs' => [
    [
        'name'      => 'status',
        'title'     => 'Status',
        'type'      => 'custom',
        'component' => 'StatusDashboard',
    ],
],
```

The `type` must be `'custom'` and `component` must match the name passed to `registerComponent()`.

## Section Intro Components

You can also use registered components as section introductions. When the `intro` property of a section matches a registered component name, it renders the component instead of plain text:

```php
'sections' => [
    [
        'id'    => 'storage',
        'title' => 'Storage',
        'intro' => 'StorageStatus',  // Renders the StorageStatus component
        'fields' => [ /* ... */ ],
    ],
],
```

```javascript
window.MilliBase.registerComponent('StorageStatus', function (props) {
    const { status } = props;
    return React.createElement('div', { className: 'storage-status' },
        React.createElement('p', null, 'Connected to: ' + (status?.storage?.host || 'N/A'))
    );
});
```

## Header Button Components

Custom components can also be used for header buttons:

```php
'header' => [
    'buttons' => [
        [
            'component' => 'PurgeCacheButton',
        ],
    ],
],
```

```javascript
window.MilliBase.registerComponent('PurgeCacheButton', function ({ status, triggerAction, isSaving, isLoading }) {
    return React.createElement('button', {
        onClick: () => triggerAction('purge-cache'),
        disabled: isSaving || isLoading,
    }, 'Purge Cache');
});
```

## Enqueue Order

Same as custom field types — enqueue your script with `millibase` as a dependency:

```php
wp_enqueue_script(
    'my-custom-components',
    plugins_url('assets/js/components.js', __FILE__),
    ['millibase'],
    '1.0.0',
    ['in_footer' => true]
);
```

## Next Steps

- **[Extending with Filters](./03-extending-with-filters.md)** — extend settings via PHP filters
- **[Custom Field Types](./01-custom-field-types.md)** — register custom field types
