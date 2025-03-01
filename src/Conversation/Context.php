<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Conversation;

class Context
{
    protected ?string $step = null;

    /**
     * @var array<string, mixed>
     */
    protected array $properties = [];

    public function __construct(protected string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStep(): ?string
    {
        return $this->step;
    }

    public function setStep(?string $step): void
    {
        $this->step = $step;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $key): mixed
    {
        return $this->properties[$key] ?? null;
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function setProperty(string $key, mixed $value): void
    {
        $this->properties[$key] = $value;
    }

    /**
     * @return array{name: string, step: ?string, properties: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'step' => $this->getStep(),
            'properties' => $this->getProperties(),
        ];
    }
}
