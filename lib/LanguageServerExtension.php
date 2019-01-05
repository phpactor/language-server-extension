<?php

namespace Phpactor\Extension\LanguageServer;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\LanguageServer\Handler\InitializeHandler;
use Phpactor\Extension\LanguageServer\Handler\PhpactorHandlerLoader;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\Command\StartCommand;
use Phpactor\LanguageServer\Core\Handler\ExitHandler;
use Phpactor\LanguageServer\Core\Handler\SystemHandler;
use Phpactor\LanguageServer\Core\Handler\TextDocumentHandler;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\MapResolver\Resolver;

class LanguageServerExtension implements Extension
{
    const SERVICE_LANGUAGE_SERVER_BUILDER = 'language_server.builder';
    const SERVICE_EVENT_EMITTER = 'language_server.event_emitter';

    const TAG_SESSION_HANDLER = 'language_server.session_handler';

    const PARAM_WELCOME_MESSAGE = 'language_server.welcome_message';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_WELCOME_MESSAGE => 'Welcome to a Phpactor Language Server'
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $this->registerServer($container);
        $this->registerCommand($container);
    }

    private function addDefaultHandlers(LanguageServerBuilder $builder, Container $container)
    {
        $builder->addHandler(
            new TextDocumentHandler(
                $container->get(self::SERVICE_EVENT_EMITTER),
                $container->get(self::SERVICE_SESSION_MANAGER)
            )
        );
        $builder->addHandler(new ExitHandler());
        $builder->addHandler(new SystemHandler($container->get(self::SERVICE_SESSION_MANAGER)));
    }

    private function registerServer(ContainerBuilder $container)
    {
        $container->register(self::SERVICE_LANGUAGE_SERVER_BUILDER, function (Container $container) {
            $builder = LanguageServerBuilder::create(
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
            $builder->enableTextDocumentHandler();
            $builder->addHandlerLoader($container->get('language_server.handler_loader.phpactor'));
        
            return $builder;
        });

        $container->register('language_server.handler_loader.phpactor', function (Container $container) {
            return new PhpactorHandlerLoader($container);
        });
    }

    private function registerCommand(ContainerBuilder $container)
    {
        $container->register('language_server.command.lsp_start', function (Container $container) {
            return new StartCommand($container->get(self::SERVICE_LANGUAGE_SERVER_BUILDER));
        }, [ ConsoleExtension::TAG_COMMAND => [ 'name' => StartCommand::NAME ]]);
    }
}
