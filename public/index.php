<?php

declare(strict_types=1);

use TiMacDonald\Website\CachedCollection;
use TiMacDonald\Website\CachedResponse;
use TiMacDonald\Website\Capture;
use TiMacDonald\Website\Collection;
use TiMacDonald\Website\E;
use TiMacDonald\Website\ErrorHandling;
use TiMacDonald\Website\HttpException;
use TiMacDonald\Website\Markdown;
use TiMacDonald\Website\Renderer;
use TiMacDonald\Website\Request;
use TiMacDonald\Website\Response;
use TiMacDonald\Website\Template;
use TiMacDonald\Website\Url;

/*
 * Helpers...
 */

function dd(mixed ...$args): never
{
    ob_end_clean();
    header('content-type: text/plain');
    var_dump(...$args);

    exit;
}

/*
 * Bootstrap...
 */

$projectBase = realpath(__DIR__.'/../');

assert(is_string($projectBase));

require_once "{$projectBase}/vendor/autoload.php";

ErrorHandling::bootstrap($projectBase);

$production = ! getenv('LOCAL');

/*
 * Capture request...
 */

$path = $_SERVER['REQUEST_URI'] ?? '';

$path = explode('?', $path, 2)[0] ?? $path;

if (str_ends_with($path, '/index.html')) {
    $path = substr($path, 0, -10);
}

$path = '/'.trim($path, '/');

$request = new Request(
    base: $production ? 'https://tim.macdonald.au' : 'http://'.$_SERVER['HTTP_HOST'],
    path: $path,
);

/*
 * Create services...
 */

$capture = new Capture;

$url = new Url(
    base: $request->base,
    projectBase: $projectBase,
);

$template = new Template($projectBase, $props = static fn () => [
    'theme' => '#5f40f6',
    'projectBase' => $projectBase,
    'request' => $request,
    'url' => $url,
    'e' => new E,
    'markdown' => new Markdown,
    'capture' => $capture,
]);

$collection = new Collection($projectBase, $capture, $props = static fn () => [
    ...$props(),
    'template' => $template,
]);

if ($production) {
    $collection = new CachedCollection($projectBase, $collection);
}

$render = new Renderer($projectBase, $capture, static fn () => [
    ...$props(),
    'collection' => $collection,
]);

$redirect = static fn (string $location) => new Response(
    callback: fn () => '',
    status: 307,
    headers: [
        'Location' => $location,
    ],
);

/*
 * Generate request handler...
 */

$handler = static fn (): Response => match ($request->path) {
    /*
     * Static routes...
     */
    '/' => $render('home.php'),
    '/about' => $redirect($url->to('/')),

    /*
     * Dynamic routes...
     */
    default => (static function () use ($render, $request, $collection, $url): Response {
        foreach ($collection('posts') as $post) {
            if ($url->page($post) === $request->url()) {
                return $render($post->file);
            }
        }

        throw HttpException::notFound();
    })(),
};

/*
 * Resolve the response...
 */

try {
    $response = $handler();

    /*
     * Only allow GET or HEAD requests for known routes...
     */
    if (! in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], strict: true)) {
        throw HttpException::methodNotAllowed();
    }

    if ($production && $response->status() < 300) {
        /*
         * Cache known routes...
         */
        $response = new CachedResponse($projectBase, $request, $response);
    }
} catch (HttpException $e) {
    /*
     * Transform Http exceptions into nice responses...
     */
    $response = $render('error.php', [
        'message' => $e->getMessage(),
    ], $e->status);
}

/*
 * Resolve the response...
 */

$body = $response->render();

$status = $response->status();

$headers = [
    'Content-Length' => strlen($body),
    ...$response->headers(),
];

/*
 * Send the response...
 */

http_response_code($status);

foreach ($headers as $key => $value) {
    header("{$key}: {$value}");
}

if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    echo $body;
}
