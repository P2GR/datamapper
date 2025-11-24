<?php

namespace Tests\Support;

use RuntimeException;

abstract class TestCaseShim
{
    protected function assertInstanceOf($expected, $actual, string $message = ''): void
    {
        if (!($actual instanceof $expected)) {
            $this->fail($message ?: sprintf('Failed asserting that %s is instance of %s.', $this->describe($actual), $expected));
        }
    }

    protected function assertCount($expectedCount, $haystack, string $message = ''): void
    {
        $actual = is_countable($haystack) ? count($haystack) : 0;
        if ($expectedCount !== $actual) {
            $this->fail($message ?: sprintf('Failed asserting count %d matches actual count %d.', $expectedCount, $actual));
        }
    }

    protected function assertSame($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $this->fail($message ?: sprintf('Failed asserting that %s is identical to %s.', var_export($actual, TRUE), var_export($expected, TRUE)));
        }
    }

    protected function assertArrayHasKey($key, $array, string $message = ''): void
    {
        if (!is_array($array) || !array_key_exists($key, $array)) {
            $this->fail($message ?: sprintf('Failed asserting that array has key %s.', var_export($key, TRUE)));
        }
    }

    protected function assertGreaterThanOrEqual($expected, $actual, string $message = ''): void
    {
        if ($actual < $expected) {
            $this->fail($message ?: sprintf('Failed asserting that %s is greater than or equal to %s.', var_export($actual, TRUE), var_export($expected, TRUE)));
        }
    }

    protected function fail(string $message): void
    {
        throw new RuntimeException($message);
    }

    private function describe($value): string
    {
        if (is_object($value)) {
            return get_class($value);
        }
        return gettype($value);
    }
}
