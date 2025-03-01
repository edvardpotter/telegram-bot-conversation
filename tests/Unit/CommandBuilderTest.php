<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Unit;

use Edvardpotter\TelegramBotConversation\CommandBuilder;
use Edvardpotter\TelegramBotConversation\CommandHandlers\ConversationCommandHandler;
use Edvardpotter\TelegramBotConversation\CommandHandlers\InlineCommandHandler;
use Edvardpotter\TelegramBotConversation\CommandHandlers\SimpleCommandHandler;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TelegramBot\Api\BotApi;

class CommandBuilderTest extends TestCase
{
    private BotApi $api;
    private CommandBuilder $commandBuilder;

    public function testCommandCreatesSimpleCommandHandler(): void
    {
        $handler = function (): void {
        };

        $this->commandBuilder->command('start', $handler);

        $commands = $this->commandBuilder->getCommands();
        $this->assertArrayHasKey('start', $commands);
        $this->assertInstanceOf(SimpleCommandHandler::class, $commands['start']);
    }

    public function testConversationCreatesConversationCommandHandler(): void
    {
        $initialHandler = function (): void {
        };

        $this->commandBuilder->conversation('register', $initialHandler);

        $commands = $this->commandBuilder->getCommands();
        $this->assertArrayHasKey('register', $commands);
        $this->assertInstanceOf(ConversationCommandHandler::class, $commands['register']);
    }

    public function testStepAddsHandlerToConversation(): void
    {
        $initialHandler = function (): void {
        };
        $stepHandler = function (): void {
        };

        $this->commandBuilder->conversation('register', $initialHandler)
            ->step('name', $stepHandler);

        // Проверяем шаги через рефлексию
        $steps = $this->getPrivateProperty($this->commandBuilder, 'steps');

        $this->assertArrayHasKey('register', $steps);
        $this->assertArrayHasKey('name', $steps['register']);
        $this->assertSame($stepHandler, $steps['register']['name']);
    }

    public function testStepWithoutConversationThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Step must be defined within a conversation');

        $this->commandBuilder->step('name', function (): void {
        });
    }

    public function testInlineCreatesInlineCommandHandler(): void
    {
        $handler = function (): void {
        };

        $this->commandBuilder->inline('button_click', $handler);

        $commands = $this->commandBuilder->getInlineCommands();
        $this->assertArrayHasKey('button_click', $commands);
        $this->assertInstanceOf(InlineCommandHandler::class, $commands['button_click']);
        $this->assertEquals('button_click', $commands['button_click']->getName());
    }

    protected function setUp(): void
    {
        $this->api = Mockery::mock(BotApi::class);

        $this->commandBuilder = new CommandBuilder($this->api);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Вспомогательный метод для доступа к private/protected свойствам
     *
     * @param object $object Объект, содержащий свойство
     * @param string $propertyName Имя свойства
     * @return mixed Значение свойства
     */
    private function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
