<?php

namespace Phpactor\Extension\LanguageServerHover;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\ObjectRenderer\ObjectRendererBuilder;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\LanguageServerHover\Handler\HoverHandler;
use Phpactor\Container\Extension;
use Phpactor\MapResolver\Resolver;

class LanguageServerHoverExtension implements Extension
{
    private const SERVICE_MARKDOWN_RENDERER = 'language_server_completion.object_renderer.markdown';

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
        $container->register('language_server_completion.handler.hover', function (Container $container) {
            return new HoverHandler(
                $container->get(LanguageServerExtension::SERVICE_SESSION_WORKSPACE),
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(self::SERVICE_MARKDOWN_RENDERER)
            );
        }, [ LanguageServerExtension::TAG_SESSION_HANDLER => []]);

        $container->register(self::SERVICE_MARKDOWN_RENDERER, function (Container $container) {
            return ObjectRendererBuilder::create()
                ->setLogger($container->get(LoggingExtension::SERVICE_LOGGER))
                ->addTemplatePath(__DIR__ . '/../../templates/markdown')
                ->enableInterfaceCandidates()
                ->renderEmptyOnNotFound()
                ->build();
        });
    }
}
