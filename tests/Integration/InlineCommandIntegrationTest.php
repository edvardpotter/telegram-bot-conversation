<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Integration;

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackData;
use Edvardpotter\TelegramBotConversation\CommandBuilder;
use Edvardpotter\TelegramBotConversation\CommandHandlers\InlineCommandHandler;
use Edvardpotter\TelegramBotConversation\Conversation\Context;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Edvardpotter\TelegramBotConversation\Tests\Stubs\CallbackDataStorageStub;
use Mockery;
use PHPUnit\Framework\TestCase;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class InlineCommandIntegrationTest extends TestCase
{
    private BotApi $api;
    private CommandBuilder $commandBuilder;
    private CallbackDataStorageStub $callbackDataStorage;
    private Message $message;
    private Conversation $conversation;
    private bool $commandHandled = false;

    public function testInlineCommandIntegration(): void
    {
        // Настройка ожидаемого поведения API
        $this->api->shouldReceive('sendMessage')
            ->with('123456', 'Hello from inline command!')
            ->once();

        // Создаем и сохраняем данные callback
        $callbackData = new CallbackData(
            'callback_id_123',
            'message_123',
            'button_click',
            ['param1' => 'value1'],
            '123456',
        );
        $this->callbackDataStorage->save($callbackData);

        // Регистрация inline команды
        $this->commandBuilder->inline(
            'button_click',
            function (Message $message, Conversation $conversation, array $params, BotApi $api): void {
                $this->commandHandled = true;

                // Проверяем, что правильные параметры переданы в обработчик
                $this->assertSame($this->message, $message);
                $this->assertSame($this->conversation, $conversation);
                $this->assertSame($this->api, $api);
                $this->assertEquals(['param1' => 'value1'], $params);

                // Отправляем сообщение
                $api->sendMessage($conversation->getChatId(), 'Hello from inline command!');
            },
        );

        // Получаем команду
        $inlineCommands = $this->commandBuilder->getInlineCommands();
        $this->assertArrayHasKey('button_click', $inlineCommands);

        $commandHandler = $inlineCommands['button_click'];
        $this->assertInstanceOf(InlineCommandHandler::class, $commandHandler);

        // Выполняем команду с параметрами из callback
        $commandHandler->handle(
            $this->message,
            $this->conversation,
            $callbackData->getParameters(),
        );

        // Проверяем результаты
        $this->assertTrue($this->commandHandled, 'Обработчик команды не был вызван');
    }

    protected function setUp(): void
    {
        $this->api = Mockery::mock(BotApi::class);

        $this->callbackDataStorage = new CallbackDataStorageStub();

        $this->commandBuilder = new CommandBuilder($this->api);

        // Подготовка сообщения
        $this->message = Mockery::mock(Message::class);
        $this->message->shouldReceive('getText')->andReturn('/test');

        // Создаем объект разговора
        $this->conversation = new Conversation();
        $this->conversation->setChatId('123456');
        $this->conversation->setBotId('bot123');

        $context = new Context('default');
        $this->conversation->setContext($context);

        $this->commandHandled = false;
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
