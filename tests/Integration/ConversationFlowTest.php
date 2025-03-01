<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Integration;

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataFactory;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Edvardpotter\TelegramBotConversation\TelegramConversationHandler;
use Edvardpotter\TelegramBotConversation\Tests\Stubs\CallbackDataStorageStub;
use Edvardpotter\TelegramBotConversation\Tests\Stubs\ConversationStorageStub;
use Mockery;
use PHPUnit\Framework\TestCase;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Chat;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class ConversationFlowTest extends TestCase
{
    private TelegramConversationHandler $handler;
    private BotApi|Mockery\MockInterface $api;
    private ConversationStorageStub $conversationStorage;

    public function testCompleteRegistrationFlow(): void
    {
        // Ожидаемые сообщения от бота
        $this->api->shouldReceive('sendMessage')
            ->with('123456', 'Пожалуйста, введите ваше имя:')
            ->once();

        $this->api->shouldReceive('sendMessage')
            ->with('123456', 'Приятно познакомиться, Иван! Сколько вам лет?')
            ->once();

        $this->api->shouldReceive('sendMessage')
            ->with('123456', 'Спасибо! Регистрация завершена. Имя: Иван, Возраст: 30')
            ->once();

        // Шаг 1: Пользователь отправляет команду /register
        $update1 = $this->createUpdate('/register');
        $this->handler->handle($update1);

        // Проверяем, что разговор был создан и имеет правильное состояние
        $conversation = $this->conversationStorage->getByChatId('123456');
        $this->assertNotNull($conversation);
        $this->assertNotNull($conversation->getContext());
        $this->assertEquals('/register', $conversation->getContext()->getName());
        $this->assertEquals('name', $conversation->getContext()->getStep());

        // Шаг 2: Пользователь отправляет имя
        $update2 = $this->createUpdate('Иван');
        $this->handler->handle($update2);

        // Проверяем обновление состояния разговора
        $conversation = $this->conversationStorage->getByChatId('123456');
        $this->assertNotNull($conversation->getContext());
        $this->assertEquals('/register', $conversation->getContext()->getName());
        $this->assertEquals('age', $conversation->getContext()->getStep());
        $this->assertEquals('Иван', $conversation->getContext()->getProperty('name'));

        // Шаг 3: Пользователь отправляет возраст
        $update3 = $this->createUpdate('30');
        $this->handler->handle($update3);

        // Проверяем, что разговор завершен
        $conversation = $this->conversationStorage->getByChatId('123456');
        $this->assertNull($conversation->getContext());
    }

    protected function setUp(): void
    {
        $this->api = Mockery::mock(BotApi::class);
        $this->conversationStorage = new ConversationStorageStub();
        $callbackDataStorage = new CallbackDataStorageStub();
        $callbackDataFactory = new CallbackDataFactory($callbackDataStorage);

        $this->handler = new TelegramConversationHandler(
            $this->conversationStorage,
            $callbackDataStorage,
            $this->api,
            $callbackDataFactory,
            'test_bot',
        );

        // Настраиваем команды для теста
        $this->setupCommands();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function setupCommands(): void
    {
        $commands = $this->handler->commands();

        // Создаем команду регистрации
        $commands->conversation(
            '/register',
            function (Message $message, Conversation $conversation, $context, BotApi $api) {
                // Показываем приветствие и переходим к шагу получения имени
                $api->sendMessage($conversation->getChatId(), 'Пожалуйста, введите ваше имя:');
                return 'name';
            },
        )
        ->step('name', function (Message $message, Conversation $conversation, $context, BotApi $api) {
            // Сохраняем имя и переходим к шагу получения возраста
            $name = $message->getText();
            $context->setProperty('name', $name);

            $api->sendMessage($conversation->getChatId(), "Приятно познакомиться, {$name}! Сколько вам лет?");

            return 'age';
        })
        ->step('age', function (Message $message, Conversation $conversation, $context, BotApi $api) {
            // Сохраняем возраст и завершаем разговор
            $age = $message->getText();
            $name = $context->getProperty('name');

            $api->sendMessage(
                $conversation->getChatId(),
                "Спасибо! Регистрация завершена. Имя: {$name}, Возраст: {$age}",
            );

            // Возвращаем null для завершения разговора
            return null;
        });
    }

    private function createUpdate(string $text): Update
    {
        $chat = Mockery::mock(Chat::class);
        $chat->shouldReceive('getId')->andReturn(123456);

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getChat')->andReturn($chat);
        $message->shouldReceive('getText')->andReturn($text);
        $message->shouldReceive('setText')->with($text);
        $message->shouldReceive('getContact')->andReturn(null);

        $update = Mockery::mock(Update::class);
        $update->shouldReceive('getMessage')->andReturn($message);
        $update->shouldReceive('getCallbackQuery')->andReturn(null);

        return $update;
    }
}
