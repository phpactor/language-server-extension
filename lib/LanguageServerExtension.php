<?php

namespace Phpactor\Extension\LanguageServer;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\Command\StartCommand;
use Phpactor\LanguageServer\Adapter\Evenement\EvenementEmitter;
use Phpactor\LanguageServer\Core\Session\SessionManager;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\MapResolver\Resolver;

class LanguageServerExtension implements Extension
{
    const SERVICE_LANGUAGE_SERVER_BUILDER = 'language_server.builder';
    const SERVICE_SESSION_MANAGER = 'language_server.session_manager';
    const SERVICE_EVENT_EMITTER = 'language_server.event_emitter';
    const TAG_HANDLER = 'language_server.handler';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register(self::SERVICE_LANGUAGE_SERVER_BUILDER, function (Container $container) {
            $builder = LanguageServerBuilder::create(
                $container->get(LoggingExtension::SERVICE_LOGGER),
                $container->get(self::SERVICE_SESSION_MANAGER),
                $container->get(self::SERVICE_EVENT_EMITTER)
            );

            foreach (array_keys($container->getServiceIdsForTag(self::TAG_HANDLER)) as $handlerId) {
                $handler = $container->get($handlerId);
                $builder->addHandler($handler);
            }

            return $builder;
        });

        $container->register('language_server.command.lsp_start', function (Container $container) {
            return new StartCommand($container->get(self::SERVICE_LANGUAGE_SERVER_BUILDER));
        }, [ ConsoleExtension::TAG_COMMAND => [ 'name' => StartCommand::NAME ]]);

        $container->register(self::SERVICE_SESSION_MANAGER, function (Container $container) {
            return new SessionManager();
        });

        $container->register(self::SERVICE_EVENT_EMITTER, function (Container $container) {
            return new EvenementEmitter();
        });
    }
}
