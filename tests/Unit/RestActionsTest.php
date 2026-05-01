<?php

use MilliBase\REST\Controller as RestController;
use MilliBase\Settings;

function make_actions_controller(): RestController
{
    return new RestController(
        ['slug' => 'test'],
        new Settings([
            'slug'     => 'test',
            'defaults' => ['cache' => ['enabled' => true]],
        ]),
    );
}

beforeEach(function () {
    $GLOBALS['__milli_test_filters'] = [];
});

it('accepts __reset against the default allow-list', function () {
    $response = make_actions_controller()->perform_settings_action(
        new WP_REST_Request(['action' => '__reset'])
    );

    expect($response)->toBeInstanceOf(WP_REST_Response::class);
    expect($response->get_data()['success'])->toBeTrue();
    expect($response->get_data()['action'])->toBe('__reset');
});

it('accepts __restore against the default allow-list', function () {
    $response = make_actions_controller()->perform_settings_action(
        new WP_REST_Request(['action' => '__restore'])
    );

    // No backup → switch case fires the 400 + success:false envelope.
    expect($response->get_status())->toBe(400);
    expect($response->get_data()['success'])->toBeFalse();
});

it('rejects the bare "reset" name after the rename', function () {
    $response = make_actions_controller()->perform_settings_action(
        new WP_REST_Request(['action' => 'reset'])
    );

    expect($response)->toBeInstanceOf(WP_Error::class);
    expect($response->get_error_code())->toBe('invalid_settings_action');
});

it('rejects the bare "restore" name after the rename', function () {
    $response = make_actions_controller()->perform_settings_action(
        new WP_REST_Request(['action' => 'restore'])
    );

    expect($response)->toBeInstanceOf(WP_Error::class);
    expect($response->get_error_code())->toBe('invalid_settings_action');
});

it('honours the allowed-actions filter for consumer-added names', function () {
    add_filter('test_rest_settings_allowed_actions', function (array $allowed): array {
        $allowed[] = 'my_custom';
        return $allowed;
    });

    // Allowed-but-unhandled names fall through to the success envelope —
    // proving the allow-list gate accepted the filter-added name.
    $response = make_actions_controller()->perform_settings_action(
        new WP_REST_Request(['action' => 'my_custom'])
    );

    expect($response->get_data()['success'])->toBeTrue();
    expect($response->get_data()['action'])->toBe('my_custom');
});
