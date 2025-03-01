<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Unit;

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackData;
use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataFactory;
use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataStorageInterface;
use Edvardpotter\TelegramBotConversation\CommandBuilder;
use Edvardpotter\TelegramBotConversation\CommandHandlers\CommandHandlerInterface;
use Edvardpotter\TelegramBotConversation\CommandHandlers\InlineCommandHandler;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Edvardpotter\TelegramBotConversation\Conversation\ConversationStorageInterface;
use Edvardpotter\TelegramBotConversation\Conversation\Context;
use Edvardpotter\TelegramBotConversation\TelegramConversationHandler;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\CallbackQuery;
use TelegramBot\Api\Types\Chat;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class TelegramConversationHandlerTest extends TestCase
{
    private TelegramConversationHandler $handler;
    private BotApi|MockInterface $botApi;
    private ConversationStorageInterface|MockInterface $conversationStorage;
    private CallbackDataStorageInterface|MockInterface $callbackDataStorage;
    private CommandBuilder|MockInterface $commandBuilder;
    private CommandHandlerInterface|MockInterface $commandHandler;
    private InlineCommandHandler|MockInterface $inlineCommandHandler;

    public function testCommandsReturnsCommandBuilder(): void
    {
        $commands = $this->handler->commands();

        $this->assertInstanceOf(CommandBuilder::class, $commands);
    }

    public function testHandleTextCommand(): void
    {
        // Настройка моков
        $chat = Mockery::mock(Chat::class);
        $chat->shouldReceive('getId')->andReturn(123456);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getChat')->andReturn($chat);
        $message->shouldReceive('getText')->andReturn('/start');
        $message->shouldReceive('setText')->with('/start');
        $message->shouldReceive('getContact')->andReturn(null);

        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage')->andReturn($message);
        $update->shouldReceive('getCallbackQuery')->andReturn(null);

        $conversation = new Conversation();
        $conversation->setChatId('123456');
        $conversation->setBotId('test_bot_id');

        // Настройка ожидаемого поведения хранилища разговоров
        $this->conversationStorage->shouldReceive('getByChatId')
            ->with('123456', 'test_bot_id')
            ->once()
            ->andReturn($conversation);

        $this->conversationStorage->shouldReceive('save')
            ->with($conversation)
            ->once();

        // Настройка команд
        $this->commandBuilder->shouldReceive('getCommands')
            ->twice()
            ->andReturn(['/start' => $this->commandHandler]);

        // Ожидаемый вызов обработчика команды
        $this->commandHandler->shouldReceive('handle')
            ->with($message, $conversation)
            ->once();

        // Выполнение обработки
        $this->handler->handle($update);

        // Добавляем утверждение
        $this->assertTrue(true, 'Обработка текстовой команды выполнена успешно');
    }

    public function testHandleCallbackQuery(): void
    {
        // Настройка моков для callback query
        $chat = Mockery::mock(Chat::class);
        $chat->shouldReceive('getId')->andReturn(123456);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getChat')->andReturn($chat);

        $callbackQuery = Mockery::mock(CallbackQuery::class);
        $callbackQuery->shouldReceive('getData')->andReturn('callback_id_123');
        $callbackQuery->shouldReceive('getMessage')->andReturn($message);

        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getCallbackQuery')->andReturn($callbackQuery);
        $update->shouldReceive('getMessage')->andReturn(null);

        $conversation = new Conversation();
        $conversation->setChatId('123456');
        $conversation->setBotId('test_bot_id');

        // Настройка данных callback
        $callbackData = new CallbackData(
            'callback_id_123',
            'message_123',
            'action_button',
            ['param1' => 'value1'],
            '123456',
        );

        // Настройка хранилища callback данных
        $this->callbackDataStorage->shouldReceive('getById')
            ->with('callback_id_123')
            ->once()
            ->andReturn($callbackData);

        // Настройка хранилища разговоров
        $this->conversationStorage->shouldReceive('getByChatId')
            ->with('123456', 'test_bot_id')
            ->once()
            ->andReturn($conversation);

        $this->conversationStorage->shouldReceive('save')
            ->with($conversation)
            ->once();

        // Настройка inline команд
        $this->commandBuilder->shouldReceive('getInlineCommands')
            ->once()
            ->andReturn(['action_button' => $this->inlineCommandHandler]);

        // Ожидаемый вызов обработчика inline команды
        $this->inlineCommandHandler->shouldReceive('handle')
            ->with($message, $conversation, ['param1' => 'value1'])
            ->once();

        // Выполнение обработки
        $this->handler->handle($update);

        // Добавляем утверждение
        $this->assertTrue(true, 'Обработка callback query выполнена успешно');
    }

    public function testHandleCommandWithConversation(): void
    {
        // Настройка моков
        $chat = Mockery::mock(Chat::class);
        $chat->shouldReceive('getId')->andReturn(123456);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getChat')->andReturn($chat);
        $message->shouldReceive('getText')->andReturn('Some user input');
        $message->shouldReceive('setText')->with('Some user input');
        $message->shouldReceive('getContact')->andReturn(null);

        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage')->andReturn($message);
        $update->shouldReceive('getCallbackQuery')->andReturn(null);

        // Создаем разговор с активной командой
        $conversation = new Conversation();
        $conversation->setChatId('123456');
        $conversation->setBotId('test_bot_id');

        $context = new Context('register');
        $conversation->setContext($context);

        // Настройка ожидаемого поведения хранилища разговоров
        $this->conversationStorage->shouldReceive('getByChatId')
            ->with('123456', 'test_bot_id')
            ->once()
            ->andReturn($conversation);

        $this->conversationStorage->shouldReceive('save')
            ->with($conversation)
            ->once();

        // Настройка команд
        $this->commandBuilder->shouldReceive('getCommands')
            ->once()
            ->andReturn(['register' => $this->commandHandler]);

        // Ожидаемый вызов обработчика команды
        $this->commandHandler->shouldReceive('handle')
            ->with($message, $conversation)
            ->once();

        // Выполнение обработки
        $this->handler->handle($update);

        // Добавляем утверждение
        $this->assertTrue(true, 'Обработка команды с разговором выполнена успешно');
    }

    public function testCommandMissing(): void
    {
        // Настройка моков
        $chat = Mockery::mock(Chat::class);
        $chat->shouldReceive('getId')->andReturn(123456);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getChat')->andReturn($chat);
        $message->shouldReceive('getText')->andReturn('/unknown');
        $message->shouldReceive('setText')->with('/unknown');
        $message->shouldReceive('getContact')->andReturn(null);

        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage')->andReturn($message);
        $update->shouldReceive('getCallbackQuery')->andReturn(null);

        $conversation = new Conversation();
        $conversation->setChatId('123456');
        $conversation->setBotId('test_bot_id');

        $context = new Context('some_command');
        $conversation->setContext($context);

        // Настройка ожидаемого поведения хранилища разговоров
        $this->conversationStorage->shouldReceive('getByChatId')
            ->with('123456', 'test_bot_id')
            ->once()
            ->andReturn($conversation);

        $this->conversationStorage->shouldReceive('save')
            ->with($conversation)
            ->once();

        // Настройка команд - пустой список
        $this->commandBuilder->shouldReceive('getCommands')
            ->once()
            ->andReturn([]);

        // Выполнение обработки
        $this->handler->handle($update);

        // Проверяем, что метаданные разговора сброшены
        $this->assertNull($conversation->getContext());
    }

    protected function setUp(): void
    {
        $this->api = Mockery::mock(BotApi::class);
        $this->conversationStorage = Mockery::mock(ConversationStorageInterface::class);
        $this->callbackDataStorage = Mockery::mock(CallbackDataStorageInterface::class);
        $callbackDataFactory = Mockery::mock(CallbackDataFactory::class);
        $this->commandHandler = Mockery::mock(CommandHandlerInterface::class);
        $this->inlineCommandHandler = Mockery::mock(InlineCommandHandler::class);

        $this->handler = new TelegramConversationHandler(
            $this->conversationStorage,
            $this->callbackDataStorage,
            $this->api,
            $callbackDataFactory,
            'test_bot_id',
        );

        // Мокаем CommandBuilder
        $this->commandBuilder = Mockery::mock(CommandBuilder::class);

        // Устанавливаем моканный CommandBuilder
        $reflection = new \ReflectionClass($this->handler);
        $property = $reflection->getProperty('commandBuilder');
        $property->setAccessible(true);
        $property->setValue($this->handler, $this->commandBuilder);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
