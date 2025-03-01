<?php

declare(strict_types=1);

namespace Edvardpotter\TelegramBotConversation\Tests\Unit;

use Edvardpotter\TelegramBotConversation\CommandBuilder;
use Edvardpotter\TelegramBotConversation\CommandHandlers\PatternCommandHandler;
use Mockery;
use PHPUnit\Framework\TestCase;
use TelegramBot\Api\BotApi;

class PatternCommandBuilderTest extends TestCase
{
    private BotApi $api;
    private CommandBuilder $commandBuilder;

    public function testPatternCommandWithTemplate(): void
    {
        $handler = function (): void {
        };

        $this->commandBuilder->patternCommand('call me {name}', $handler);

        $patternCommands = $this->commandBuilder->getPatternCommands();
        $this->assertCount(1, $patternCommands);
        $this->assertInstanceOf(PatternCommandHandler::class, $patternCommands[0]);
    }

    public function testPatternCommandWithRegex(): void
    {
        $handler = function (): void {
        };

        $this->commandBuilder->patternCommand('I want ([0-9]+)', $handler, 'number');

        $patternCommands = $this->commandBuilder->getPatternCommands();
        $this->assertCount(1, $patternCommands);
        $this->assertInstanceOf(PatternCommandHandler::class, $patternCommands[0]);
    }

    public function testMultiplePatternCommands(): void
    {
        $handler = function (): void {
        };

        $this->commandBuilder->patternCommand('call me {name}', $handler);
        $this->commandBuilder->patternCommand('I want ([0-9]+)', $handler, 'number');
        $this->commandBuilder->patternCommand('call me {name} the {adjective}', $handler);

        $patternCommands = $this->commandBuilder->getPatternCommands();
        $this->assertCount(3, $patternCommands);
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
}
