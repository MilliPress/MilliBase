<?php

uses()->in('Unit');

uses()
    ->beforeEach(function () {
        // Reset recorder globals so test order can't leak state.
        $GLOBALS['__milli_test_filters']           = [];
        $GLOBALS['__milli_test_actions']           = [];
        $GLOBALS['millibase_abilities_calls']      = [];
        $GLOBALS['millibase_abilities_can']        = [];
        $GLOBALS['millibase_abilities_categories'] = [];
        $GLOBALS['millibase_abilities_names']      = [];
    })
    ->in('Unit');
