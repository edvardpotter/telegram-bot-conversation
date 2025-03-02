<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Unit\CommandHandlers;

use Edvardpotter\TelegramBotConversation\CommandHandlers\PatternCommandHandler;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

class PatternCommandHandlerTest extends TestCase
{
    private BotApi|MockInterface $api;
    private Conversation|MockInterface $conversation;
    private Message|MockInterface $message;

    public function testTemplatePatternWithSingleParameter(): void
    {
        $handlerCalled = false;
        $capturedName = null;

        $handler = function (
            Message $message,
            Conversation $conversation,
            BotApi $api,
            $name,
        ) use (
            &$handlerCalled,
            &$capturedName,
        ): void {
            $handlerCalled = true;
            $capturedName = $name;
        };

        $commandHandler = new PatternCommandHandler(
            $this->api,
            $handler,
            'call me {name}',
            [],
        );

        $this->message->shouldReceive('getText')
            ->once()
            ->andReturn('call me John');

        $commandHandler->handle($this->message, $this->conversation);

        $this->assertTrue($handlerCalled, 'Handler should be called');
        $this->assertEquals('John', $capturedName);
    }

    public function testTemplatePatternWithMultipleParameters(): void
    {
        $handlerCalled = false;
        $capturedName = null;
        $capturedAdjective = null;

        $handler = function (
            Message $message,
            Conversation $conversation,
            BotApi $api,
            $name,
            $adjective,
        ) use (
            &$handlerCalled,
            &$capturedName,
            &$capturedAdjective,
        ): void {
            $handlerCalled = true;
            $capturedName = $name;
            $capturedAdjective = $adjective;
        };

        $commandHandler = new PatternCommandHandler(
            $this->api,
            $handler,
            'call me {name} the {adjective}',
            [],
        );

        $this->message->shouldReceive('getText')
            ->once()
            ->andReturn('call me John the Great');

        $commandHandler->handle($this->message, $this->conversation);

        $this->assertTrue($handlerCalled, 'Handler should be called');
        $this->assertEquals('John', $capturedName);
        $this->assertEquals('Great', $capturedAdjective);
    }

    public function testRegexPatternWithSingleParameter(): void
    {
        $handlerCalled = false;
        $capturedNumber = null;

        $handler = function (
            Message $message,
            Conversation $conversation,
            BotApi $api,
            $number,
        ) use (
            &$handlerCalled,
            &$capturedNumber,
        ): void {
            $handlerCalled = true;
            $capturedNumber = $number;
        };

        $commandHandler = new PatternCommandHandler(
            $this->api,
            $handler,
            'I want ([0-9]+)',
            ['number'],
        );

        $this->message->shouldReceive('getText')
            ->once()
            ->andReturn('I want 42');

        $commandHandler->handle($this->message, $this->conversation);

        $this->assertTrue($handlerCalled, 'Handler should be called');
        $this->assertEquals('42', $capturedNumber);
    }

    public function testRegexPatternWithMultipleParameters(): void
    {
        $handlerCalled = false;
        $capturedAmount = null;
        $capturedDish = null;

        $handler = function (
            Message $message,
            Conversation $conversation,
            BotApi $api,
            $amount,
            $dish,
        ) use (
            &$handlerCalled,
            &$capturedAmount,
            &$capturedDish
        ): void {
            $handlerCalled = true;
            $capturedAmount = $amount;
            $capturedDish = $dish;
        };

        $commandHandler = new PatternCommandHandler(
            $this->api,
            $handler,
            'I want ([0-9]+) portions of (Cheese|Cake)',
            ['amount', 'dish'],
        );

        $this->message->shouldReceive('getText')
            ->once()
            ->andReturn('I want 5 portions of Cake');

        $commandHandler->handle($this->message, $this->conversation);

        $this->assertTrue($handlerCalled, 'Handler should be called');
        $this->assertEquals('5', $capturedAmount);
        $this->assertEquals('Cake', $capturedDish);
    }

    public function testRegexPatternWithoutNamedParameters(): void
    {
        $handlerCalled = false;
        $capturedNumber = null;
        $capturedAge = null;

        $handler = function (
            Message $message,
            Conversation $conversation,
            BotApi $api,
            $number,
            $age,
        ) use (
            &$handlerCalled,
            &$capturedNumber,
            &$capturedAge,
        ): void {
            $handlerCalled = true;
            $capturedNumber = $number;
            $capturedAge = $age;
        };

        $commandHandler = new PatternCommandHandler(
            $this->api,
            $handler,
            'I want ([0-9]+) and my age is ([0-9]+)',
            [],
        );

        $this->message->shouldReceive('getText')
            ->once()
            ->andReturn('I want 42 and my age is 30');

        $commandHandler->handle($this->message, $this->conversation);

        $this->assertTrue($handlerCalled, 'Handler should be called');
        $this->assertEquals('42', $capturedNumber);
        $this->assertEquals('30', $capturedAge);
    }

    public function testNoMatchShouldNotCallHandler(): void
    {
        $handlerCalled = false;

        $handler = function () use (&$handlerCalled): void {
            $handlerCalled = true;
        };

        $commandHandler = new PatternCommandHandler(
            $this->api,
            $handler,
            'call me {name}',
            [],
        );

        $this->message->shouldReceive('getText')
            ->once()
            ->andReturn('something completely different');

        $commandHandler->handle($this->message, $this->conversation);

        $this->assertFalse($handlerCalled, 'Handler should not be called when pattern does not match');
    }

    protected function setUp(): void
    {
        $this->api = Mockery::mock(BotApi::class);
        $this->conversation = Mockery::mock(Conversation::class);
        $this->message = Mockery::mock(Message::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
