<?php

namespace Phpactor\Extension\LanguageServerReferenceFinder\Handler;

use Amp\Promise;
use LanguageServerProtocol\Location as LspLocation;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\TextDocumentIdentifier;
use Phpactor\Extension\LanguageServer\Helper\OffsetHelper;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\ReferenceFinder\ClassImplementationFinder;
use Phpactor\TextDocument\Location;
use Phpactor\TextDocument\Locations;
use Phpactor\TextDocument\Util\LineAtOffset;
use Phpactor\TextDocument\Util\LineColFromOffset;
use RuntimeException;
use Phpactor\ReferenceFinder\Exception\CouldNotLocateDefinition;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class GotoImplementationHandler implements Handler, CanRegisterCapabilities
{
    /**
     * @var Workspace
     */
    private $workspace;

    /**
     * @var ClassImplementationFinder
     */
    private $finder;

    public function __construct(Workspace $workspace, ClassImplementationFinder $finder)
    {
        $this->workspace = $workspace;
        $this->finder = $finder;
    }

    /**
     * {@inheritDoc}
     */
    public function methods(): array
    {
        return [
            'textDocument/implementation' => 'gotoImplementation',
        ];
    }

    public function gotoImplementation(
        TextDocumentIdentifier $textDocument,
        Position $position
    ): Promise {
        return \Amp\call(function () use ($textDocument, $position) {
            $textDocument = $this->workspace->get($textDocument->uri);
            $phpactorDocument = TextDocumentBuilder::create(
                $textDocument->text
            )->uri(
                $textDocument->uri
            )->language(
                $textDocument->languageId ?? 'php'
            )->build();

            $offset = ByteOffset::fromInt($position->toOffset($textDocument->text));
            $locations = $this->finder->findImplementations($phpactorDocument, $offset);

            return $this->toLocations($locations);
        });
    }

    /**
     * @return LspLocation[]
     */
    private function toLocations(Locations $locations): array
    {
        $lspLocations = [];
        foreach ($locations as $location) {
            assert($location instanceof Location);

            $contents = @file_get_contents($location->uri());

            if (false === $contents) {
                continue;
            }

            $startPosition = OffsetHelper::offsetToPosition($contents, $location->offset()->toInt());
            $lspLocations[] = new LspLocation($location->uri()->__toString(), new Range(
                $startPosition,
                $startPosition
            ));
        }

        return $lspLocations;
    }

    public function registerCapabiltiies(ServerCapabilities $capabilities)
    {
        $capabilities->implementationProvider = true;
    }
}
