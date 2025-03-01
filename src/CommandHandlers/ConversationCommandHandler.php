<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\CommandHandlers;

use Closure;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Edvardpotter\TelegramBotConversation\Conversation\Context;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

class ConversationCommandHandler implements CommandHandlerInterface
{
    /**
     * @param array<string, Closure> $steps
     */
    public function __construct(
        protected BotApi $api,
        protected string $name,
        protected array $steps = [],
    ) {
    }

    public function handle(Message $message, Conversation $conversation): void
    {
        $context = $conversation->getContext();

        if (null === $context || $context->getName() !== $this->name) {
            $context = new Context($this->name);
            $context->setStep($this->name);
            $conversation->setContext($context);
        }

        $currentStep = $context->getStep();

        if (!isset($this->steps[$currentStep])) {
            $conversation->setContext(null);
            return;
        }

        $handler = $this->steps[$currentStep];
        $nextStep = $handler($message, $conversation, $context, $this->api);

        if (is_string($nextStep)) {
            $context->setStep($nextStep);
        } elseif (null === $nextStep) {
            $conversation->setContext(null);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isConversational(): bool
    {
        return true;
    }

    public function addStep(string $stepName, Closure $handler): self
    {
        $this->steps[$stepName] = $handler;
        return $this;
    }

    /**
     * @return array<string, Closure>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }
}
