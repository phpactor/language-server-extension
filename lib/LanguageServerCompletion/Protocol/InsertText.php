<?php

namespace Phpactor\Extension\LanguageServerCompletion\Protocol;

use LanguageServerProtocol\InsertTextFormat;

final class InsertText
{
    /**
     * @var string|null
     */
    private $value;

    /**
     * @var int|null
     */
    private $type;

    public function __construct(?string $value, ?int $type = null)
    {
        $this->value = $value;
        $this->type = $type ?: self::deduceType($value);
    }

    public static function plainText(string $value): self
    {
        return new self($value, InsertTextFormat::PLAIN_TEXT);
    }

    public static function snippet(string $value): self
    {
        return new self($value, InsertTextFormat::SNIPPET);
    }

    public static function placeholder(int $position, ?string $text = null): string
    {
        $text = $text ? ":$text" : null;
        // Important to escape the backslash first!
        $text = str_replace(['\\', '$', '}'], ['\\\\', '\$', '\}'], $text);

        return \sprintf('${%d%s}', $position, $text);
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function type(): ?int
    {
        return $this->type;
    }

    private static function deduceType(?string $value): ?int
    {
        if (\is_null($value)) {
            return null;
        }

        return \preg_match('/(?<!\\\)\$(?:(?:{\d+(?::[^}]+)?})|(?:\d+))/', $value)
            ? InsertTextFormat::SNIPPET
            : InsertTextFormat::PLAIN_TEXT
        ;
    }
}
