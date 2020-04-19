<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Phpactor\Extension\LanguageServer\Helper\ArrayHelper;

final class ArrayHelperTest extends TestCase
{
    /**
     * @dataProvider provideArrayToFlatten
     */
    public function testFlattenAnArray(array $expected, array $actual): void
    {
        $this->assertEqualsCanonicalizing(
            $expected,
            ArrayHelper::flatten($actual)
        );
    }

    public function provideArrayToFlatten(): iterable
    {
        yield 'empty array' => [[], []];

        yield 'single dimension array' => [
            ['0' => 'a', 'b' => 'b1'],
            ['a', 'b' => 'b1'],
        ];

        yield 'multiple dimention array' => [
            ['0' => 'a', 'b.0' => 'b1', 'c.c1' => 'c11', 'c.0' => 'c12'],
            ['a', 'b' => ['b1'], 'c' => ['c1' => 'c11', 'c12']],
        ];
    }
}
