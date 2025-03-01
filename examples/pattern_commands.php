<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'Storage/JsonCallbackDataStorage.php';
require_once 'Storage/JsonConversationStorage.php';

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataFactory;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Edvardpotter\TelegramBotConversation\TelegramConversationHandler;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$conversationStorage = new JsonConversationStorage($dataDir . '/conversations.json');
$callbackDataStorage = new JsonCallbackDataStorage($dataDir . '/callback_data.json');
$callbackDataFactory = new CallbackDataFactory($callbackDataStorage);

$token = 'YOUR_BOT_TOKEN';
$botApi = new BotApi($token);

$handler = new TelegramConversationHandler(
    $conversationStorage,
    $callbackDataStorage,
    $botApi,
    $callbackDataFactory,
);

// Register a regular command
$handler->commands()->command(
    'start',
    function (Message $message, Conversation $conversation, BotApi $botApi): void {
        $botApi->sendMessage(
            $message->getChat()->getId(),
            'Hello! I can work with command patterns. Try the following commands:' . PHP_EOL .
            '- call me John' . PHP_EOL .
            '- call me John the Great' . PHP_EOL .
            '- I want 5 portions of Cake' . PHP_EOL .
            '- I want 10 portions of Cheese' . PHP_EOL .
            '- I want 42 and my age is 30',
        );
    },
);

// Command with a simple template - parameters as named arguments
$handler->commands()->patternCommand(
    'call me {name}',
    function (Message $message, Conversation $conversation, BotApi $botApi, $name): void {
        $botApi->sendMessage(
            $message->getChat()->getId(),
            "Hello, {$name}! Nice to meet you.",
        );
    },
);

// Command with multiple parameters in a template - parameters as named arguments
$handler->commands()->patternCommand(
    'call me {name} the {adjective}',
    function (Message $message, Conversation $conversation, BotApi $botApi, $name, $adjective): void {
        $botApi->sendMessage(
            $message->getChat()->getId(),
            "Greetings, {$name} the {$adjective}! Your greatness knows no bounds!",
        );
    },
);

// Command using a regular expression - parameters as named arguments
$handler->commands()->patternCommand(
    'I want ([0-9]+)',
    function (Message $message, Conversation $conversation, BotApi $botApi, $number): void {
        $number = (int)$number;
        $botApi->sendMessage(
            $message->getChat()->getId(),
            "You ordered {$number} portions. Anything else?",
        );
    },
    'number',
);

// Command with multiple parameters in a regular expression - parameters as named arguments
$handler->commands()->patternCommand(
    'I want ([0-9]+) portions of (Cheese|Cake)',
    function (Message $message, Conversation $conversation, BotApi $botApi, $amount, $dish): void {
        $amount = (int)$amount;
        $botApi->sendMessage(
            $message->getChat()->getId(),
            "You ordered {$amount} portions of {$dish}. Excellent choice!",
        );
    },
    'amount',
    'dish',
);

// Regular expression with multiple parameters without specifying names - just in order
$handler->commands()->patternCommand(
    'I want ([0-9]+) and my age is ([0-9]+)',
    function (Message $message, Conversation $conversation, BotApi $botApi, $number, $age): void {
        $number = (int)$number;
        $age = (int)$age;
        $botApi->sendMessage(
            $message->getChat()->getId(),
            "You ordered {$number} portions. And you are {$age} years old. Anything else?",
        );
    },
);

while (true) {
    processUpdates($handler, $botApi);
    usleep(100);
}

// Webhook handler
/*$update = Update::fromResponse(json_decode(file_get_contents('php://input'), true));
$handler->handle($update);*/


function processUpdates(TelegramConversationHandler $handler, BotApi $botApi): void
{
    $updates = $botApi->getUpdates();

    $lastUpdateId = null;
    foreach ($updates as $update) {
        $lastUpdateId = $update->getUpdateId();
        $handler->handle($update);
    }

    if ($lastUpdateId) {
        $botApi->getUpdates($lastUpdateId + 1, 1);
    }
}
