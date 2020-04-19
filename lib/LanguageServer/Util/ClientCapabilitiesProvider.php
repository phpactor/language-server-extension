<?php

namespace Phpactor\Extension\LanguageServer\Util;

use InvalidArgumentException;
use Phpactor\Extension\LanguageServer\Helper\ArrayHelper;
use Psr\Container\ContainerInterface;

final class ClientCapabilitiesProvider implements ContainerInterface
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

    /**
     * {@inheritDoc}
     */
    public function has($id)
    {
        return isset($this->capabilities[$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function get($id)
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
