---
title: 'Namespace Prefixing'
post_excerpt: 'Why every plugin shipping MilliBase must Strauss-prefix it, and how MilliBase handles cross-prefix instances internally.'
menu_order: 40
---

# Namespace Prefixing

> **⚠ Required reading for plugin authors.**
> If your plugin bundles MilliBase via Composer, you **must** prefix it
> (Strauss, php-scoper, or equivalent) before shipping. Plugins that publish
> the unprefixed `MilliBase\` namespace will conflict with every other plugin
> doing the same — and with each other's Settings instances at runtime.

## Why Prefixing Is Required

WordPress runs every plugin in the same PHP process and the same global
namespace. If two plugins each `require` MilliBase v1.2 and v1.4 unprefixed,
exactly one copy wins (whichever loads first), and the other plugin silently
runs against a version it wasn't tested with. Class-not-found and
"unexpected method signature" errors follow.

The standard fix in the WordPress ecosystem is to **vendor-prefix**
your dependencies into a plugin-private namespace, e.g.:

| Plugin       | Prefixed FQCN                            |
|--------------|------------------------------------------|
| MilliCache   | `MilliCache\Deps\MilliBase\Settings`     |
| MilliFoo     | `MilliFoo\Vendor\MilliBase\Settings`     |
| Stand-alone  | `MilliBase\Settings` (unprefixed)        |

Each plugin then loads its own copy, isolated from every other plugin.

## Recommended Tooling

[**Strauss**](https://github.com/BrianHenryIE/strauss) is the simplest option
for most plugins. Add it to `composer.json`:

```json
{
    "require": {
        "millipress/millibase": "^2.0"
    },
    "require-dev": {
        "brianhenryie/strauss": "^0.19"
    },
    "extra": {
        "strauss": {
            "namespace_prefix": "MyPlugin\\Deps\\",
            "classmap_prefix": "MyPlugin_Deps_",
            "target_directory": "vendor-prefixed",
            "packages": [
                "millipress/millibase"
            ]
        }
    },
    "scripts": {
        "post-install-cmd": ["@composer-strauss"],
        "post-update-cmd":  ["@composer-strauss"],
        "composer-strauss": "strauss"
    }
}
```

After `composer install`, your plugin loads `MyPlugin\Deps\MilliBase\Manager`
instead of the unprefixed class. Update your `use` statements accordingly.

[**php-scoper**](https://github.com/humbug/php-scoper) is an alternative with
more configuration knobs; either tool works.

## How MilliBase Handles Cross-Prefix Instances

PHP enforces type hints by **fully-qualified class name**. So
`MilliBase\Settings` and `MilliCache\Deps\MilliBase\Settings` are completely
different types to the runtime — even when their source is byte-identical.
A strict `Settings $settings` parameter would throw a `TypeError` if a
caller passed in the prefixed copy.

To stay tolerant during the **dual-active window** (the brief moment during
plugin activation when both prefixed and unprefixed autoloaders are
registered), MilliBase intentionally drops the native type hint at every
public boundary that receives a `Settings` instance. The type is preserved
via `@param`/`@var` so PHPStan still enforces it within a single prefix
scope.

**Contract for contributors:** any new public method or constructor in
`Manager`, `REST\Controller`, `CLI\Controller`, or any other class that
receives a `Settings` from `Manager` MUST follow this pattern:

```php
/**
 * @var Settings
 */
private $settings;          // not: private Settings $settings

/**
 * @param Settings $settings
 */
public function __construct( $settings ) {  // not: ( Settings $settings )
    $this->settings = $settings;
}
```

If you add a strict native type back, the next cross-prefix consumer's
plugin will TypeError on activation. PHPStan will not catch this — it sees
your tightening as a strictly-better annotation.

## Why Not `class_alias`?

A common first instinct is to `class_alias( \MilliBase\Settings::class,
\Foo\Deps\MilliBase\Settings::class )` from the consumer plugin. This is a
trap:

- **Direction-dependent.** The alias only takes effect if the target name
  isn't yet a real autoloadable class. As soon as Strauss's autoloader
  resolves the prefixed file, the alias is bypassed.
- **Defeats the point of prefixing.** Aliasing the prefixed name back to the
  unprefixed one re-couples every plugin to whichever MilliBase version
  loaded first.
- **N×N problem.** Two consumer plugins need aliases in both directions;
  three plugins need six. There is no canonical name to alias *to*.

The relaxed-type pattern is one-sided, version-stable, and requires zero
cooperation from consumers — which is why MilliBase uses it.
