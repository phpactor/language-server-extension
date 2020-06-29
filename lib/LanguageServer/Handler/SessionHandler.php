<?php

namespace Phpactor\Extension\LanguageServer\Handler;

use Amp\Promise;
use Amp\Success;
use Phpactor\LanguageServerProtocol\TextDocumentItem;
use Phpactor\Container\Container;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServer\Core\Session\Workspace;

class SessionHandler implements Handler
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var ClientApi
     */
    private $client;

    /**
     * @var Workspace
     */
    private $workspace;

    public function __construct(Container $container, ClientApi $client, Workspace $workspace)
    {
        $this->container = $container;
        $this->client = $client;
        $this->workspace = $workspace;
    }

    /**
     * {@inheritDoc}
     */
    public function methods(): array
    {
        return [
            'session/dumpConfig' => 'dumpConfig',
            'session/dumpWorkspace' => 'dumpWorkspace'
        ];
    }

    /**
     * @return Promise<null>
     */
    public function dumpConfig(): Promise
    {
        $message = [
            'Config Dump',
            '===========',
            '',
            'File Paths',
            '----------',
            '',
        ];
        $paths = [];

        foreach (
            $this->container->get(
                FilePathResolverExtension::SERVICE_EXPANDERS
            )->toArray() as $tokenName => $value
        ) {
            $message[] = sprintf('%s: %s', $tokenName, $value);
        }

        $message[] = '';
        $message[] = 'Config';
        $message[] = '------';


        $message[] = json_encode($this->container->getParameters(), JSON_PRETTY_PRINT);

        $this->client->window()->logMessage()->info(implode(PHP_EOL, $message));

        return new Success(null);
    }

    /**
     * @return Promise<null>
     */
    public function dumpWorkspace(): Promise
    {
        $info = [];
        foreach ($this->workspace as $document) {
            assert($document instanceof TextDocumentItem);
            $info[] = sprintf('// %s', $document->uri);
            $info[] = '-----------------';
            $info[] = $document->text;
        }

        $this->client->window()->logMessage()->info(implode("\n", $info));

        return new Success(null);
    }
}
