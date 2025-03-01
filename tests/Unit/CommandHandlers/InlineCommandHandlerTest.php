<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Unit\CommandHandlers;

use Edvardpotter\TelegramBotConversation\CommandHandlers\InlineCommandHandler;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

class InlineCommandHandlerTest extends TestCase
{
    private InlineCommandHandler $handler;
    private BotApi|MockInterface $api;
    private bool $closureCalled = false;

    public function testHandle(): void
    {
        $message = Mockery::mock(Message::class);
        $conversation = Mockery::mock(Conversation::class);
        $parameters = ['param1' => 'value1'];

        $this->handler->handle($message, $conversation, $parameters);

        $this->assertTrue($this->closureCalled, 'Handler closure was not called');
    }

    public function testGetName(): void
    {
        $this->assertEquals('test_command', $this->handler->getName());
    }

    public function testSetName(): void
    {
        $this->handler->setName('new_name');
        $this->assertEquals('new_name', $this->handler->getName());
    }

    public function testIsConversational(): void
    {
        $this->assertFalse($this->handler->isConversational());
    }

    protected function setUp(): void
    {
        $this->api = Mockery::mock(BotApi::class);

        $this->closureCalled = false;
        $handlerClosure = function ($message, $conversation, $parameters, $api): void {
            $this->closureCalled = true;
            $this->assertInstanceOf(Message::class, $message);
            $this->assertInstanceOf(Conversation::class, $conversation);
            $this->assertIsArray($parameters);
            $this->assertSame($this->api, $api);
        };

        $this->handler = new InlineCommandHandler(
            $this->api,
            $handlerClosure,
        );

        $this->handler->setName('test_command');
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
