<?php

namespace Phpactor\Extension\LanguageServer;

use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\LanguageServer\Handler\PhpactorHandlerLoader;
use Phpactor\Extension\LanguageServer\Handler\SessionHandler;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\Command\StartCommand;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\LanguageServer\Core\Session\WorkspaceListener;
use Phpactor\LanguageServer\Handler\System\ServiceHandler;
use Phpactor\LanguageServer\Handler\TextDocument\TextDocumentHandler;
use Phpactor\LanguageServer\Handler\Workspace\CommandHandler;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\LanguageServer\Workspace\CommandDispatcher;
use Phpactor\MapResolver\Resolver;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

class LanguageServerExtension implements Extension
{
    const SERVICE_LANGUAGE_SERVER_BUILDER = 'language_server.builder';
    const SERVICE_EVENT_EMITTER = 'language_server.event_emitter';
    const TAG_SESSION_HANDLER = 'language_server.session_handler';
    const TAG_COMMAND = 'language_server.command';
    const TAG_LISTENER_PROVIDER = 'language_server.listener_provider';
    const SERVICE_SESSION_WORKSPACE = 'language_server.session.workspace';

    const PARAM_CLIENT_CAPABILITIES = 'language_server.client_capabilities';
    const PARAM_WELCOME_MESSAGE = 'language_server.welcome_message';
    const PARAM_ENABLE_WORKPACE = 'language_server.enable_workspace';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_WELCOME_MESSAGE => 'Welcome to a Phpactor Language Server',
            self::PARAM_CLIENT_CAPABILITIES => [],
            self::PARAM_ENABLE_WORKPACE => true,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $this->registerServer($container);
        $this->registerCommand($container);
        $this->registerSession($container);
        $this->registerEventDispatcher($container);
        $this->registerCommandDispatcher($container);
    }

    private function registerServer(ContainerBuilder $container): void
    {
        $container->register(self::SERVICE_LANGUAGE_SERVER_BUILDER, function (Container $container) {
            $builder = LanguageServerBuilder::create(
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
            $builder->addHandlerLoader(
                $container->get('language_server.handler_loader.phpactor')
            );

            return $builder;
        });

        $container->register('language_server.handler_loader.phpactor', function (Container $container) {
            return new PhpactorHandlerLoader($container);
        });
    }

    private function registerCommand(ContainerBuilder $container): void
    {
        if (!class_exists(ConsoleExtension::class)) {
            return;
        }

        $container->register('language_server.command.lsp_start', function (Container $container) {
            return new StartCommand($container->get(self::SERVICE_LANGUAGE_SERVER_BUILDER));
        }, [ ConsoleExtension::TAG_COMMAND => [ 'name' => StartCommand::NAME ]]);
    }

    private function registerSession(ContainerBuilder $container): void
    {
        $container->register(self::SERVICE_SESSION_WORKSPACE, function (Container $container) {
            return new Workspace(
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        });

        $container->register(WorkspaceListener::class, function (Container $container) {
            if ($container->getParameter(self::PARAM_ENABLE_WORKPACE) === false) {
                return null;
            }

            return new WorkspaceListener($container->get(self::SERVICE_SESSION_WORKSPACE));
        }, [
            self::TAG_LISTENER_PROVIDER => [],
        ]);

        $container->register('language_server.session.handler.text_document', function (Container $container) {
            return new TextDocumentHandler($container->get(EventDispatcherInterface::class));
        }, [ self::TAG_SESSION_HANDLER => []]);

        $container->register('language_server.session.handler.session', function (Container $container) {
            return new SessionHandler($container);
        }, [ self::TAG_SESSION_HANDLER => []]);

        $container->register(ServiceHandler::class, function (Container $container) {
            return new ServiceHandler();
        }, [ self::TAG_SESSION_HANDLER => []]);

        $container->register(CommandHandler::class, function (Container $container) {
            return new CommandHandler($container->get(CommandDispatcher::class));
        }, [ self::TAG_SESSION_HANDLER => []]);
    }

    private function registerEventDispatcher(ContainerBuilder $container): void
    {
        $container->register(EventDispatcherInterface::class, function (Container $container) {
            $aggregate = new ListenerProviderAggregate();
            foreach (array_keys($container->getServiceIdsForTag(self::TAG_LISTENER_PROVIDER)) as $serviceId) {
                $listener = $container->get($serviceId);

                // if listener is NULL then assume it was conditionally
                // disabled
                if (null === $listener) {
                    continue;
                }

                $aggregate->attach($listener);
            }

            return new EventDispatcher($aggregate);
        });
    }

    private function registerCommandDispatcher(ContainerBuilder $container)
    {
        $container->register(CommandDispatcher::class, function (Container $container) {
            $map = [];
            foreach ($container->getServiceIdsForTag(self::TAG_COMMAND) as $serviceId => $attrs) {
                if (!isset($attrs['name'])) {
                    throw new RuntimeException(sprintf(
                        'Cannot register command with service ID "%s" Each command must define a "name" attribute',
                        $serviceId
                    ));
                }
                $map[$attrs['name']] = $container->get($serviceId);
            }

            return new CommandDispatcher($map);
        });
    }
}
