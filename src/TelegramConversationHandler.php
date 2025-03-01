<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation;

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataFactory;
use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataStorageInterface;
use Edvardpotter\TelegramBotConversation\CommandHandlers\PatternCommandHandler;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Edvardpotter\TelegramBotConversation\Conversation\ConversationStorageInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class TelegramConversationHandler
{
    private CommandBuilder $commandBuilder;

    public function __construct(
        protected ConversationStorageInterface $conversationStorage,
        protected CallbackDataStorageInterface $callbackDataStorage,
        protected BotApi $api,
        protected CallbackDataFactory $callbackDataFactory,
        protected ?string $botId = null,
    ) {
        $this->commandBuilder = new CommandBuilder($this->api);
    }

    public function commands(): CommandBuilder
    {
        return $this->commandBuilder;
    }

    public function handle(Update $update): void
    {
        $message = $update->getMessage();

        $actionName = null;
        $actionParameters = [];

        $callbackQuery = $update->getCallbackQuery();
        if (
            null !== $callbackQuery &&
            !empty($callbackQuery->getData())
        ) {
            $callbackDataEntity = $this->callbackDataStorage->getById($callbackQuery->getData());
            if (null !== $callbackDataEntity) {
                $actionName = $callbackDataEntity->getName();
                if ($actionName) {
                    $actionParameters = $callbackDataEntity->getParameters();
                    $message = $callbackQuery->getMessage();
                }
            }
        }

        $conversation = null;
        if (null !== $message && null !== $message->getChat()) {
            $chatId = (string) $message->getChat()->getId();

            $conversation = $this->conversationStorage->getByChatId($chatId, $this->botId);

            if (null === $conversation) {
                $conversation = new Conversation();
                $conversation->setChatId($chatId);
                $conversation->setBotId($this->botId);
                $this->conversationStorage->save($conversation);
            }
        }

        if (null !== $actionName) {
            $this->handleInlineCommand($actionName, $message, $conversation, $actionParameters);
            $this->conversationStorage->save($conversation);
            return;
        }

        if (null === $message) {
            return;
        }

        if (null === $message->getContact()) {
            $message->setText(trim($message->getText() ?? ''));
            if (empty($message->getText()) && '0' !== $message->getText()) {
                return;
            }
        }

        $commandName = $conversation->getContext()?->getName() ?? $message->getText();

        if (null !== $conversation->getContext()) {
            $this->handleCommand($commandName, $message, $conversation);
        } else {
            $text = $message->getText();

            // Сначала пробуем найти точное совпадение команды
            if (isset($this->commandBuilder->getCommands()[$text])) {
                $this->handleCommand($text, $message, $conversation);
                $this->conversationStorage->save($conversation);
                return;
            }

            // Если точного совпадения нет, пробуем искать по шаблонам
            $patternHandler = $this->findMatchingPatternHandler($text);
            if (null !== $patternHandler) {
                $patternHandler->handle($message, $conversation);
                $this->conversationStorage->save($conversation);
                return;
            }

            // Если ничего не нашли, используем стандартную обработку
            $this->handleCommand($text, $message, $conversation);
        }

        $this->conversationStorage->save($conversation);
    }

    protected function handleCommand(string $name, Message $message, Conversation $conversation): void
    {
        $commands = $this->commandBuilder->getCommands();
        if (!isset($commands[$name])) {
            $conversation->setContext(null);
            return;
        }

        $commands[$name]->handle($message, $conversation);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function handleInlineCommand(
        string $name,
        Message $message,
        Conversation $conversation,
        array $parameters = [],
    ): void {
        $inlineCommands = $this->commandBuilder->getInlineCommands();
        if (isset($inlineCommands[$name])) {
            $inlineCommands[$name]->handle($message, $conversation, $parameters);
        }
    }

    protected function findMatchingPatternHandler(string $text): ?PatternCommandHandler
    {
        foreach ($this->commandBuilder->getPatternCommands() as $handler) {
            if ($handler->matchesText($text)) {
                return $handler;
            }
        }

        return null;
    }
}
