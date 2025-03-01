<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Stubs;

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackData;
use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataStorageInterface;

/**
 * Заглушка для тестирования хранилища callback-данных
 */
class CallbackDataStorageStub implements CallbackDataStorageInterface
{
    /** @var array<string, CallbackData> */
    private array $items = [];

    public function save(CallbackData $callbackData): void
    {
        $this->items[$callbackData->getId()] = $callbackData;
    }

    public function getById(string $id): ?CallbackData
    {
        return $this->items[$id] ?? null;
    }

    /**
     * @return list<CallbackData>
     */
    public function getByMessageId(string $messageId): array
    {
        $result = [];
        foreach ($this->items as $item) {
            if ($item->getMessageId() === $messageId) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function clearByMessageId(string $messageId): void
    {
        foreach ($this->items as $id => $item) {
            if ($item->getMessageId() === $messageId) {
                unset($this->items[$id]);
            }
        }
    }
}
