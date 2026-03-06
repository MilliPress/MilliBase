---
title: 'Installation'
post_excerpt: 'Install MilliBase via Composer and set up your first settings page.'
menu_order: 20
---

# Installation

## Install the Package

```bash
composer require millipress/millibase
```

## Build Assets

MilliBase ships pre-built JS and CSS in the `build/` directory. If you need to rebuild (e.g. after modifying the source):

```bash
cd vendor/millipress/millibase
npm install
npm run build
```

## Your First Settings Page

Create a settings page by passing a configuration array to `\MilliBase\Settings`:

```php
<?php

use MilliBase\Settings;

$settings = new Settings([
    'slug'           => 'my-plugin',
    'page_title'     => 'My Plugin',
    'menu_title'     => 'My Plugin',
    'capability'     => 'manage_options',
    'menu_parent'    => 'options-general.php',
    'rest_namespace' => 'my-plugin/v1',
    'basename'       => plugin_basename(__FILE__),

    'header' => [
        'title' => 'My Plugin Settings',
    ],

    'tabs' => [
        [
            'name'     => 'general',
            'title'    => 'General',
            'sections' => [
                [
                    'id'     => 'main',
                    'title'  => 'Main Settings',
                    'fields' => [
                        [
                            'key'     => 'general.enabled',
                            'type'    => 'toggle',
                            'label'   => 'Enable Feature',
                            'default' => true,
                        ],
                        [
                            'key'         => 'general.api_key',
                            'type'        => 'text',
                            'label'       => 'API Key',
                            'default'     => '',
                            'placeholder' => 'Enter your API key',
                        ],
                    ],
                ],
            ],
        ],
    ],
]);
```

This registers:
- An admin submenu page under **Settings > My Plugin**
- A REST endpoint at `POST /wp/v2/settings` for saving
- Action endpoints at `POST /my-plugin/v1/settings` for reset/restore

## Verify

1. Navigate to **Settings > My Plugin** in your WordPress admin
2. You should see the React-powered settings UI with your defined tabs and fields
3. Change a value and click **Save Settings**

## Programmatic Access

Access stored settings anywhere in your plugin:

```php
// Get the Store instance.
$store = $settings->store();

// Read a value using dot notation.
$enabled = $store->get('general.enabled');
$api_key = $store->get('general.api_key', 'fallback');

// Set a value.
$store->set('general.api_key', 'sk-abc123');

// Get all settings.
$all = $store->get_all();
```

## Next Steps

- **[Configuration](../02-usage/01-configuration.md)** — all configuration options explained
- **[Schema Definition](../02-usage/02-schema-definition.md)** — define tabs, sections, fields, and conditions
- **[Field Types](../04-reference/01-field-types.md)** — reference for all 9 built-in field types
