<?php

namespace Phpactor\Extension\LanguageServerReferenceFinder\Handler;

use Amp\Promise;
use LanguageServerProtocol\Location;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\TextDocumentIdentifier;
use Phpactor\Extension\LanguageServer\Helper\OffsetHelper;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\ReferenceFinder\Exception\CouldNotLocateType;
use Phpactor\ReferenceFinder\TypeLocator;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use RuntimeException;

class TypeDefinitionHandler implements Handler, CanRegisterCapabilities
{
    /**
     * @var TypeLocator
     */
    private $typeLocator;

    /**
     * @var Workspace
     */
    private $workspace;

    public function __construct(Workspace $workspace, TypeLocator $typeLocator)
    {
        $this->typeLocator = $typeLocator;
        $this->workspace = $workspace;
    }

    public function methods(): array
    {
        return [
            'textDocument/typeDefinition' => 'type',
        ];
    }

    public function type(
        TextDocumentIdentifier $textDocument,
        Position $position
    ): Promise {
        return \Amp\call(function () use ($textDocument, $position) {
            $textDocument = $this->workspace->get($textDocument->uri);

            $offset = $position->toOffset($textDocument->text);

            try {
                $location = $this->typeLocator->locateType(
                    TextDocumentBuilder::create($textDocument->text)->uri($textDocument->uri)->language('php')->build(),
                    ByteOffset::fromInt($offset)
                );
            } catch (CouldNotLocateType $type) {
                return null;
            }

            // this _should_ exist for sure, but would be better to refactor the
            // goto type result to return the source code.
            $sourceCode = file_get_contents($location->uri());

            if (false === $sourceCode) {
                throw new RuntimeException(sprintf(
                    'Could not read file "%s"',
                    $location->uri()
                ));
            }

            $startPosition = OffsetHelper::offsetToPosition(
                $sourceCode,
                $location->offset()->toInt()
            );

            $location = new Location($location->uri(), new Range(
                $startPosition,
                $startPosition
            ));

            return $location;
        });
    }

    public function registerCapabiltiies(ServerCapabilities $capabilities): void
    {
        $capabilities->typeDefinitionProvider = true;
    }
}
