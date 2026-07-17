<?php

use MilliBase\Logger;

/**
 * Capture whatever the given callback writes via error_log().
 *
 * error_log() with no message type writes to the destination configured by
 * the `error_log` ini setting, so we point it at a temp file for the duration
 * of the call and return the file's contents (timestamp prefix included).
 */
function mb_capture_error_log(callable $fn): string
{
    $file = tempnam(sys_get_temp_dir(), 'mb-log-');
    $prev = ini_get('error_log');
    ini_set('error_log', $file);
    try {
        $fn();
    } finally {
        ini_set('error_log', $prev === false ? '' : $prev);
    }
    $contents = (string) file_get_contents($file);
    @unlink($file);

    return $contents;
}

// ─── line format ────────────────────────────────────────────────────

it('writes error entries tagged with the channel and level', function () {
    $output = mb_capture_error_log(function () {
        (new Logger('MilliCache'))->error('Storage connection lost');
    });

    expect($output)->toContain('[MilliCache] [error] Storage connection lost');
});

it('writes warning entries tagged with the channel and level', function () {
    $output = mb_capture_error_log(function () {
        (new Logger('MilliCache'))->warning('Falling back to disk cache');
    });

    expect($output)->toContain('[MilliCache] [warning] Falling back to disk cache');
});

// ─── structured context ─────────────────────────────────────────────

it('appends structured context as JSON', function () {
    $output = mb_capture_error_log(function () {
        (new Logger('MilliCache'))->error('Purge failed', ['key' => 'home', 'code' => 500]);
    });

    expect($output)
        ->toContain('[MilliCache] [error] Purge failed')
        ->toContain('{"key":"home","code":500}');
});

it('omits the JSON suffix when context is empty', function () {
    $output = mb_capture_error_log(function () {
        (new Logger('MilliCache'))->error('No context here');
    });

    expect(trim($output))->toEndWith('No context here');
});

// ─── millibase_log action ───────────────────────────────────────────

it('fires the millibase_log action with channel, level, message and context', function () {
    $captured = [];
    add_action('millibase_log', function ($channel, $level, $message, $context) use (&$captured): void {
        $captured = compact('channel', 'level', 'message', 'context');
    }, 10, 4);

    mb_capture_error_log(function () {
        (new Logger('MilliCache'))->warning('Degraded mode', ['reason' => 'timeout']);
    });

    expect($captured)->toBe([
        'channel' => 'MilliCache',
        'level'   => Logger::WARNING,
        'message' => 'Degraded mode',
        'context' => ['reason' => 'timeout'],
    ]);
});

// ─── WP_DEBUG gating ────────────────────────────────────────────────

// WP_DEBUG is a compile-time constant and cannot be toggled per test, so we
// assert the production-safety direction: with WP_DEBUG undefined (the test
// bootstrap never defines it), debug entries are suppressed entirely — no
// error_log write and no millibase_log dispatch.
it('suppresses debug entries when WP_DEBUG is off', function () {
    expect(defined('WP_DEBUG') && WP_DEBUG)->toBeFalse();

    $output = mb_capture_error_log(function () {
        (new Logger('MilliCache'))->debug('verbose diagnostic');
    });

    expect($output)->toBe('');
    expect($GLOBALS['__milli_test_actions_fired'])->not->toContain('millibase_log');
});
