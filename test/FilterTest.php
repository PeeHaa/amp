<?php

namespace Amp\Test;

use Amp;
use Amp\{ Producer, Stream, Emitter };
use AsyncInterop\Loop;

class FilterTest extends \PHPUnit_Framework_TestCase {
    public function testNoValuesEmitted() {
        $invoked = false;
        Loop::execute(function () use (&$invoked){
            $emitter = new Emitter;

            $stream = Amp\filter($emitter->stream(), function ($value) use (&$invoked) {
                $invoked = true;
            });

            $this->assertInstanceOf(Stream::class, $stream);

            $emitter->resolve();
        });

        $this->assertFalse($invoked);
    }

    public function testValuesEmitted() {
        $count = 0;
        $values = [1, 2, 3];
        $results = [];
        $expected = [1, 3];
        Loop::execute(function () use (&$results, &$result, &$count, $values) {
            $producer = new Producer(function (callable $emit) use ($values) {
                foreach ($values as $value) {
                    yield $emit($value);
                }
            });

            $stream = Amp\filter($producer, function ($value) use (&$count) {
                ++$count;
                return $value & 1;
            });

            $stream->listen(function ($value) use (&$results) {
                $results[] = $value;
            });

            $stream->when(function ($exception, $value) use (&$result) {
                $result = $value;
            });
        });

        $this->assertSame(\count($values), $count);
        $this->assertSame($expected, $results);
    }

    /**
     * @depends testValuesEmitted
     */
    public function testCallbackThrows() {
        $values = [1, 2, 3];
        $exception = new \Exception;
        Loop::execute(function () use (&$reason, $values, $exception) {
            $producer = new Producer(function (callable $emit) use ($values) {
                foreach ($values as $value) {
                    yield $emit($value);
                }
            });

            $stream = Amp\filter($producer, function () use ($exception) {
                throw $exception;
            });

            $stream->listen(function ($value) use (&$results) {
                $results[] = $value;
            });

            $callback = function ($exception, $value) use (&$reason) {
                $reason = $exception;
            };

            $stream->when($callback);
        });

        $this->assertSame($exception, $reason);
    }

    public function testStreamFails() {
        $invoked = false;
        $exception = new \Exception;
        Loop::execute(function () use (&$invoked, &$reason, &$exception){
            $emitter = new Emitter;

            $stream = Amp\filter($emitter->stream(), function ($value) use (&$invoked) {
                $invoked = true;
            });

            $emitter->fail($exception);

            $callback = function ($exception, $value) use (&$reason) {
                $reason = $exception;
            };

            $stream->when($callback);
        });

        $this->assertFalse($invoked);
        $this->assertSame($exception, $reason);
    }
}
