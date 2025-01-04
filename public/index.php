<?php

declare(strict_types=1);

use TiMacDonald\Website\HttpException;
use TiMacDonald\Website\Request;
use TiMacDonald\Website\Response;
use TiMacDonald\Website\Url;

$basePath = __DIR__.'/..';

require_once "{$basePath}/vendor/autoload.php";

error_reporting(-1);

ini_set('display_errors', 'Off');

set_error_handler(static function (int $level, string $message, string $file = '', int $line = 0): void {
    throw new ErrorException($message, 0, $level, $file, $line);
});

$handler = static function (string $type, string $message, string $file, int $line, string $trace) use ($basePath) {
    header('content-type: text/plain');

    $trace = $trace ? '[trace] '.PHP_EOL.$trace : '';

    $output = trim(<<<EOF
    {$type}

    [message] 
    {$message}

    [file]
    {$file}:{$line}

    {$trace}
    EOF);

    echo $output;

    file_put_contents("{$basePath}/error.log", '['.date('Y-m-d H:i:s').'] '.$output.PHP_EOL.PHP_EOL, flags: FILE_APPEND);
};

set_exception_handler(static function (Throwable $e) use ($handler): void {
    $handler(
        type: $e::class,
        message: $e->getMessage(),
        file: $e->getFile(),
        line: $e->getLine(),
        trace: $e->getTraceAsString(),
    );
});

register_shutdown_function(static function () use ($handler): void {
    $error = error_get_last();

    if ($error !== null && in_array($error['type'], [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE], strict: true)) {
        $handler(
            type: 'FatalError',
            message: $error['message'],
            file: $error['file'],
            line: $error['line'],
            trace: '',
        );
    }
});

$request = new Request(
    base: 'https://tim.macdonald.au',
    path: '/'.trim($_SERVER['PATH_INFO'] ?? '', '/'),
);

$local = (bool) getenv('LOCAL');

$cache = static function (string $key, Response $response) use ($basePath, $local): Response {
    return $response->decorate(static function ($response) use ($basePath, $local, $key): string {
        $key = preg_replace('/[^0-9a-z\-]/', '-', $key);

        $path = "{$basePath}/cache/{$key}";

        $content = false;

        if (is_file($path)) {
            $content = file_get_contents($path);
        }

        if ($content === false || $local) {
            file_put_contents($path, $content = $response->render());
        }

        return $content;
    });
};

$page = static function (string $path, array $data = []) use ($basePath, $request): Response {
    $__path = "{$basePath}/content/{$path}";

    if (! is_file($__path)) {
        throw HttpException::notFound();
    }

    $__data = $data;

    return new Response(static function () use ($basePath, $request, $__path, $__data): string {
        $url = new Url(
            base: $request->base,
            assetVersion: '1',
        );

        $markdown = static function (string $content): string {
            /**
             * There is currently a deprecation error in the Markdown Parser.
             * This line fixes the issue by casting `null` to a string. Could
             * probably move this to a deployment script.
             *
             * @see https://github.com/michelf/php-markdown/pull/365
             */
            system('sed -i "232s/, \$attr, /, (string) \$attr, /g" ../vendor/michelf/php-markdown/Michelf/MarkdownExtra.php');

            $parser = new \Michelf\MarkdownExtra;
            $parser->code_class_prefix = 'language-';
            $parser->header_id_func = static function (string $heading): string {
                $slug = mb_strtolower($heading);

                $slug = preg_replace('/[^a-z0-9]/', '-', $slug);

                return $slug;
            };

            return $parser->transform($content);
        };

        $e = static function (string $value): void {
            echo htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        extract($__data);

        try {
            ob_start();
            require $__path;
            $content = ob_get_clean() ?: '';
        } catch (Throwable $e) {
            // ob_end_clean();

            throw $e;
        }

        if (! isset($page)) {
            return $content;
        }

        try {
            ob_start();
            match ($page->template) {
                'page' => require "{$basePath}/content/templates/page.php",
                'post' => require "{$basePath}/content/templates/post.php",
            };
            $content = ob_get_clean() ?: '';
        } catch (Throwable $e) {
            // ob_end_clean();

            throw $e;
        }

        return $content;
    });
};

/*
 * Routing...
 */

$handler = static fn () => match ($request->path) {
    '/' => $page('home.php'),
    '/about' => $page('about.php'),
    default => (static function () use ($page, $request): Response {
        foreach (['posts', 'talk'] as $type) {
            if (preg_match("/^\/{$type}\/([0-9a-z\-]+)$/", $request->path, $matches) === 1) {
                return $page("{$type}/{$matches[1]}.md");
            }
        }

        throw HttpException::notFound();
    })(),
};

try {
    $response = $handler();

    if ($response->status === 200 && ! in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], strict: true)) {
        throw HttpException::methodNotAllowed();
    }

    if ($response->status === 200) {
        $response = $cache($request->url(), $response);
    }
} catch (HttpException $e) {
    $response = $page('error.php', [
        'message' => $e->getMessage(),
    ])->withStatus($e->status);
}

// TODO what to do with HEAD requests?
// additional headers?
$content = $response->render();
http_response_code($response->status);
echo $content;
