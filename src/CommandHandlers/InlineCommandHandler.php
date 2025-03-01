<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\CommandHandlers;

use Closure;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

class InlineCommandHandler implements CommandHandlerInterface
{
    protected string $name;

    public function __construct(
        protected BotApi $api,
        protected Closure $handler,
    ) {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function handle(Message $message, Conversation $conversation, array $parameters = []): void
    {
        ($this->handler)($message, $conversation, $parameters, $this->api);
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
