---
title: 'Installation'
post_excerpt: 'Install MilliBase via Composer and set up your first settings page.'
menu_order: 20
---

# Installation

> **⚠ Shipping a plugin? Prefix MilliBase first.**
> If MilliBase is going to live inside a distributed WordPress plugin, you
> must vendor-prefix it (Strauss, php-scoper, or equivalent) so two plugins
> bundling MilliBase don't collide on the same `MilliBase\` namespace at
> runtime. See [Namespace Prefixing](../04-reference/04-namespace-prefixing.md)
> for a Strauss-based setup and the contract MilliBase follows internally to
> tolerate cross-prefix Settings instances.

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

Create a settings page by passing a `slug` and a config `Closure` to `\MilliBase\Manager`. The closure is called on `init`, so translation calls like `__()` can run after textdomains are loaded:

```php
<?php

use MilliBase\Manager;

$manager = new Manager(
    slug: 'my-plugin',
    config: fn() => [
        'page_title' => __( 'My Plugin', 'my-plugin' ),
        'menu_title' => __( 'My Plugin', 'my-plugin' ),

        'header' => [
            'title' => __( 'My Plugin Settings', 'my-plugin' ),
        ],

        'tabs' => [
            [
                'name'     => 'general',
                'title'    => __( 'General', 'my-plugin' ),
                'sections' => [
                    [
                        'id'     => 'main',
                        'title'  => __( 'Main Settings', 'my-plugin' ),
                        'fields' => [
                            [
                                'key'     => 'general.enabled',
                                'type'    => 'toggle',
                                'label'   => __( 'Enable Feature', 'my-plugin' ),
                                'default' => true,
                            ],
                            [
                                'key'         => 'general.api_key',
                                'type'        => 'text',
                                'label'       => __( 'API Key', 'my-plugin' ),
                                'default'     => '',
                                'placeholder' => __( 'Enter your API key', 'my-plugin' ),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
);
```

This registers:
- An admin submenu page under **Settings > My Plugin**
- REST endpoints at `GET/POST /my-plugin/v1/settings` for reading and saving
- An action endpoint at `POST /my-plugin/v1/settings/actions` for `__reset` / `__restore`
- WP-CLI commands at `wp my-plugin config <subcommand>`

## Verify

1. Navigate to **Settings > My Plugin** in your WordPress admin
2. You should see the React-powered settings UI with your defined tabs and fields
3. Change a value and click **Save Settings**

## Programmatic Access

Access stored settings anywhere in your plugin:

```php
// Get the Settings instance.
$settings = $manager->settings();

// Read a value using dot notation.
$enabled = $settings->get('general.enabled');
$api_key = $settings->get('general.api_key', 'fallback');

// Set a value.
$settings->set('general.api_key', 'sk-abc123');

// Get all settings.
$all = $settings->get();
```

## Next Steps

- **[Configuration](../02-usage/01-configuration.md)** — all configuration options explained
- **[Schema Definition](../02-usage/02-schema-definition.md)** — define tabs, sections, fields, and conditions
- **[Field Types](../04-reference/01-field-types.md)** — reference for all 9 built-in field types
- **[Namespace Prefixing](../04-reference/04-namespace-prefixing.md)** — required reading before shipping a plugin that bundles MilliBase
