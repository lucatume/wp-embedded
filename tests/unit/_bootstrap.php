<?php
// Here you can initialize variables that will be available to your tests
use tad\FunctionMocker\FunctionMocker;

FunctionMocker::init();
/**
 * @return \Codeception\Lib\ModuleContainer
 */
function make_container()
{
    return \Codeception\Util\Stub::make('Codeception\Lib\ModuleContainer');
}

