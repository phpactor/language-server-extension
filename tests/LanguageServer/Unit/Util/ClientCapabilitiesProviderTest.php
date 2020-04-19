<?php

namespace Phpactor\Extension\LanguageServer\Tests\Unit\Util;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phpactor\Extension\LanguageServer\Util\ClientCapabilitiesProvider;

final class ClientCapabilitiesProviderTest extends TestCase
{
    public function testShouldHaveACapability(): void
    {
        $container = $this->container(['textDocument' => ['completion' => ['completionItem' => [
            'supportSnippet' => true,
        ]]]]);

        $this->assertTrue(
            $container->has('textDocument.completion.completionItem.supportSnippet')
        );
    }

    public function testShouldNotHaveACapability(): void
    {
        $container = $this->container([]);

        $this->assertFalse(
            $container->has('textDocument.completion.completionItem.supportSnippet')
        );
    }

    public function testThrowWhenAccessingANonExistingCapability(): string
    {
        $key = 'textDocument.completion.completionItem.supportSnippet';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            "The capability '%s' does not exist.",
            $key
        ));

        $this->container([])->get($key);
    }

    public function testFindExistingCapability(): void
    {
        $container = $this->container(['textDocument' => ['completion' => ['completionItem' => [
            'supportSnippet' => true,
        ]]]]);

        $this->assertTrue(
            $container->get('textDocument.completion.completionItem.supportSnippet')
        );
    }

    private function container($capabilities): ClientCapabilitiesProvider
    {
        return new ClientCapabilitiesProvider($capabilities);
    }
}
