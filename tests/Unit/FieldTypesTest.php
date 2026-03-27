<?php

use MilliBase\Fields\Text;
use MilliBase\Fields\Toggle;
use MilliBase\Fields\Number;
use MilliBase\Fields\Select;
use MilliBase\Fields\Color;
use MilliBase\Fields\Code;
use MilliBase\Fields\Password;
use MilliBase\Fields\Unit;
use MilliBase\Fields\TokenList;

// ─── get_type() ─────────────────────────────────────────────────────

it('returns correct type identifiers', function () {
    expect((new Text())->get_type())->toBe('text');
    expect((new Toggle())->get_type())->toBe('toggle');
    expect((new Number())->get_type())->toBe('number');
    expect((new Select())->get_type())->toBe('select');
    expect((new Color())->get_type())->toBe('color');
    expect((new Code())->get_type())->toBe('code');
    expect((new Password())->get_type())->toBe('password');
    expect((new Unit())->get_type())->toBe('unit');
    expect((new TokenList())->get_type())->toBe('token-list');
});

// ─── get_schema() ───────────────────────────────────────────────────

it('returns string schema for text, code, password, color, select', function () {
    $field = [];
    expect((new Text())->get_schema($field)['type'])->toBe('string');
    expect((new Code())->get_schema($field)['type'])->toBe('string');
    expect((new Password())->get_schema($field)['type'])->toBe('string');
    expect((new Color())->get_schema($field)['type'])->toBe('string');
});

it('returns boolean schema for toggle', function () {
    expect((new Toggle())->get_schema([])['type'])->toBe('boolean');
});

it('returns number schema for number and unit', function () {
    expect((new Number())->get_schema([])['type'])->toBe('number');
    expect((new Unit())->get_schema([])['type'])->toBe('number');
});

it('returns array schema for token-list', function () {
    $schema = (new TokenList())->get_schema([]);
    expect($schema['type'])->toBe('array');
    expect($schema['items']['type'])->toBe('string');
});

it('includes min/max in number schema when defined', function () {
    $field = ['min' => 0, 'max' => 100];
    $schema = (new Number())->get_schema($field);

    expect($schema['minimum'])->toBe(0);
    expect($schema['maximum'])->toBe(100);
});

it('includes enum in select schema', function () {
    $field = [
        'options' => [
            ['label' => 'Redis', 'value' => 'redis'],
            ['label' => 'File', 'value' => 'file'],
        ],
    ];

    $schema = (new Select())->get_schema($field);

    expect($schema['enum'])->toBe(['redis', 'file']);
});

// ─── sanitize() ─────────────────────────────────────────────────────

it('sanitizes text fields by stripping tags', function () {
    $field = new Text();

    expect($field->sanitize('<b>bold</b>', []))->toBe('bold');
    expect($field->sanitize('plain text', []))->toBe('plain text');
});

it('sanitizes toggle to boolean', function () {
    $field = new Toggle();

    expect($field->sanitize(1, []))->toBeTrue();
    expect($field->sanitize(0, []))->toBeFalse();
    expect($field->sanitize('yes', []))->toBeTrue();
});

it('clamps number values to min/max', function () {
    $field = new Number();
    $def = ['min' => 0, 'max' => 100];

    expect($field->sanitize(50, $def))->toBe(50);
    expect($field->sanitize(-10, $def))->toBe(0);
    expect($field->sanitize(200, $def))->toBe(100);
    expect($field->sanitize('not-a-number', $def))->toBe(0);
});

it('validates select against allowed options', function () {
    $field = new Select();
    $def = [
        'default' => 'redis',
        'options' => [
            ['label' => 'Redis', 'value' => 'redis'],
            ['label' => 'File', 'value' => 'file'],
        ],
    ];

    expect($field->sanitize('redis', $def))->toBe('redis');
    expect($field->sanitize('file', $def))->toBe('file');
    expect($field->sanitize('invalid', $def))->toBe('redis'); // falls back to default
});

it('validates hex colors', function () {
    $field = new Color();

    expect($field->sanitize('#fff', []))->toBe('#fff');
    expect($field->sanitize('#aabbcc', []))->toBe('#aabbcc');
    expect($field->sanitize('not-a-color', []))->toBe('');
    expect($field->sanitize(42, []))->toBe('');
});

it('passes through code and password values', function () {
    expect((new Code())->sanitize('<script>alert(1)</script>', []))->toBe('<script>alert(1)</script>');
    expect((new Password())->sanitize('s3cret!', []))->toBe('s3cret!');
});

it('sanitizes unit fields as numeric', function () {
    $field = new Unit();

    expect($field->sanitize(42, []))->toBe(42);
    expect($field->sanitize('100', []))->toBe(100);
    expect($field->sanitize('abc', []))->toBe(0);
});

it('sanitizes token-list as array of strings', function () {
    $field = new TokenList();

    expect($field->sanitize(['hello', '<b>world</b>', ''], []))->toBe(['hello', 'world']);
    expect($field->sanitize('not-an-array', []))->toBe([]);
});
