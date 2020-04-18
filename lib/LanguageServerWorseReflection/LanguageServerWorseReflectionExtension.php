<?php

namespace Phpactor\Extension\LanguageServerWorseReflection;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\LanguageServerWorseReflection\SourceLocator\WorkspaceSourceLocator;
use Phpactor\Extension\LanguageServerWorseReflection\Workspace\WorkspaceIndex;
use Phpactor\Extension\LanguageServerWorseReflection\Workspace\WorkspaceIndexListener;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\MapResolver\Resolver;
use Phpactor\WorseReflection\ReflectorBuilder;

class LanguageServerWorseReflectionExtension implements Extension
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
        $container->register(WorkspaceSourceLocator::class, function (Container $container) {
            return new WorkspaceSourceLocator(
                $container->get(WorkspaceIndex::class)
            );
        }, [ WorseReflectionExtension::TAG_SOURCE_LOCATOR => [
            'priority' => 255,
        ]]);

        $container->register(WorkspaceIndexListener::class, function (Container $container) {
            return new WorkspaceIndexListener(
                $container->get(WorkspaceIndex::class),
            );
        }, [ LanguageServerExtension::TAG_LISTENER_PROVIDER => [] ]);

        $container->register(WorkspaceIndex::class, function (Container $container) {
            return new WorkspaceIndex(
                ReflectorBuilder::create()->build()
            );
        });
    }
}
