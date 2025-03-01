<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Conversation;

class Conversation
{
    private string $chatId;
    private ?string $botId = null;
    private ?Context $context = null;

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function setChatId(string $chatId): void
    {
        $this->chatId = $chatId;
    }

    public function getBotId(): ?string
    {
        return $this->botId;
    }

    public function setBotId(?string $botId): void
    {
        $this->botId = $botId;
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }

    public function setContext(?Context $context): void
    {
        $this->context = $context;
    }
}
