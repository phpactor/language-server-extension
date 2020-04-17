<?php

namespace Phpactor\Extension\LanguageServerCompletion\Util;

use Phpactor\Completion\Core\Suggestion;
use Phpactor\Extension\LanguageServerCompletion\Protocol\InsertText;

/**
 * Generates an InsertText from a Suggestion
 *
 * @see InsertText
 * @see Suggestion
 * @final
 */
/* final */ class SuggestionInsertTextFactory
{
    private const FORMAT_METHODS = [
        Suggestion::TYPE_FUNCTION => 'formatAllKindOfFunctions',
        Suggestion::TYPE_METHOD => 'formatAllKindOfFunctions',
        Suggestion::TYPE_CONSTRUCTOR => 'formatAllKindOfFunctions',
    ];

    public function createFrom(Suggestion $suggestion): InsertText
    {
        if (!$formatMethod = self::FORMAT_METHODS[$suggestion->type()] ?? null) {
            return new InsertText(null);
        }

        return new InsertText($this->{$formatMethod}($suggestion));
    }

    private function formatAllKindOfFunctions(Suggestion $suggestion): ?string
    {
        $matches = [];
        \preg_match(
            '/(\w+)\s*\(\s*([^)]*)\s*\)/',
            $suggestion->shortDescription(),
            $matches
        );

        if (empty($matches)) { // Ensure it looks like a function
            return null;
        }

        [,$name, $parametersAsString] = $matches;

        if (empty(trim($parametersAsString))) { // Without parameters
            return "$name()"; // Include the parentheses
        }

        $placeholders = [];
        $position = 0;
        foreach (\explode(',', $parametersAsString) as $parameter) {
            if (false !== \strpos($parameter, '=')) {
                continue; // Ignore optional parameters
            }

            $parameterName = \preg_replace('/.*(\$\w+).*/', '\1', $parameter);
            $placeholders[] = InsertText::placeholder(++$position, $parameterName);
        }

        return \sprintf(
            '%s(%s)%s',
            $name,
            // If no placeholders then all parameters are optional
            // But we still want to stop between the parentheses
            \implode(', ', $placeholders ?: [InsertText::placeholder(1)]),
            InsertText::placeholder(0)
        );
    }
}
