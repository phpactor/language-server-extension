<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\Container\Container;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\Extension\LanguageServer\Tests\Example\TestExtension;
use Phpactor\LanguageServer\Test\ServerTester;

class LanguageServerExtensionTest extends LanguageServerTestCase
{
    public function testInitializesLanguageServer()
    {
        $serverTester = $this->createTester();
        $serverTester->initialize();
    }

    public function testLoadsTextDocuments()
    {
        $serverTester = $this->createTester();
        $responses = $serverTester->initialize();
        $serverTester->assertSuccess($responses);
    }

    public function testLoadsHandlers()
    {
        $serverTester = $this->createTester();
        $serverTester->initialize();
        $responses = $serverTester->dispatch('test', []);
        $this->assertCount(2, $responses);

        $this->assertTrue($serverTester->assertSuccess($responses));
    }
}
