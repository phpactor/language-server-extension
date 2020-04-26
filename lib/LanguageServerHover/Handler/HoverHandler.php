<?php

namespace Phpactor\Extension\LanguageServerHover\Handler;

use Amp\Promise;
use LanguageServerProtocol\Hover;
use LanguageServerProtocol\MarkedString;
use LanguageServerProtocol\MarkupContent;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\TextDocumentIdentifier;
use Phpactor\Completion\Core\Exception\CouldNotFormat;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Extension\LanguageServer\Helper\OffsetHelper;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\ObjectRenderer\Model\ObjectRenderer;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\WorseReflection\Core\DocBlock\DocBlock;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Reflector;

class HoverHandler implements Handler, CanRegisterCapabilities
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var ObjectRenderer
     */
    private $renderer;

    /**
     * @var Workspace
     */
    private $workspace;

    public function __construct(Workspace $workspace, Reflector $reflector, ObjectRenderer $renderer)
    {
        $this->reflector = $reflector;
        $this->renderer = $renderer;
        $this->workspace = $workspace;
    }

    public function methods(): array
    {
        return [
            'textDocument/hover' => 'hover',
        ];
    }

    public function hover(
        TextDocumentIdentifier $textDocument,
        Position $position
    ): Promise {
        return \Amp\call(function () use ($textDocument, $position) {
            $document = $this->workspace->get($textDocument->uri);
            $offset = $position->toOffset($document->text);
            $document = TextDocumentBuilder::create($document->text)
                ->uri($document->uri)
                ->language('php')
                ->build();

            $offsetReflection = $this->reflector->reflectOffset($document, $offset);

            $symbolContext = $offsetReflection->symbolContext();
            $info = $this->resolveInfo($symbolContext);
            $string = new MarkupContent('markdown', $info);

            return new Hover($string, new Range(
                OffsetHelper::offsetToPosition($document->__toString(), $symbolContext->symbol()->position()->start()),
                OffsetHelper::offsetToPosition($document->__toString(), $symbolContext->symbol()->position()->end())
            ));
        });
    }

    public function registerCapabiltiies(ServerCapabilities $capabilities): void
    {
        $capabilities->hoverProvider = true;
    }

    private function messageFromSymbolContext(SymbolContext $symbolContext): ?string
    {
        try {
            return $this->renderSymbolContext($symbolContext);
        } catch (CouldNotFormat $e) {
        }

        return null;
    }

    private function renderSymbolContext(SymbolContext $symbolContext): ?string
    {
        switch ($symbolContext->symbol()->symbolType()) {
            case Symbol::METHOD:
            case Symbol::PROPERTY:
            case Symbol::CONSTANT:
                return $this->renderMember($symbolContext);
            case Symbol::CLASS_:
                return $this->renderClass($symbolContext->type());
            case Symbol::FUNCTION:
                return $this->renderFunction($symbolContext);
            case Symbol::VARIABLE:
                return $this->renderVariable($symbolContext);
        }

        return null;
    }

    private function renderMember(SymbolContext $symbolContext): string
    {
        $name = $symbolContext->symbol()->name();
        $container = $symbolContext->containerType();

        try {
            $class = $this->reflector->reflectClassLike((string) $container);
            $member = null;

            // note that all class-likes (classes, traits and interfaces) have
            // methods but not all have constants or properties, so we play safe
            // with members() which is first-come-first-serve, rather than risk
            // a fatal error because of a non-existing method.
            $symbolType = $symbolContext->symbol()->symbolType();
            switch ($symbolType) {
                case Symbol::METHOD:
                    $member = $class->methods()->get($name);
                    break;
                case Symbol::CONSTANT:
                    $member = $class->members()->get($name);
                    break;
                case Symbol::PROPERTY:
                    $member = $class->members()->get($name);
                    break;
                default:
                    return sprintf('Unknown symbol type "%s"', $symbolType);
            }

            return $this->prependDocumentation($member->docblock(), $this->renderer->render($member));
        } catch (NotFound $e) {
            return $e->getMessage();
        }
    }

    private function renderFunction(SymbolContext $symbolContext): string
    {
        $name = $symbolContext->symbol()->name();
        $function = $this->reflector->reflectFunction($name);

        return $this->prependDocumentation($function->docblock(), $this->renderer->render($function));
    }

    private function renderVariable(SymbolContext $symbolContext): string
    {
        return $this->renderer->render($symbolContext->types());
    }

    private function renderClass(Type $type): string
    {
        try {
            $class = $this->reflector->reflectClassLike((string) $type);
            return $this->prependDocumentation($class->docblock(), $this->renderer->render($class));
        } catch (NotFound $e) {
            return $e->getMessage();
        }
    }

    private function resolveInfo(SymbolContext $symbolContext): string
    {
        $info = $this->messageFromSymbolContext($symbolContext);
        $info = $info ?: sprintf(
            '%s %s',
            $symbolContext->symbol()->symbolType(),
            $symbolContext->symbol()->name()
        );
        return $info;
    }

    private function prependDocumentation(DocBlock $docBlock, string $info): string
    {
        if (!$docBlock->isDefined()) {
            return $info;
        }

        $documentation = trim($docBlock->formatted());
        if (empty($documentation)) {
            return $info;
        }

        return $info . "\n" . str_repeat('-', mb_strlen($info)) . "\n\n" . $documentation;
    }
}
