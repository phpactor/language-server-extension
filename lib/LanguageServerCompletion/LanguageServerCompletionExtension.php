<?php

namespace Phpactor\Extension\LanguageServerCompletion;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Completion\CompletionExtension;
use Phpactor\Extension\LanguageServerCompletion\Handler\HoverHandler;
use Phpactor\Extension\LanguageServerCompletion\Handler\SignatureHelpHandler;
use Phpactor\Extension\LanguageServerCompletion\Util\SuggestionNameFormatter;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\LanguageServerCompletion\Handler\CompletionHandler;
use Phpactor\Extension\LanguageServer\Util\ClientCapabilitiesProvider;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\MapResolver\Resolver;

class LanguageServerCompletionExtension implements Extension
{
    const PARAM_TRIM_LEADING_DOLLAR = 'language_server_completion.trim_leading_dollar';

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $this->registerHandlers($container);
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_TRIM_LEADING_DOLLAR => false,
        ]);
    }

    private function registerHandlers(ContainerBuilder $container): void
    {
        $container->register('language_server_completion.handler.completion', function (Container $container) {
            $capabilities = $container->getParameter(LanguageServerExtension::PARAM_CLIENT_CAPABILITIES);
            return new CompletionHandler(
                $container->get(LanguageServerExtension::SERVICE_SESSION_WORKSPACE),
                $container->get(CompletionExtension::SERVICE_REGISTRY),
                $container->get(SuggestionNameFormatter::class),
                $container->get(ClientCapabilitiesProvider::class)
            );
        }, [ LanguageServerExtension::TAG_SESSION_HANDLER => [
            'methods' => [
                'textDocument/completion'
            ]
        ]]);

        $container->register(SuggestionNameFormatter::class, function (Container $container) {
            return new SuggestionNameFormatter($container->getParameter(self::PARAM_TRIM_LEADING_DOLLAR));
        });

        $container->register('language_server_completion.handler.signature_help', function (Container $container) {
            return new SignatureHelpHandler(
                $container->get(LanguageServerExtension::SERVICE_SESSION_WORKSPACE),
                $container->get(CompletionExtension::SERVICE_SIGNATURE_HELPER)
            );
        }, [ LanguageServerExtension::TAG_SESSION_HANDLER => [] ]);

        $container->register('language_server_completion.handler.hover', function (Container $container) {
            return new HoverHandler(
                $container->get(LanguageServerExtension::SERVICE_SESSION_WORKSPACE),
                $container->get(WorseReflectionExtension::SERVICE_REFLECTOR),
                $container->get(CompletionExtension::SERVICE_SHORT_DESC_FORMATTER)
            );
        }, [ LanguageServerExtension::TAG_SESSION_HANDLER => []]);
    }
}
