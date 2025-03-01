<?php

declare(strict_types=1);

require_once '../vendor/autoload.php';
require_once 'Storage/JsonCallbackDataStorage.php';
require_once 'Storage/JsonConversationStorage.php';

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataFactory;
use Edvardpotter\TelegramBotConversation\Conversation\Conversation;
use Edvardpotter\TelegramBotConversation\TelegramConversationHandler;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Message;

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Create file-based storages
$conversationStorage = new JsonConversationStorage($dataDir . '/conversations.json');
$callbackDataStorage = new JsonCallbackDataStorage($dataDir . '/callback_data.json');
// Create a factory for callback data
$callbackDataFactory = new CallbackDataFactory($callbackDataStorage);

// Create Bot API
$token = 'YOUR_BOT_TOKEN';
$botApi = new BotApi($token);

// Create conversation handler
$handler = new TelegramConversationHandler(
    $conversationStorage,
    $callbackDataStorage,
    $botApi,
    $callbackDataFactory,
);

// Define commands
$handler->commands()
    // Simple /start command
    ->command('/start', function (Message $message, Conversation $conversation, BotApi $api): void {
        $api->sendMessage(
            $message->getChat()->getId(),
            'Hello! Available commands: /help, /register, /profile',
        );
    })

    // Simple /help command with First class callable syntax
    ->command('/help', (new HelpHandler())(...))

    // Command with dialogue (states) /register
    ->conversation(
        '/register',
        function (Message $message, Conversation $conversation, $context, BotApi $api) {
            $api->sendMessage(
                $message->getChat()->getId(),
                'Please enter your name:',
            );

            return 'wait_name'; // next step
        },
    )
    ->step(
        'wait_name',
        function (Message $message, Conversation $conversation, $context, BotApi $api) {
            $name = $message->getText();
            $context->setProperty('name', $name);

            $api->sendMessage(
                $message->getChat()->getId(),
                "Thank you, {$name}! Now please enter your email:",
            );

            return 'wait_email';
        },
    )
    ->step(
        'wait_email',
        function (Message $message, Conversation $conversation, $context, BotApi $api) use ($callbackDataFactory) {
            $email = $message->getText();
            $name = $context->getProperty('name');
            $context->setProperty('email', $email);

            // Create a keyboard with confirmation buttons
            $confirmCallbackId = $callbackDataFactory->create(actionName: 'register_confirm', parameters: [
                'name' => $name,
                'email' => $email,
            ]);

            $cancelCallbackId = $callbackDataFactory->create('register_cancel');

            $keyboard = new InlineKeyboardMarkup(inlineKeyboard: [
            [
                ['text' => 'Confirm', 'callback_data' => $confirmCallbackId],
                ['text' => 'Cancel', 'callback_data' => $cancelCallbackId],
            ],
            ]);

            $api->sendMessage(
                $message->getChat()->getId(),
                "Please verify your information:\nName: {$name}\nEmail: {$email}",
                null,
                false,
                null,
                $keyboard,
            );

            return null;
        },
    )

    // Inline commands (handlers for callback query)
    ->inline(
        'register_confirm',
        function (Message $message, Conversation $conversation, $parameters, BotApi $api): void {
            $api->sendMessage(
                $message->getChat()->getId(),
                "Registration complete! Thank you, {$parameters['name']}!",
            );

        // End the conversation
            $conversation->setContext(null);
        },
    )
    ->inline(
        'register_cancel',
        function (Message $message, Conversation $conversation, $parameters, BotApi $api): void {
            $api->sendMessage(
                $message->getChat()->getId(),
                "Registration canceled. You can start again using the /register command",
            );

        // End the conversation
            $conversation->setContext(null);
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


class HelpHandler
{
    public function __invoke(Message $message, Conversation $conversation, BotApi $api): void
    {
        $api->sendMessage(
            $message->getChat()->getId(),
            "List of commands:\n" .
            "/start - Start working with the bot\n" .
            "/help - Show this help information\n" .
            "/register - Register a new user\n",
        );
    }
}
