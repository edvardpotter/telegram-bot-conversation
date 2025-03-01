<?php

declare(strict_types=1);

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackData;
use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataStorageInterface;

class JsonCallbackDataStorage implements CallbackDataStorageInterface
{
    private string $filePath;
    private array $callbackData = [];

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->loadFromFile();
    }

    public function getById(string $id): ?CallbackData
    {
        if (!isset($this->callbackData[$id])) {
            return null;
        }

        $data = $this->callbackData[$id];

        return new CallbackData(
            $data['id'],
            $data['messageId'],
            $data['name'],
            $data['parameters'] ?? [],
            $data['chatId'] ?? '',
        );
    }

    public function save(CallbackData $callbackData): void
    {
        $this->callbackData[$callbackData->getId()] = [
            'id' => $callbackData->getId(),
            'messageId' => $callbackData->getMessageId(),
            'name' => $callbackData->getName(),
            'parameters' => $callbackData->getParameters(),
            'chatId' => $callbackData->getChatId(),
        ];

        $this->saveToFile();
    }

    public function clearByMessageId(string $messageId): void
    {
        foreach ($this->callbackData as $id => $data) {
            if ($data['messageId'] === $messageId) {
                unset($this->callbackData[$id]);
            }
        }

        $this->saveToFile();
    }

    private function loadFromFile(): void
    {
        if (!file_exists($this->filePath)) {
            $this->callbackData = [];
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

        $this->callbackData = $data;
    }

    private function saveToFile(): void
    {
        $content = json_encode($this->callbackData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (false === $content) {
            throw new \RuntimeException("Ошибка при создании JSON: " . json_last_error_msg());
        }

        $result = file_put_contents($this->filePath, $content);
        if (false === $result) {
            throw new \RuntimeException("Не удалось сохранить данные в файл: {$this->filePath}");
        }
    }
}
