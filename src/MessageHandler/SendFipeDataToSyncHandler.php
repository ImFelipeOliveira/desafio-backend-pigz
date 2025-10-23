<?php

namespace App\MessageHandler;

use App\Message\SendFipeDataToSync;
use App\Application\UseCase\Fipe\SyncFipeDataUseCase;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
final class SendFipeDataToSyncHandler
{

    public function __construct(
        private LoggerInterface $logger,
        private readonly SyncFipeDataUseCase $syncFipeDataUseCase
    ) {}

    public function __invoke(SendFipeDataToSync $message): void
    {
        $this->syncFipeDataUseCase->execute($message->data);
        $this->logger->info('Fipe data sent to sync.', ['data' => $message]);
    }
}
