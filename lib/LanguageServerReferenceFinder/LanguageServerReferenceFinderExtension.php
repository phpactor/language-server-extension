<?php

namespace Phpactor\Extension\LanguageServerReferenceFinder;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\LanguageServerReferenceFinder\Handler\GotoDefinitionHandler;
use Phpactor\Extension\LanguageServerReferenceFinder\Handler\GotoImplementationHandler;
use Phpactor\Extension\LanguageServerReferenceFinder\Handler\TypeDefinitionHandler;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\ReferenceFinder\ReferenceFinderExtension;
use Phpactor\MapResolver\Resolver;

class LanguageServerReferenceFinderExtension implements Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $container->register(GotoDefinitionHandler::class, function (Container $container) {
            return new GotoDefinitionHandler(
                $container->get(LanguageServerExtension::SERVICE_SESSION_WORKSPACE),
                $container->get(ReferenceFinderExtension::SERVICE_DEFINITION_LOCATOR)
            );
        }, [ LanguageServerExtension::TAG_SESSION_HANDLER => [] ]);

        $container->register(TypeDefinitionHandler::class, function (Container $container) {
            return new TypeDefinitionHandler(
                $container->get(LanguageServerExtension::SERVICE_SESSION_WORKSPACE),
                $container->get(ReferenceFinderExtension::SERVICE_TYPE_LOCATOR)
            );
        }, [ LanguageServerExtension::TAG_SESSION_HANDLER => [] ]);

        $container->register(GotoImplementationHandler::class, function (Container $container) {
            return new GotoImplementationHandler(
                $container->get(LanguageServerExtension::SERVICE_SESSION_WORKSPACE),
                $container->get(ReferenceFinderExtension::SERVICE_IMPLEMENTATION_FINDER)
            );
        }, [ LanguageServerExtension::TAG_SESSION_HANDLER => [] ]);
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }
}
