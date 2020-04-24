<?php

namespace Phpactor\Extension\LanguageServer\Util;

use InvalidArgumentException;
use Phpactor\Extension\LanguageServer\Helper\ArrayHelper;

final class ClientCapabilitiesProvider
{
    public const COMPLETION_SUPPORT_SNIPPET = 'textDocument.completion.completionItem.snippetSupport';

    /**
     * @var array
     */
    private $capabilities;

    public function __construct(array $capabilities)
    {
        $this->capabilities = ArrayHelper::flatten($capabilities);
    }

    public function has(string $id): bool
    {
        return isset($this->capabilities[$id]);
    }

    /**
     * @return mixed
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new InvalidArgumentException(\sprintf(
                "The capability '%s' does not exist.",
                $id
            ));
        }

        return $this->capabilities[$id];
    }
}
