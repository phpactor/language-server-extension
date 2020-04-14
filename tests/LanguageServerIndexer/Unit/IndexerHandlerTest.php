<?php

namespace Phpactor\Extension\LanguageServerIndexer\Tests\Unit;

use Amp\CancellationTokenSource;
use Phpactor\AmpFsWatch\ModifiedFile;
use Phpactor\AmpFsWatch\ModifiedFileQueue;
use Phpactor\AmpFsWatch\Watcher\TestWatcher\TestWatcher;
use Phpactor\Extension\LanguageServerIndexer\Handler\IndexerHandler;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\LanguageServer\Core\Server\Transmitter\NullMessageTransmitter;
use Psr\Log\LoggerInterface;
use Phpactor\Extension\LanguageServerIndexer\Tests\IntegrationTestCase;

class IndexerHandlerTest extends IntegrationTestCase
{
    /**
     * @var ObjectProphecy|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    public function testIndexer(): void
    {
        $this->workspace()->put(
            'Foobar.php',
            <<<'EOT'
<?php
EOT
        );
        \Amp\Promise\wait(\Amp\call(function () {
            $indexer = $this->container()->get(Indexer::class);
            $watcher = new TestWatcher(new ModifiedFileQueue([
                new ModifiedFile($this->workspace()->path('Foobar.php'), ModifiedFile::TYPE_FILE),
            ]));
            $handler = new IndexerHandler($indexer, $watcher, $this->logger->reveal());
            $token = (new CancellationTokenSource())->getToken();
            yield $handler->indexer(new NullMessageTransmitter(), $token);
        }));

        $this->logger->debug(sprintf(
            'Indexed file: %s',
            $this->workspace()->path('Foobar.php')
        ))->shouldHaveBeenCalled();
    }
}
