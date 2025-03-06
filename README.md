# Telegram Conversation Library

[![Latest Stable Version](https://poser.pugx.org/edvardpotter/telegram-bot-conversation/v?style=for-the-badge)](https://packagist.org/packages/edvardpotter/telegram-bot-conversation) [![Total Downloads](https://poser.pugx.org/edvardpotter/telegram-bot-conversation/downloads?style=for-the-badge)](https://packagist.org/packages/edvardpotter/telegram-bot-conversation) [![Latest Unstable Version](https://poser.pugx.org/edvardpotter/telegram-bot-conversation/v/unstable?style=for-the-badge)](https://packagist.org/packages/edvardpotter/telegram-bot-conversation) [![License](https://poser.pugx.org/edvardpotter/telegram-bot-conversation/license?style=for-the-badge)](https://packagist.org/packages/edvardpotter/telegram-bot-conversation) [![PHP Version Require](https://poser.pugx.org/edvardpotter/telegram-bot-conversation/require/php?style=for-the-badge)](https://packagist.org/packages/edvardpotter/telegram-bot-conversation)

A library for creating Telegram bots with support for declarative command descriptions and conversations (dialogue states).

## Features

- Declarative description of commands and their handlers
- State system for creating multi-step dialogues
- Support for regular commands and inline buttons
- Support for patterns and regular expressions in commands
- Simple and intuitive API

## Installation

```bash
composer require edvardpotter/telegram-bot-conversation
```

## Usage

### Simple Example

```php
<?php

use Edvardpotter\TelegramBotConversation\TelegramConversationHandler;
use TelegramBot\Api\BotApi;

// Create a Bot API
$botApi = new BotApi('YOUR_BOT_TOKEN');

// Create a conversation handler
$handler = new TelegramConversationHandler(
    $conversationStorage, // Your implementation of conversation storage
    $callbackDataStorage, // Your implementation of callback data storage
    $botApi
);

// Define commands in a declarative style
$handler->commands()
    // Simple /start command
    ->command('/start', function ($message, $conversation, $api) {
        $api->sendMessage(
            $message->getChat()->getId(),
            'Hello! I am a bot with a declarative command system!'
        );
    })
    
    // Command with dialogue /register
    ->conversation('/register', function ($message, $conversation, Context $context, $api) {
        $api->sendMessage(
            $message->getChat()->getId(),
            'Please enter your name:'
        );
        
        return 'wait_name'; // next step
    })
    ->step('wait_name', function ($message, $conversation, $context, $api) {
        $name = $message->getText();
        $context->setProperty('name', $name);
        
        $api->sendMessage(
            $message->getChat()->getId(),
            "Thank you, {$name}! Registration complete."
        );
        
        return 'finished'; // end conversation
    });

// Process webhook request
$update = Update::fromResponse(json_decode(file_get_contents('php://input'), true));
$handler->handle($update);
```

### Working with Inline Buttons

```php
<?php

use Edvardpotter\TelegramBotConversation\CallbackData\CallbackDataFactory;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

// Create a factory for callback data
$callbackDataFactory = new CallbackDataFactory($callbackDataStorage);

// Define command and inline handlers
$handler->commands()
    ->command('/menu', function ($message, $conversation, $api) use ($callbackDataFactory) {
        // Create callback data for buttons
        $option1CallbackId = $callbackDataFactory->create('menu_option1');
        $option2CallbackId = $callbackDataFactory->create('menu_option2');
        
        // Create keyboard
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => 'Option 1', 'callback_data' => $option1CallbackId],
                ['text' => 'Option 2', 'callback_data' => $option2CallbackId]
            ]
        ]);
        
        $api->sendMessage(
            $message->getChat()->getId(),
            'Select an option:',
            null, false, null, $keyboard
        );
    })
    
    // Handlers for inline buttons
    ->inline('menu_option1', function ($message, $conversation, $parameters, $api) {
        $api->sendMessage(
            $message->getChat()->getId(),
            'You selected Option 1!'
        );
    })
    ->inline('menu_option2', function ($message, $conversation, $parameters, $api) {
        $api->sendMessage(
            $message->getChat()->getId(),
            'You selected Option 2!'
        );
    });
```

### Using Patterns and Regular Expressions

The library supports two types of command patterns:

1. Templates with placeholders in `{name}` format
2. Regular expressions with capture groups `([0-9]+)`

Command parameters are passed as separate arguments to the handler function:

```php
<?php

// Command with a simple template
$handler->commands()->patternCommand('call me {name}', function (Message $message, Conversation $conversation, BotApi $api, string $name) {
    $api->sendMessage(
        $message->getChat()->getId(),
        "Hello, {$name}! Nice to meet you."
    );
});

// Command with multiple parameters in a template
$handler->commands()->patternCommand('call me {name} the {adjective}', function (Message $message, Conversation $conversation, BotApi $api, string $name, string $adjective) {
    $api->sendMessage(
        $message->getChat()->getId(),
        "Greetings, {$name} the {$adjective}! Your greatness knows no bounds!"
    );
});

// Command using regular expression with a named parameter
$handler->commands()->patternCommand('/I want ([0-9]+) apples/', function (Message $message, Conversation $conversation, BotApi $api, string $quantity) {
    $quantity = (int)$quantity;
    $api->sendMessage(
        $message->getChat()->getId(),
        "You want {$quantity} apples? That's a lot of fruit!"
    );
}, true);

// Command with named capture groups in a regular expression
$handler->commands()->patternCommand(
    '/I want (?<quantity>[0-9]+) and my age is (?<age>[0-9]+)/',
    function (Message $message, Conversation $conversation, BotApi $api, string $quantity, string $age) {
        $quantity = (int)$quantity;
        $age = (int)$age;
        $api->sendMessage(
            $message->getChat()->getId(),
            "You want {$quantity} items and you are {$age} years old."
        );
    },
    true
);
```

> **Note:** The order of parameters is important! System parameters always come first: `$message`, `$conversation`, `$api`, followed by parameters extracted from the message text.

## License

MIT 
