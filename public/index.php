<?php

declare(strict_types=1);

use TiMacDonald\Website\Cache;
use TiMacDonald\Website\E;
use TiMacDonald\Website\ErrorHandling;
use TiMacDonald\Website\HttpException;
use TiMacDonald\Website\Markdown;
use TiMacDonald\Website\Page as Render;
use TiMacDonald\Website\Request;
use TiMacDonald\Website\Response;
use TiMacDonald\Website\Url;

$basePath = __DIR__.'/..';

require_once "{$basePath}/vendor/autoload.php";

ErrorHandling::bootstrap($basePath);

// TODO: strip "index.html" from the path for when the cached file does not yet exist.

$request = new Request(
    base: 'https://tim.macdonald.au',
    path: '/'.trim($_SERVER['PATH_INFO'] ?? '', '/'),
);

$render = new Render($basePath, static fn () => [
    'basePath' => $basePath,
    'request' => $request,
    'url' => new Url(
        base: $request->base,
        assetVersion: '1',
    ),
    'e' => new E,
    'markdown' => new Markdown,
]);

/*
 * Routing...
 */

$handler = static fn (): Response => match ($request->path) {
    '/' => $render('home.php'),
    '/about' => $render('about.php'),
    default => (static function () use ($render, $request): Response {
        foreach (['posts', 'talk'] as $type) {
            if (preg_match("/^\/{$type}\/([0-9a-z\-]+)$/", $request->path, $matches) === 1) {
                return $render("{$type}/{$matches[1]}.md");
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
        $cache = new Cache($basePath);

        $response = $cache($request->path, $response);
    }
} catch (HttpException $e) {
    $response = $render('error.php', [
        'message' => $e->getMessage(),
    ])->withStatus($e->status);
}

// TODO what to do with HEAD requests?
// additional headers?
$content = $response->render();
http_response_code($response->status);
echo $content;
