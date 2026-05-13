<?php

use MilliBase\Settings;
use MilliBase\Settings\Group;

function group_settings(string $slug, bool $network = false): Settings
{
    return new Settings([
        'slug'     => $slug,
        'network'  => $network,
        'defaults' => [],
    ]);
}

it('resolve(false) returns the first per-site member', function () {
    $site    = group_settings('a', false);
    $network = group_settings('b', true);

    $group = new Group($site);
    $group->add($network);

    expect($group->resolve(false))->toBe($site);
});

it('resolve(true) returns the first network-scoped member', function () {
    $site    = group_settings('a', false);
    $network = group_settings('b', true);

    $group = new Group($site);
    $group->add($network);

    expect($group->resolve(true))->toBe($network);
});

it('resolve(false) falls through to a network member when no per-site exists', function () {
    // Single network-only Manager — operators omitting --network still get
    // the only available Settings instead of an error.
    $network = group_settings('only', true);
    $group   = new Group($network);

    expect($group->resolve(false))->toBe($network);
});

it('resolve(true) returns null when no network member exists', function () {
    // Site-only plugin + operator passed --network — caller surfaces the
    // null as a wp-cli error.
    $site  = group_settings('a', false);
    $group = new Group($site);

    expect($group->resolve(true))->toBeNull();
});

it('resolve(true) on a network-only registry returns the network member', function () {
    $network = group_settings('only', true);
    $group   = new Group($network);

    expect($group->resolve(true))->toBe($network);
});

it('add() appends additional members so resolve() can find them', function () {
    $primary = group_settings('a', false);
    $extra   = group_settings('b', true);

    $group = new Group($primary);
    $group->add($extra);

    expect($group->resolve(false))->toBe($primary);
    expect($group->resolve(true))->toBe($extra);
});
