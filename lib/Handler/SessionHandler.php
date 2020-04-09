<?php

namespace Phpactor\Extension\LanguageServer\Handler;

use Amp\Promise;
use Amp\Success;
use LanguageServerProtocol\MessageType;
use Phpactor\Container\Container;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
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

    /**
     * @return Promise<NotificationMessage>
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

        return new Success(new NotificationMessage('window/logMessage', [
            'type' => MessageType::INFO,
            'message' => implode(PHP_EOL, $message),
        ]));
    }
}
