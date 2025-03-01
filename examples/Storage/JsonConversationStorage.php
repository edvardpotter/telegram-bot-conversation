<?php

declare(strict_types=1);

use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Edvardpotter\TelegramBotConversation\Conversation\ConversationStorageInterface;
use Edvardpotter\TelegramBotConversation\Conversation\Context;

class JsonConversationStorage implements ConversationStorageInterface
{
    private string $filePath;
    private array $conversations = [];

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->loadFromFile();
    }

    public function getByChatId(string $chatId, ?string $botId = null): ?Conversation
    {
        $key = $this->generateKey($chatId, $botId);
        if (!isset($this->conversations[$key])) {
            return null;
        }

        $data = $this->conversations[$key];

        $conversation = new Conversation();
        $conversation->setChatId($data['chatId']);

        if (isset($data['botId'])) {
            $conversation->setBotId($data['botId']);
        }

        if (isset($data['context']) && null !== $data['context']) {
            $context = new Context($data['context']['name']);
            $context->setStep($data['context']['step']);
            $context->setProperties($data['context']['properties']);
            $conversation->setContext($context);
        }

        return $conversation;
    }

    public function save(Conversation $conversation): void
    {
        $key = $this->generateKey($conversation->getChatId(), $conversation->getBotId());

        $data = [
            'chatId' => $conversation->getChatId(),
            'botId' => $conversation->getBotId(),
            'context' => null,
        ];

        $context = $conversation->getContext();
        if (null !== $context) {
            $data['context'] = $context->toArray();
        }

        $this->conversations[$key] = $data;
        $this->saveToFile();
    }

    private function generateKey(string $chatId, ?string $botId): string
    {
        return $botId ? "{$chatId}:{$botId}" : $chatId;
    }

    private function loadFromFile(): void
    {
        if (!file_exists($this->filePath)) {
            $this->conversations = [];
            return;
        }

        $content = file_get_contents($this->filePath);
        if (false === $content) {
            throw new \RuntimeException("Не удалось прочитать файл: {$this->filePath}");
        }

        $data = json_decode($content, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException("Ошибка разбора JSON: " . json_last_error_msg());
        }

        $this->conversations = $data;
    }

    private function saveToFile(): void
    {
        $content = json_encode($this->conversations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (false === $content) {
            throw new \RuntimeException("Ошибка при создании JSON: " . json_last_error_msg());
        }

        $result = file_put_contents($this->filePath, $content);
        if (false === $result) {
            throw new \RuntimeException("Не удалось сохранить данные в файл: {$this->filePath}");
        }
    }
}
