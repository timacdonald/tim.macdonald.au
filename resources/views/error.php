<?php
/**
 * Props.
 *
 * @var string $message
 * @var (callable(string): void) $e
 */
?><!doctype html>
<html>
    <head lang="en">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php $e($message); ?></title>
    </head>
    <body>
        <div style="display: flex; height: 100vh; align-items: center; justify-content: center;">
            <div style="text-align: center;">
                <h1><?php $e($message); ?></h1>
                <a href="/">Home</a>
            </div>
        </div>
    </body>
</html>
