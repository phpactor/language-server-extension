<?php

namespace Phpactor\Extension\LanguageServerIndexer\Handler;

use Amp\CancellationToken;
use Amp\CancelledException;
use Amp\Delayed;
use Amp\Promise;
use Amp\Success;
use LanguageServerProtocol\MessageType;
use Phpactor\AmpFsWatch\ModifiedFile;
use Phpactor\AmpFsWatch\Watcher;
use Phpactor\LanguageServer\Core\Handler\ServiceProvider;
use Phpactor\LanguageServer\Core\Rpc\NotificationMessage;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Phpactor\Indexer\Model\Indexer;
use Phpactor\LanguageServer\Core\Service\ServiceManager;
use Psr\Log\LoggerInterface;

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

    public function __construct(
        Indexer $indexer,
        Watcher $watcher,
        LoggerInterface $logger
    ) {
        $this->indexer = $indexer;
        $this->watcher = $watcher;
        $this->logger = $logger;
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
                assert($file instanceof ModifiedFile);
                $job = $this->indexer->getJob($file->path());

                foreach ($job->generator() as $file) {
                    $this->logger->debug(sprintf('Indexed file: %s', $file));
                    yield new Delayed(1);
                }
            }

            return new Success();
        });
    }

    public function reindex(ServiceManager $serviceManager, bool $hard = false): Promise
    {
        return \Amp\call(function () use ($serviceManager, $hard) {
            if ($serviceManager->isRunning(self::SERVICE_INDEXER)) {
                $serviceManager->stop(self::SERVICE_INDEXER);
            }

            if ($hard) {
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
