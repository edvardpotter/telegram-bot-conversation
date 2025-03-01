<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Unit\CallbackData;

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackData;
use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataFactory;
use Edvardpotter\TelegramBotConversation\Tests\Stubs\CallbackDataStorageStub;
use Mockery;
use PHPUnit\Framework\TestCase;
use TelegramBot\Api\Types\Message;

class CallbackDataFactoryTest extends TestCase
{
    private CallbackDataFactory $factory;
    private CallbackDataStorageStub $storage;

    public function testCreate(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getMessageId')->andReturn('123');
        $message->shouldReceive('getChat->getId')->andReturn('123456');

        $callbackDataId = $this->factory->create('action_name', ['param1' => 'value1'], $message);

        // Получаем созданные данные из хранилища
        $callbackData = $this->storage->getById($callbackDataId);

        $this->assertInstanceOf(CallbackData::class, $callbackData);
        $this->assertEquals('action_name', $callbackData->getName());
        $this->assertEquals(['param1' => 'value1'], $callbackData->getParameters());
        $this->assertEquals('123456', $callbackData->getChatId());
        $this->assertEquals('123', $callbackData->getMessageId());
    }

    public function testGetByMessageId(): void
    {
        $message1 = Mockery::mock(Message::class);
        $message1->shouldReceive('getMessageId')->andReturn('111');
        $message1->shouldReceive('getChat->getId')->andReturn('123456');

        $message2 = Mockery::mock(Message::class);
        $message2->shouldReceive('getMessageId')->andReturn('222');
        $message2->shouldReceive('getChat->getId')->andReturn('123456');

        $this->factory->create('action1', [], $message1);
        $this->factory->create('action2', [], $message1);
        $this->factory->create('action3', [], $message2);

        $result = $this->storage->getByMessageId('111');

        $this->assertCount(2, $result);
        $this->assertEquals('action1', $result[0]->getName());
        $this->assertEquals('action2', $result[1]->getName());
    }

    public function testClearByMessageId(): void
    {
        $message1 = Mockery::mock(Message::class);
        $message1->shouldReceive('getMessageId')->andReturn('111');
        $message1->shouldReceive('getChat->getId')->andReturn('123456');

        $message2 = Mockery::mock(Message::class);
        $message2->shouldReceive('getMessageId')->andReturn('222');
        $message2->shouldReceive('getChat->getId')->andReturn('123456');

        $this->factory->create('action1', [], $message1);
        $this->factory->create('action2', [], $message1);
        $this->factory->create('action3', [], $message2);

        $this->factory->clearByMessageId('111');

        $this->assertEmpty($this->storage->getByMessageId('111'));
        $this->assertCount(1, $this->storage->getByMessageId('222'));
    }

    protected function setUp(): void
    {
        $this->storage = new CallbackDataStorageStub();
        $this->factory = new CallbackDataFactory($this->storage);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
