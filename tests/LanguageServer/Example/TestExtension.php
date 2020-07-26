<?php

namespace Phpactor\Extension\LanguageServer\Tests\Example;

use Amp\Promise;
use Amp\Success;
use Phpactor\LanguageServerProtocol\MessageType;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServer\Core\Service\ServiceProvider;
use Phpactor\LanguageServer\ServiceProvider\PingProvider;
use Phpactor\MapResolver\Resolver;

class TestExtension implements Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register('test.handler', function (Container $container) {
            return new class implements Handler {
                public function methods(): array
                {
                    return ['test' => 'test'];
                }

                public function test()
                {
                    return new Success(new NotificationMessage('window/showMessage', [
                        'type' => MessageType::INFO,
                        'message' => 'Hallo',
                    ]));
                }
            };
        }, [ LanguageServerExtension::TAG_METHOD_HANDLER => []]);

        $container->register('test.service', function (Container $container) {
            return new class($container->get(ClientApi::class)) implements ServiceProvider {
                /** @var ClientApi */
                private $api;
                public function __construct(ClientApi $api) {
                    $this->api = $api;
                }
                public function services(): array
                {
                    return ['test'];
                }

                public function test()
                {
                    $this->api->window()->showmessage()->info('service started');
                    return new Success(new NotificationMessage('window/showMessage', [
                        'type' => MessageType::INFO,
                        'message' => 'Hallo',
                    ]));
                }
            };
        }, [ LanguageServerExtension::TAG_SERVICE_PROVIDER => []]);

        $container->register('test.command', function (Container $container) {
            return new class {
                public function __invoke(string $text): Promise
                {
                    return new Success($text);
                }
            };
        }, [
            LanguageServerExtension::TAG_COMMAND => [
                'name' => 'echo',
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }
}
