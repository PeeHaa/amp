<?php

namespace Amp\Test\Loop;

use Amp\Loop\EvLoop;
use Amp\Test\LoopTest;

/**
 * @requires extension ev
 */
class EvLoopLoopTest extends LoopTest {
    public function getFactory(): callable {
        return function () {
            return new EvLoop;
        };
    }
}