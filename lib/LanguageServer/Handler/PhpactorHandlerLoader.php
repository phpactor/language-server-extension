<?php

namespace Phpactor\Extension\LanguageServer\Handler;

use LanguageServerProtocol\InitializeParams;
use Phpactor\Container\Container;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\LanguageServer\LanguageServerExtension;
use Phpactor\Extension\LanguageServer\LanguageServerSessionExtension;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\LanguageServer\Core\Handler\HandlerLoader;
use Phpactor\LanguageServer\Core\Handler\Handlers;
use Phpactor\LanguageServer\Core\Server\SessionServices;
use Phpactor\MapResolver\Resolver;
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

    public function load(InitializeParams $params, SessionServices $sessionServices): Handlers
    {
        $container = $this->createContainer($params, $sessionServices);
        $handlers = [];

        foreach (array_keys(
            $container->getServiceIdsForTag(LanguageServerExtension::TAG_SESSION_HANDLER)
        ) as $serviceId) {
            $handlers[] = $container->get($serviceId);
        }

        return new Handlers($handlers);
    }

    protected function createContainer(InitializeParams $params, SessionServices $sessionServices): Container
    {
        $container = $this->container;
        $parameters = $container->getParameters();
        $parameters[FilePathResolverExtension::PARAM_PROJECT_ROOT] = TextDocumentUri::fromString(
            $params->rootUri
        )->path();

        $extensionClasses = $container->getParameter(
            PhpactorContainer::PARAM_EXTENSION_CLASSES
        );

        // merge in any language-server specific configuration
        $parameters = array_merge($parameters, $container->getParameter(LanguageServerExtension::PARAM_SESSION_PARAMETERS));

        $container = $this->buildContainer(
            $extensionClasses,
            array_merge($parameters, $params->initializationOptions, [
                LanguageServerExtension::PARAM_CLIENT_CAPABILITIES => $params->capabilities
            ]),
            $sessionServices
        );

        return $container;
    }

    private function buildContainer(array $extensionClasses, array $parameters, SessionServices $services): Container
    {
        $container = new PhpactorContainer();

        $extensions = array_map(function (string $class) {
            return new $class;
        }, $extensionClasses);
        $extensions[] = new LanguageServerSessionExtension($services);

        $resolver = new Resolver();
        $resolver->setDefaults([
            PhpactorContainer::PARAM_EXTENSION_CLASSES => $extensionClasses
        ]);
        foreach ($extensions as $extension) {
            $extension->configure($resolver);
        }

        $parameters = $resolver->resolve($parameters);

        foreach ($extensions as $extension) {
            $extension->load($container);
        }

        return $container->build($parameters);
    }
}
