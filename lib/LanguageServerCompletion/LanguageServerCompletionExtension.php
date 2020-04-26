<?php

namespace Phpactor\Extension\LanguageServerCompletion;

use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\Completion\CompletionExtension;
use Phpactor\Extension\LanguageServerCompletion\Handler\SignatureHelpHandler;
use Phpactor\Extension\LanguageServerCompletion\Util\SuggestionNameFormatter;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\LanguageServerCompletion\Handler\CompletionHandler;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\MapResolver\Resolver;
use Phpactor\ObjectRenderer\ObjectRendererBuilder;

class LanguageServerCompletionExtension implements Extension
{
    private const PARAM_TRIM_LEADING_DOLLAR = 'language_server_completion.trim_leading_dollar';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
            self::PARAM_TRIM_LEADING_DOLLAR => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container)
    {
        $this->registerHandlers($container);
        $this->registerFormatter($container);
    }

    private function registerHandlers(ContainerBuilder $container): void
    {
        $container->register('language_server_completion.handler.completion', function (Container $container) {
            $capabilities = $container->getParameter(LanguageServerExtension::PARAM_CLIENT_CAPABILITIES);
            return new CompletionHandler(
                $container->get(LanguageServerExtension::SERVICE_SESSION_WORKSPACE),
                $container->get(CompletionExtension::SERVICE_REGISTRY),
                $container->get(SuggestionNameFormatter::class),
                $capabilities['textDocument']['completion']['completionItem']['snippetSupport'] ?? false
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
    }
}
