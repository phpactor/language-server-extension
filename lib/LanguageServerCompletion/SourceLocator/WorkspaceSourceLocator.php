<?php

namespace Phpactor\Extension\LanguageServerCompletion\SourceLocator;

use LanguageServerProtocol\TextDocumentItem;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\SourceCodeLocator;

class WorkspaceSourceLocator implements SourceCodeLocator
{
    /**
     * @var Workspace
     */
    private $workspace;

    /**
     * @var SourceCodeReflector
     */
    private $reflector;

    public function __construct(Workspace $workspace, SourceCodeReflector $reflector)
    {
        $this->workspace = $workspace;
        $this->reflector = $reflector;
    }

    /**
     * {@inheritDoc}
     */
    public function locate(Name $name): SourceCode
    {
        foreach ($this->workspace as $textDocument) {
            assert($textDocument instanceof TextDocumentItem);

            $textDocument = TextDocumentBuilder::create(
                $textDocument->text
            )->uri(
                $textDocument->uri
            )->language(
                $textDocument->languageId
            )->build();

            $classes = $this->reflector->reflectClassesIn($textDocument);

            if (false === $classes->has((string) $name)) {
                continue;
            }

            return SourceCode::fromUnknown($textDocument);
        }

        throw new SourceNotFound(sprintf(
            'Class "%s" not found',
            (string) $name
        ));
    }
}
