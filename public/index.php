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

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$path = $_SERVER['REQUEST_URI'] ?? '';
$base = $production
    ? 'https://tim.macdonald.au'
    : 'http://'.$_SERVER['HTTP_HOST'];

$path = explode('?', $path, 2)[0] ?? $path;

if (str_ends_with($path, '/index.html')) {
    $path = substr($path, 0, -10);
}

$request = new Request($base, $method, $path);

/*
 * Create services...
 */

$capture = new Capture;

$url = new Url(
    base: $request->base,
    projectBase: $projectBase,
);

$e = new E;

$template = new Template($projectBase, $props = static fn () => [
    'production' => $production,
    'theme' => '#5f40f6',
    'projectBase' => $projectBase,
    'request' => $request,
    'url' => $url,
    'e' => $e,
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
    callback: fn (): string => 'Redirecting to <a href="'.$e->escape($location).'">'.$e->escape($location).'</a>...',
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
    '/feed.xml' => $render('feed.xml.php', headers: [
        'content-type' => 'text/xml; charset=utf-8',
    ]),

    /*
     * Redirects...
     */
    '/wip' => $redirect($url->to('/')),
    '/about' => $redirect($url->to('/')),
    '/mark-all-files-unread-github' => $redirect($url->to('/mark-all-files-unviewed-github/')),

    /*
     * Dynamic routes...
     */
    default => (static function () use ($render, $request, $collection, $url): Response {
        foreach ($collection->all('posts') as $post) {
            if ($url->page($post) === $request->url()) {
                return $render($post->file);
            }
        }

        throw HttpException::notFound();
    })(),
};

/*
 * Determine the response...
 */

try {
    $response = $handler();

    /*
     * Only allow GET or HEAD requests for known routes...
     */
    if (! in_array($request->method, ['get', 'head'], strict: true)) {
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
 * Resolve the response values...
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

if ($request->method === 'get') {
    echo $body;
}
