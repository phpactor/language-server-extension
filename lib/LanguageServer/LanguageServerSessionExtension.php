<?php

namespace Phpactor\Extension\LanguageServer;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServer\Core\Server\ResponseWatcher;
use Phpactor\LanguageServer\Core\Server\RpcClient;
use Phpactor\LanguageServer\Core\Server\RpcClient\JsonRpcClient;
use Phpactor\LanguageServer\Core\Server\SessionServices;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Phpactor\LanguageServer\Core\Service\ServiceManager;
use Phpactor\MapResolver\Resolver;

class LanguageServerSessionExtension implements Extension
{
    /**
     * @var MessageTransmitter
     */
    private $transmitter;

    public function __construct(
        MessageTransmitter $transmitter
    )
    {
        $this->transmitter = $transmitter;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register(MessageTransmitter::class, function (Container $container) {
            return $this->transmitter;
        });

        $container->register(ClientApi::class, function (Container $container) {
            return new ClientApi($container->get(RpcClient::class));
        });

        $container->register(RpcClient::class, function (Container $container) {
            return new JsonRpcClient($this->transmitter, $this->watcher);
        });

        $container->register(ServiceManager::class, function (Container $container) {
            return $this->serviceManager;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }
}
