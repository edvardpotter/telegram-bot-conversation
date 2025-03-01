<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Conversation;

interface ConversationStorageInterface
{
    public function getByChatId(string $chatId, ?string $botId = null): ?Conversation;
    public function save(Conversation $conversation): void;
}
