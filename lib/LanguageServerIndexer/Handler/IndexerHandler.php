<?php

namespace Phpactor\Extension\LanguageServerIndexer\Handler;

use Amp\CancellationToken;
use Amp\CancelledException;
use Amp\Delayed;
use Amp\Promise;
use Amp\Success;
use LanguageServerProtocol\MessageType;
use Phpactor\AmpFsWatch\Watcher;
use Phpactor\Indexer\Model\IndexBuilder;
use Phpactor\LanguageServer\Core\Handler\ServiceProvider;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\LanguageServer\Core\Service\ServiceManager;
use Psr\Log\LoggerInterface;
use SplFileInfo;

class IndexerHandler implements ServiceProvider
{
    const SERVICE_INDEXER = 'indexer';

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var Watcher
     */
    private $watcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var IndexBuilder
     */
    private $indexBuilder;

    public function __construct(
        Indexer $indexer,
        IndexBuilder $indexBuilder,
        Watcher $watcher,
        LoggerInterface $logger
    ) {
        $this->indexer = $indexer;
        $this->watcher = $watcher;
        $this->logger = $logger;
        $this->indexBuilder = $indexBuilder;
    }

    /**
     * @return array<string>
     */
    public function methods(): array
    {
        return [
            'indexer/reindex' => 'reindex',
        ];
    }

    /**
     * @return array<string>
     */
    public function services(): array
    {
        return [
            self::SERVICE_INDEXER
        ];
    }

    /**
     * @return Promise<mixed>
     */
    public function indexer(MessageTransmitter $transmitter, CancellationToken $cancel): Promise
    {
        return \Amp\call(function () use ($transmitter, $cancel) {
            $job = $this->indexer->getJob();
            $size = $job->size();
            $this->showMessage($transmitter, sprintf('Indexing "%s" PHP files', $size));

            $index = 0;
            foreach ($job->generator() as $file) {
                $index++;

                if ($index % 500 === 0) {
                    $this->showMessage($transmitter, sprintf(
                        'Indexed %s/%s (%s%%)',
                        $index,
                        $size,
                        number_format($index / $size * 100, 2)
                    ));
                }

                try {
                    $cancel->throwIfRequested();
                } catch (CancelledException $cancelled) {
                    break;
                }

                yield new Delayed(1);
            }

            $this->showMessage($transmitter, 'Index initialized, watching.');

            $process = yield $this->watcher->watch();

            while (null !== $file = yield $process->wait()) {
                try {
                    $cancel->throwIfRequested();
                } catch (CancelledException $cancelled) {
                    break;
                }

                $this->indexBuilder->index(new SplFileInfo($file->path()));
                $this->logger->debug(sprintf('Indexed file: %s', $file->path()));
                yield new Delayed(0);
            }

            return new Success();
        });
    }

    public function reindex(ServiceManager $serviceManager, bool $soft = false): Promise
    {
        return \Amp\call(function () use ($serviceManager, $soft) {
            if ($serviceManager->isRunning(self::SERVICE_INDEXER)) {
                $serviceManager->stop(self::SERVICE_INDEXER);
            }

            if (false === $soft) {
                $this->indexer->reset();
            }

            $serviceManager->start(self::SERVICE_INDEXER);
        });
    }

    private function showMessage(MessageTransmitter $transmitter, string $message): void
    {
        $transmitter->transmit(new NotificationMessage('window/showMessage', [
            'type' => MessageType::INFO,
            'message' => $message
        ]));
    }
}
