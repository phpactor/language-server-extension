<?php

namespace Phpactor\Extension\LanguageServer;

use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\LanguageServer\Dispatcher\PhpactorDispatcherFactory;
use Phpactor\Extension\LanguageServer\Handler\PhpactorHandlerLoader;
use Phpactor\Extension\LanguageServer\Handler\SessionHandler;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\Console\ConsoleExtension;
use Phpactor\Extension\LanguageServer\Command\StartCommand;
use Phpactor\LanguageServer\Adapter\DTL\DTLArgumentResolver;
use Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver;
use Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver\ChainArgumentResolver;
use Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver\LanguageSeverProtocolParamsResolver;
use Phpactor\LanguageServer\Core\Handler\HandlerMethodResolver;
use Phpactor\LanguageServer\Core\Handler\HandlerMethodRunner;
use Phpactor\LanguageServer\Core\Handler\MethodRunner;
use Phpactor\LanguageServer\Core\Handler\Handlers;
use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher\MiddlewareDispatcher;
use Phpactor\LanguageServer\Core\Server\ClientApi;
use Phpactor\LanguageServer\Core\Server\ServerStats;
use Phpactor\LanguageServer\Core\Service\ServiceProviders;
use Phpactor\LanguageServer\Core\Service\ServiceManager;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\LanguageServer\Handler\System\SystemHandler;
use Phpactor\LanguageServer\Middleware\HandlerMiddleware;
use Phpactor\LanguageServer\Core\Session\WorkspaceListener;
use Phpactor\LanguageServer\Middleware\CancellationMiddleware;
use Phpactor\LanguageServer\Handler\System\ServiceHandler;
use Phpactor\LanguageServer\Middleware\InitializeMiddleware;
use Phpactor\LanguageServer\Middleware\ErrorHandlingMiddleware;
use Phpactor\LanguageServer\Handler\TextDocument\TextDocumentHandler;
use Phpactor\LanguageServer\Handler\Workspace\CommandHandler;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Phpactor\LanguageServer\Workspace\CommandDispatcher;
use Phpactor\MapResolver\Resolver;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

class LanguageServerExtension implements Extension
{
    public const SERVICE_LANGUAGE_SERVER_BUILDER = 'language_server.builder';
    public const SERVICE_EVENT_EMITTER = 'language_server.event_emitter';
    public const SERVICE_SESSION_WORKSPACE = 'language_server.session.workspace';
    public const TAG_SESSION_HANDLER = 'language_server.session_handler';
    public const TAG_COMMAND = 'language_server.command';
    public const TAG_LISTENER_PROVIDER = 'language_server.listener_provider';

    public const PARAM_SESSION_PARAMETERS = 'language_server.session_parameters';
    public const PARAM_CLIENT_CAPABILITIES = 'language_server.client_capabilities';
    public const PARAM_ENABLE_WORKPACE = 'language_server.enable_workspace';
    public const PARAM_CATCH_ERRORS = 'language_server.catch_errors';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_CATCH_ERRORS => true,
            self::PARAM_CLIENT_CAPABILITIES => [],
            self::PARAM_ENABLE_WORKPACE => true,
            self::PARAM_SESSION_PARAMETERS => [],
        ]);
        $schema->setDescriptions([
            self::PARAM_SESSION_PARAMETERS => 'Phpactor parameters (config) that apply only to the language server session',
            self::PARAM_CLIENT_CAPABILITIES => 'For internal use only: will contain the capabilities of the connected language server client',
            self::PARAM_ENABLE_WORKPACE => <<<'EOT'
If workspace management / text synchronization should be enabled (this isn't required for some language server implementations, e.g. static analyzers)
EOT
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
        $this->registerServiceManager($container);
        $this->registerMiddleware($container);
        $this->registerHandlers($container);
    }

    private function registerServer(ContainerBuilder $container): void
    {
        $container->register(ServerStats::class, function (Container $container) {
            return new ServerStats();
        });

        $container->register(LanguageServerBuilder::class, function (Container $container) {
            $builder = LanguageServerBuilder::create(
                new PhpactorDispatcherFactory($container),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );

            return $builder;
        });
    }

    private function registerCommand(ContainerBuilder $container): void
    {
        if (!class_exists(ConsoleExtension::class)) {
            return;
        }

        $container->register('language_server.command.lsp_start', function (Container $container) {
            return new StartCommand($container->get(LanguageServerBuilder::class));
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

        $container->register('language_server.session.handler.session', function (Container $container) {
            return new SessionHandler(
                $container,
                $container->get(ClientApi::class),
                $container->get(self::SERVICE_SESSION_WORKSPACE),
            );
        }, [ self::TAG_SESSION_HANDLER => []]);

        $container->register(ServiceHandler::class, function (Container $container) {
            return new ServiceHandler($container->get(ServiceManager::class), $container->get(ClientApi::class));
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

    private function registerCommandDispatcher(ContainerBuilder $container): void
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
                assert(is_string($attrs['name']));
                $map[$attrs['name']] = $container->get($serviceId);
            }

            return new CommandDispatcher($map);
        });
    }

    private function registerServiceManager(ContainerBuilder $container): void
    {
        $container->register(ServiceManager::class, function (Container $container) {
            return new ServiceManager(
                new ServiceProviders([]),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        });
    }

    private function registerMiddleware(ContainerBuilder $container): void
    {
        $container->register(MiddlewareDispatcher::class, function (Container $container) {
            $stack = [];

            if ($container->getParameter(self::PARAM_CATCH_ERRORS)) {
                $stack[] = new ErrorHandlingMiddleware($container->get(LoggingExtension::SERVICE_LOGGER));
            }

            $stack[] = new InitializeMiddleware(
                $container->get(Handlers::class),
                $container->get(EventDispatcherInterface::class)
            );

            $stack[] = new CancellationMiddleware(
                $container->get(MethodRunner::class)
            );

            $stack[] = new HandlerMiddleware(
                $container->get(MethodRunner::class)
            );

            return new MiddlewareDispatcher(...$stack);
        });
    }

    private function registerHandlers(ContainerBuilder $container): void
    {
        $container->register(ArgumentResolver::class, function (Container $container) {
            return new ChainArgumentResolver(
                new LanguageSeverProtocolParamsResolver(),
                new DTLArgumentResolver(),
            );
        });
        $container->register(MethodRunner::class, function (Container $container) {
            return new HandlerMethodRunner(
                $container->get(Handlers::class),
                new HandlerMethodResolver(),
                $container->get(ArgumentResolver::class),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        });

        $container->register(Handlers::class, function (Container $container) {
            $handlers = [];
        
            foreach (array_keys(
                $container->getServiceIdsForTag(LanguageServerExtension::TAG_SESSION_HANDLER)
            ) as $serviceId) {
                $handlers[] = $container->get($serviceId);
            }
        
            return new Handlers($handlers);
        });

        $container->register(TextDocumentHandler::class, function (Container $container) {
            return new TextDocumentHandler($container->get(EventDispatcherInterface::class));
        }, [ self::TAG_SESSION_HANDLER => []]);

        $container->register(SystemHandler::class, function (Container $container) {
            return new SystemHandler(
                $container->get(ClientApi::class),
                $container->get(ServerStats::class)
            );
        }, [ self::TAG_SESSION_HANDLER => []]);
    }
}
