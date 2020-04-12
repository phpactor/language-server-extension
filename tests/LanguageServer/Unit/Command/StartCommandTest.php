<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit\Command;

use Phpactor\Extension\LanguageServer\Tests\Unit\LanguageServerTestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class StartCommandTest extends LanguageServerTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    protected function setUp(): void
    {
        $container = $this->createContainer([]);
        $this->tester = new CommandTester($container->get('language_server.command.lsp_start'));
    }

    public function testRecordToNonExistingFile()
    {
        $this->expectException(RuntimeException::class);
        $this->tester->execute([
            '--record' => 'foobar/ads',
        ]);
    }

    public function testDebugOptions()
    {
        $this->tester->execute([
            '--throw' => true,
            '--no-loop' => true,
        ]);
    }
}
