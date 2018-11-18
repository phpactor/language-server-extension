<?php

namespace Phpactor\Extension\LanguageServer;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\Command\StartCommand;
use Phpactor\Extension\LanguageServer\Extension\LanguageExtension;
use Phpactor\LanguageServer\Core\Session\Manager;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\MapResolver\Resolver;

class LanguageServerExtension implements Extension
{
    const SERVICE_LANGUAGE_SERVER_BUILDER = 'language_server.builder';

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
                $container->get('language_server.session_manager')
            );
            $builder->withCoreExtension();

            foreach (array_keys($container->getServiceIdsForTag('language_server.extension')) as $extensionId) {
                $extension = $container->get($extensionId);
                $builder->addExtension($extension);
            }

            return $builder;
        });

        $container->register('language_server.command.lsp_start', function (Container $container) {
            return new StartCommand($container->get(self::SERVICE_LANGUAGE_SERVER_BUILDER));
        }, [ ConsoleExtension::TAG_COMMAND => [ 'name' => StartCommand::NAME ]]);

        $container->register('language_server.session_manager', function (Container $container) {
            return new Manager();
        });

        $container->register('language_server.extension.core', function (Container $container) {
            return new LanguageExtension($container->get('language_server.session_manager'));
        }, [ 'language_server.extension' => [] ]);
    }
}
