<?php

namespace Phpactor\Extension\LanguageServer\Handler;

use LanguageServerProtocol\InitializeParams;
use Phpactor\Container\Container;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\LanguageServer\Core\Handler\HandlerLoader;
use Phpactor\LanguageServer\Core\Handler\Handlers;
use Phpactor\TextDocument\TextDocumentUri;

class PhpactorHandlerLoader implements HandlerLoader
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function load(InitializeParams $params): Handlers
    {
        $container = $this->createContainer($params->rootUri, $params->initializationOptions);
        $handlers = [];

        foreach (array_keys(
            $container->getServiceIdsForTag(LanguageServerExtension::TAG_SESSION_HANDLER)
        ) as $serviceId) {
            $handlers[] = $container->get($serviceId);
        }

        return new Handlers($handlers);
    }

    protected function createContainer(string $rootUri, array $config): Container
    {
        $container = $this->container;
        $parameters = $container->getParameters();
        $parameters[FilePathResolverExtension::PARAM_PROJECT_ROOT] = TextDocumentUri::fromString($rootUri)->path();

        $extensionClasses = $container->getParameter(
            PhpactorContainer::PARAM_EXTENSION_CLASSES
        );

        if (in_array('Phpactor\Extension\WorseReflection\WorseReflectionExtension', $extensionClasses)) {
            // this is necessary for Phpactor RPC (single request process) but
            // not with the language server (which has up-to-date sources in
            // the workspace)
            $parameters['worse_reflection.enable_context_location'] = false;
        }

        $container = PhpactorContainer::fromExtensions(
            $extensionClasses,
            array_merge($parameters, $config)
        );

        return $container;
    }
}
