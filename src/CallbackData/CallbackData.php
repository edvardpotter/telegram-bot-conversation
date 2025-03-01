<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\CallbackData;

readonly class CallbackData
{
    /**
     * @param array<string, scalar> $parameters
     */
    public function __construct(
        protected string $id,
        protected string $messageId,
        protected string $name,
        protected array $parameters = [],
        protected string $chatId = '',
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, scalar>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }
}
