<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\CallbackData;

interface CallbackDataStorageInterface
{
    public function getById(string $id): ?CallbackData;
    public function save(CallbackData $callbackData): void;
    public function clearByMessageId(string $messageId): void;
}
