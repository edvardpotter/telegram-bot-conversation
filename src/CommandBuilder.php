<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation;

use Closure;
use Edvardpotter\TelegramBotConversation\CommandHandlers\CommandHandlerInterface;
use Edvardpotter\TelegramBotConversation\CommandHandlers\ConversationCommandHandler;
use Edvardpotter\TelegramBotConversation\CommandHandlers\InlineCommandHandler;
use Edvardpotter\TelegramBotConversation\CommandHandlers\PatternCommandHandler;
use Edvardpotter\TelegramBotConversation\CommandHandlers\SimpleCommandHandler;
use TelegramBot\Api\BotApi;

class CommandBuilder
{
    /**
     * @var array<string, CommandHandlerInterface>
     */
    private array $commands = [];

    /**
     * @var array<string, InlineCommandHandler>
     */
    private array $inlineCommands = [];

    /**
     * @var array<string, array<string, Closure>>
     */
    private array $steps = [];

    /**
     * @var array<PatternCommandHandler>
     */
    private array $patternCommands = [];

    private ?string $currentCommand = null;

    public function __construct(
        private BotApi $api,
    ) {
    }

    public function command(string $name, Closure $handler): self
    {
        $this->commands[$name] = new SimpleCommandHandler($this->api, $handler);
        return $this;
    }

    public function patternCommand(string $pattern, Closure $handler, string ...$paramNames): self
    {
        $patternHandler = new PatternCommandHandler(
            $this->api,
            $handler,
            $pattern,
            $paramNames,
        );

        $this->patternCommands[] = $patternHandler;
        return $this;
    }

    public function conversation(string $name, Closure $initialHandler): self
    {
        $this->currentCommand = $name;
        $this->steps[$name] = [];

        $this->step($name, $initialHandler);

        $commandHandler = new ConversationCommandHandler(
            $this->api,
            $name,
            $this->steps[$name] ?? [],
        );

        /** @var CommandHandlerInterface $commandHandler */
        $this->commands[$name] = $commandHandler;

        return $this;
    }

    public function step(string $stepName, Closure $handler): self
    {
        if (null === $this->currentCommand) {
            throw new \RuntimeException('Step must be defined within a conversation');
        }

        $this->steps[$this->currentCommand][$stepName] = $handler;

        if (
            isset($this->commands[$this->currentCommand]) &&
            $this->commands[$this->currentCommand] instanceof CommandHandlers\ConversationCommandHandler
        ) {
            /** @var CommandHandlers\ConversationCommandHandler $commandHandler */
            $commandHandler = $this->commands[$this->currentCommand];
            $commandHandler->addStep($stepName, $handler);
        }

        return $this;
    }

    public function inline(string $name, Closure $handler): self
    {
        $inlineHandler = new InlineCommandHandler(
            $this->api,
            $handler,
        );
        $inlineHandler->setName($name);
        $this->inlineCommands[$name] = $inlineHandler;
        return $this;
    }

    /**
     * @return array<string, CommandHandlerInterface>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return array<string, InlineCommandHandler>
     */
    public function getInlineCommands(): array
    {
        return $this->inlineCommands;
    }

    /**
     * @return array<PatternCommandHandler>
     */
    public function getPatternCommands(): array
    {
        return $this->patternCommands;
    }
}
