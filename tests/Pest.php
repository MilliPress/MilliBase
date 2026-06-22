<?php

uses()->in('Unit');

uses()
    ->beforeEach(function () {
        // Reset recorder globals so test order can't leak state.
        $GLOBALS['__milli_test_filters']           = [];
        $GLOBALS['__milli_test_actions']           = [];
        $GLOBALS['__milli_test_actions_fired']     = [];
        $GLOBALS['__milli_test_doing_it_wrong']    = [];
        $GLOBALS['__milli_test_options']           = [];
        $GLOBALS['__milli_test_site_options']      = [];
        $GLOBALS['__milli_test_transients']        = [];
        $GLOBALS['__milli_test_site_transients']   = [];
        $GLOBALS['millibase_abilities_calls']      = [];
        $GLOBALS['millibase_abilities_can']        = [];
        $GLOBALS['millibase_abilities_categories'] = [];
        $GLOBALS['millibase_abilities_names']      = [];
    })
    ->in('Unit');
