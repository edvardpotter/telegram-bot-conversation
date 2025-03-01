<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\CommandHandlers;

use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use TelegramBot\Api\Types\Message;

interface CommandHandlerInterface
{
    public function handle(Message $message, Conversation $conversation): void;

    public function getName(): string;

    public function isConversational(): bool;
}
