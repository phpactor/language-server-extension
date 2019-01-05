<?php

namespace Phpactor\Extension\LanguageServer\Handler;

use Generator;
use LanguageServerProtocol\MessageType;
use Phpactor\Container\Container;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;

class SessionHandler implements Handler
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function methods(): array
    {
        return [
            'session/dumpConfig' => 'dumpConfig'
        ];
    }

    public function dumpConfig(): Generator
    {
        yield null;
        yield new NotificationMessage('window/logMessage', [
            'type' => MessageType::INFO,
            'message' => json_encode($this->container->getParameters(), JSON_PRETTY_PRINT),
        ]);
    }
}
