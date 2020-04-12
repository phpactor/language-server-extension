<?php

namespace Phpactor\Extension\LanguageServerWorseReflection;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\LanguageServerWorseReflection\SourceLocator\WorkspaceSourceLocator;
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
                $container->get(LanguageServerExtension::SERVICE_SESSION_WORKSPACE),
                ReflectorBuilder::create()->build()
            );
        }, [ WorseReflectionExtension::TAG_SOURCE_LOCATOR => [
            'priority' => 255,
        ]]);
    }
}
