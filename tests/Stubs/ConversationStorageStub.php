<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Stubs;

use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Edvardpotter\TelegramBotConversation\Conversation\ConversationStorageInterface;

/**
 * Заглушка для тестирования хранилища разговоров
 */
class ConversationStorageStub implements ConversationStorageInterface
{
    /** @var array<string, Conversation> */
    private array $conversations = [];

    public function save(Conversation $conversation): void
    {
        $this->conversations[$conversation->getChatId()] = $conversation;
    }

    public function getByChatId(string $chatId, ?string $botId = null): ?Conversation
    {
        return $this->conversations[$chatId] ?? null;
    }

    public function deleteById(string $id): void
    {
        if (isset($this->conversations[$id])) {
            unset($this->conversations[$id]);
        }
    }

    public function clearAll(): void
    {
        $this->conversations = [];
    }
}
