<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\CallbackData;

use TelegramBot\Api\Types\Message;

class CallbackDataFactory
{
    public function __construct(
        private CallbackDataStorageInterface $callbackDataStorage,
    ) {
    }

    /**
     * @param array<string, string|int|float|bool|null> $parameters
     */
    public function create(string $actionName, array $parameters = [], ?Message $message = null): string
    {
        $id = uniqid('callback_', true);
        $messageId = null !== $message ? (string) $message->getMessageId() : '';
        $chatId = null !== $message ? (string) $message->getChat()->getId() : '';

        $filteredParameters = array_filter($parameters, static fn($value) => null !== $value);

        $callbackData = new CallbackData(
            id: $id,
            messageId: $messageId,
            name: $actionName,
            parameters: $filteredParameters,
            chatId: $chatId,
        );

        $this->callbackDataStorage->save($callbackData);

        return $callbackData->getId();
    }

    public function clearByMessageId(string $messageId): void
    {
        $this->callbackDataStorage->clearByMessageId($messageId);
    }
}
