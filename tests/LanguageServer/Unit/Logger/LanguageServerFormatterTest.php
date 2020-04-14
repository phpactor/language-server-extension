<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit\Logger;

use DateTime;
use PHPUnit\Framework\TestCase;
use Phpactor\Extension\LanguageServer\Logger\LanguageServerFormatter;

class LanguageServerFormatterTest extends TestCase
{
    /**
     * @dataProvider provideFormat
     */
    public function testFormat(array $record)
    {
        $record = array_merge([
            'level_name' => 'info',
            'context' => [],
            'message' => 'hello',
            'datetime' => new DateTime(),
        ]);
        $formatter = new LanguageServerFormatter();
        $string = $formatter->format($record);
        self::assertIsString($string);
    }

    public function provideFormat()
    {
        yield [
            ['level_name' => 'critical'],
        ];
        yield [
            ['level_name' => 'unknown'],
        ];
    }
}
