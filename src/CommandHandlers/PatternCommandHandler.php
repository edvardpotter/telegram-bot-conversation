<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\CommandHandlers;

use Closure;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

class PatternCommandHandler implements CommandHandlerInterface
{
    protected string $name;
    protected string $pattern;
    /** @var array<string> */
    protected array $paramNames = [];
    protected bool $isRegex;

    /**
     * @param array<string> $paramNames
     */
    public function __construct(
        protected BotApi $api,
        protected Closure $handler,
        string $pattern,
        array $paramNames = [],
    ) {
        $this->pattern = $pattern;
        $this->paramNames = $paramNames;
        $this->name = $pattern;
        $this->isRegex = $this->detectIsRegex($pattern);
    }

    public function handle(Message $message, Conversation $conversation): void
    {
        $text = $message->getText() ?? '';
        $parameters = $this->extractParameters($text);

        if (empty($parameters)) {
            return;
        }

        // Вместо передачи параметров как массива, передаем их как отдельные аргументы
        // Сначала стандартные параметры: сообщение, разговор, API
        $args = [$message, $conversation, $this->api];

        // Затем добавляем извлеченные параметры как отдельные аргументы
        if ($this->isRegex && !empty($this->paramNames)) {
            // Если это регулярное выражение с именованными параметрами,
            // сохраняем порядок параметров как указано в paramNames
            foreach ($this->paramNames as $name) {
                $args[] = $parameters[$name] ?? null;
            }
        } elseif ($this->isRegex) {
            // Для регулярного выражения без имен, просто добавляем значения по порядку
            foreach ($parameters as $value) {
                $args[] = $value;
            }
        } else {
            // Для шаблонов с плейсхолдерами, добавляем значения по их именам
            foreach ($parameters as $value) {
                $args[] = $value;
            }
        }

        // Вызываем обработчик с динамическим набором аргументов
        call_user_func_array($this->handler, $args);
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

    public function matchesText(string $text): bool
    {
        return !empty($this->extractParameters($text));
    }

    /**
     * @return array<string, string|null>|array<int, string>
     */
    protected function extractParameters(string $text): array
    {
        if ($this->isRegex) {
            return $this->extractFromRegex($text);
        }

        return $this->extractFromTemplate($text);
    }

    /**
     * @return array<string, string>
     */
    protected function extractFromTemplate(string $text): array
    {
        $pattern = $this->pattern;

        // Заменяем плейсхолдеры на паттерны захвата
        $regexPattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>.+?)', $pattern);

        // Готовим паттерн для строгого сравнения
        $regexPattern = '/^' . str_replace('/', '\/', $regexPattern) . '$/u';

        if (preg_match($regexPattern, $text, $matches)) {
            $result = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $result[$key] = $value;
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * @return array<string, string|null>|array<int, string>
     */
    protected function extractFromRegex(string $text): array
    {
        // Если шаблон не содержит ограничителей, добавляем их
        $pattern = $this->pattern;
        if ('/' !== substr($pattern, 0, 1) && '#' !== substr($pattern, 0, 1)) {
            $pattern = '/' . $pattern . '/u';
        }

        if (preg_match($pattern, $text, $matches)) {
            $result = [];

            // Если есть имена параметров, используем их
            if (!empty($this->paramNames)) {
                // Пропускаем первый элемент (полное совпадение)
                array_shift($matches);

                // Связываем значения с именами параметров
                foreach ($this->paramNames as $index => $name) {
                    $result[$name] = $matches[$index] ?? null;
                }
            } else {
                // Если имена не заданы, просто возвращаем массив совпадений
                // (пропуская первое полное совпадение)
                return array_slice($matches, 1);
            }

            return $result;
        }

        return [];
    }

    protected function detectIsRegex(string $pattern): bool
    {
        // Проверяем, содержит ли паттерн скобки для захвата
        return str_contains($pattern, '(') && str_contains($pattern, ')');
    }
}
