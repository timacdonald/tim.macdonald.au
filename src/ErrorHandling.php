<?php

namespace TiMacDonald\Website;

use ErrorException;
use Throwable;

class ErrorHandling
{
    public static function bootstrap(string $projectBase): void
    {
        error_reporting(-1);

        ini_set('display_errors', 'Off');

        set_error_handler(static function (int $level, string $message, string $file = '', int $line = 0): never {
            throw new ErrorException($message, 0, $level, $file, $line);
        });

        $logAndRender = static function (string $type, string $message, string $file, int $line, string $trace) use ($projectBase): void {
            $trace = $trace ? '[trace] '.PHP_EOL.$trace : '';

            $output = trim(<<<EOF
            {$type}

            [message]
            {$message}

            [file]
            {$file}:{$line}

            {$trace}
            EOF);

            file_put_contents("{$projectBase}/error.log", '['.date('Y-m-d H:i:s').'] '.$output.PHP_EOL.PHP_EOL, flags: FILE_APPEND);

            header('content-type: text/plain');
            echo $output;
        };

        set_exception_handler(static function (Throwable $e) use ($logAndRender): void {
            $logAndRender(
                type: $e::class,
                message: $e->getMessage(),
                file: $e->getFile(),
                line: $e->getLine(),
                trace: $e->getTraceAsString(),
            );
        });

        register_shutdown_function(static function () use ($logAndRender): void {
            $error = error_get_last();

            if ($error !== null && in_array($error['type'], [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE], strict: true)) {
                $logAndRender(
                    type: 'FatalError',
                    message: $error['message'],
                    file: $error['file'],
                    line: $error['line'],
                    trace: '',
                );
            }
        });
    }
}
