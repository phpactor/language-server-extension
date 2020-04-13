<?php

namespace Phpactor\Extension\LanguageServerIndexer;

use Phpactor\AmpFsWatch\Watcher;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\LanguageServerIndexer\Handler\IndexerHandler;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\MapResolver\Resolver;
use Psr\Log\LoggerInterface;

class LanguageServerIndexerExtension implements Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $this->registerSourceLocator($container);
    }

    public function configure(Resolver $schema)
    {
    }

    private function registerSourceLocator(ContainerBuilder $container): void
    {
        $container->register(IndexerHandler::class, function (Container $container) {
            return new IndexerHandler(
                $container->get(Indexer::class),
                $container->get(Watcher::class),
                $container->get(LoggerInterface::class)
            );
        }, [ WorseReflectionExtension::TAG_SOURCE_LOCATOR => [
            'priority' => 255,
        ]]);
    }
}
