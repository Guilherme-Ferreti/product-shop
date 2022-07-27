<?php

declare(strict_types=1);

use App\Config;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

function config(string $key): mixed
{
    return (new Config($_SERVER))->get($key);
}

/**
 * Dump given variables and exit the application.
 */
function dd(): never
{
    foreach (func_get_args() as $arg) {
        echo '<pre>';
        var_dump($arg);
        echo '</pre>';
    }

    exit;
}

/**
 * Return a new Logger instance.
 */
function logger(string $name = 'app'): Logger
{
    $stream = config('logs.path') . "$name.log";

    $logger = new Logger($name);

    $logger->pushHandler(new StreamHandler($stream));

    return $logger;
}
