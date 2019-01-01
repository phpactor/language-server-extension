<?php

namespace Phpactor\Extension\LanguageServer\Handler;

use LanguageServerProtocol\MessageType;
use Phpactor\Container\Container;
use Phpactor\Container\PhpactorContainer;
use Phpactor\FilePathResolverExtension\FilePathResolverExtension;
use Phpactor\LanguageServer\Core\Event\EventEmitter;
use Phpactor\LanguageServer\Core\Handler\InitializeHandler as BaseInitializeHandler;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Session\Session;
use Phpactor\LanguageServer\Core\Session\SessionManager;

class InitializeHandler extends BaseInitializeHandler
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $welcomeMessage;

    public function __construct(
        EventEmitter $emitter,
        SessionManager $manager,
        Container $container,
        string $welcomeMessage = 'Welcome to a Phpactor Language Server'
    ) {
        parent::__construct($emitter, $manager);
        $this->container = $container;
        $this->welcomeMessage = $welcomeMessage;
    }

    public function initialized()
    {
        yield new NotificationMessage('window/showMessage', [
            'type' => MessageType::INFO,
            'message' => $this->welcomeMessage,
        ]);
    }

    protected function createSession(string $rootUri, ?int $processId = null): Session
    {
        $container = $this->container;
        $parameters = $container->getParameters();
        $parameters[FilePathResolverExtension::PARAM_PROJECT_ROOT] = $rootUri;

        $container = PhpactorContainer::fromExtensions(
            $container->getParameter(
                PhpactorContainer::PARAM_EXTENSION_CLASSES
            ),
            $parameters
        );

        return new Session($rootUri, $processId, $container);
    }
}
