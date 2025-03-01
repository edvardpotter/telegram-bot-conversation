<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\CommandHandlers;

use Closure;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

class SimpleCommandHandler implements CommandHandlerInterface
{
    protected string $name;

    public function __construct(
        protected BotApi $api,
        protected Closure $handler,
    ) {
    }

    public function handle(Message $message, Conversation $conversation): void
    {
        ($this->handler)($message, $conversation, $this->api);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isConversational(): bool
    {
        return false;
    }
}
