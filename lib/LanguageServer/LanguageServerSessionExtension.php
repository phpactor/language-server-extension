<?php

namespace Phpactor\Extension\LanguageServer;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServer\Core\Server\RpcClient;
use Phpactor\LanguageServer\Core\Server\SessionServices;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Phpactor\LanguageServer\Core\Service\ServiceManager;
use Phpactor\MapResolver\Resolver;

class LanguageServerSessionExtension implements Extension
{
    /**
     * @var SessionServices
     */
    private $services;

    public function __construct(SessionServices $services = null)
    {
        $this->services = $services;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register(MessageTransmitter::class, function (Container $container) {
            return $this->services->messageTransmitter();
        });

        $container->register(ClientApi::class, function (Container $container) {
            return new ClientApi($container->get(RpcClient::class));
        });

        $container->register(RpcClient::class, function (Container $container) {
            return $this->services->client();
        });

        $container->register(ServiceManager::class, function (Container $container) {
            return $this->services->serviceManager();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }
}
