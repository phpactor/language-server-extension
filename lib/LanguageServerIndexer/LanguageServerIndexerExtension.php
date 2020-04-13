<?php

namespace Phpactor\Extension\LanguageServerIndexer;

use Phpactor\AmpFsWatch\Watcher;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\LanguageServerIndexer\Handler\IndexerHandler;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\MapResolver\Resolver;

class LanguageServerIndexerExtension implements Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $this->registerSessionHandler($container);
    }

    public function configure(Resolver $schema)
    {
    }

    private function registerSessionHandler(ContainerBuilder $container): void
    {
        $container->register(IndexerHandler::class, function (Container $container) {
            return new IndexerHandler(
                $container->get(Indexer::class),
                $container->get(Watcher::class),
                $container->get(LoggingExtension::SERVICE_LOGGER)
            );
        }, [ LanguageServerExtension::TAG_SESSION_HANDLER => []]);
    }
}
