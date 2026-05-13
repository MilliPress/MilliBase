<?php

uses()->in('Unit');

uses()
    ->beforeEach(function () {
        // Reset the bootstrap stubs' shared call-recorders so cross-test
        // accumulation cannot leak. Tests that read these globals will
        // start from a known-empty state regardless of test order.
        $GLOBALS['__milli_test_filters']           = [];
        $GLOBALS['__milli_test_actions']           = [];
        $GLOBALS['millibase_abilities_calls']      = [];
        $GLOBALS['millibase_abilities_can']        = [];
        $GLOBALS['millibase_abilities_categories'] = [];
        $GLOBALS['millibase_abilities_names']      = [];
    })
    ->in('Unit');
