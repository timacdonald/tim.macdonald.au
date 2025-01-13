<?php

declare(strict_types=1);

use TiMacDonald\Website\Cache;
use TiMacDonald\Website\Collection;
use TiMacDonald\Website\E;
use TiMacDonald\Website\ErrorHandling;
use TiMacDonald\Website\HttpException;
use TiMacDonald\Website\Markdown;
use TiMacDonald\Website\Renderer;
use TiMacDonald\Website\Request;
use TiMacDonald\Website\Response;
use TiMacDonald\Website\Url;

/*
 * Helpers...
 */

function dd(mixed ...$args): never
{
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
 *
 * TODO: strip "index.html" from the path for when the cached file does not yet exist.
 */

$request = new Request(
    base: $production ? 'https://tim.macdonald.au' : 'http://'.$_SERVER['HTTP_HOST'],
    path: '/'.trim($_SERVER['REQUEST_URI'] ?? '', '/'),
);

/**
 * Create collection helper...
 */
$collection = new Collection($projectBase, $props = static fn () => [
    'projectBase' => $projectBase,
    'request' => $request,
    'url' => new Url(
        base: $request->base,
        projectBase: $projectBase,
    ),
    'e' => new E,
    'markdown' => new Markdown,
]);

/*
 * Create renderer...
 */

$render = new Renderer($projectBase, static fn () => [
    ...$props(),
    'collection' => $collection,
]);

/*
 * Generate request handler...
 */

$handler = static fn (): Response => match ($request->path) {
    /*
     * Static routes...
     */
    '/' => $render('home.php'),
    '/about' => $render('about.php'),

    /*
     * Dynamic routes...
     */
    default => (static function () use ($render, $request): Response {
        foreach (['posts', 'talk'] as $type) {
            if (preg_match("/^\/{$type}\/([0-9a-z\-]+)$/", $request->path, $matches) === 1) {
                return $render("{$type}/{$matches[1]}.md");
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

    if ($response->status === 200) {
        /*
         * Only allow GET or HEAD requests for known routes...
         */
        if (! in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], strict: true)) {
            throw HttpException::methodNotAllowed();
        }

        /*
         * Cache known routes...
         */
        $cache = new Cache($projectBase, $production);

        $response = $cache($request->path, $response);
    }
} catch (HttpException $e) {
    /*
     * Transform Http exceptions into nice responses...
     */
    $response = $render('error.php', [
        'message' => $e->getMessage(),
    ])->withStatus($e->status);
}

/*
 * Send the response...
 *
 * TODO what to do with HEAD requests?
 * TODO additional headers?
 */

$body = $response->render();

http_response_code($response->status);

echo $body;
