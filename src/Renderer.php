<?php

namespace TiMacDonald\Website;

use Closure;

readonly class Renderer
{
    public function __construct(
        private string $projectBase,
        private Capture $capture,
        private Closure $props,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function __invoke(string $path, array $props = [], int $status = 200): Response
    {
        $__path = realpath($path);

        if ($__path === false) {
            $__path = realpath("{$this->projectBase}/resources/views/{$path}");

            if ($__path === false) {
                throw HttpException::notFound();
            }
        }

        return new Response(function () use ($__path, $props): string {
            $props = [
                ...$props,
                ...call_user_func($this->props),
            ];

            [$content, $page] = call_user_func($this->capture, static function () use ($__path, $props): ?Page {
                extract($props);
                unset($props);

                require $__path;

                return $page ?? null;
            });

            if ($page === null) {
                return $content;
            }

            $props = call_user_func($this->props);

            [$content] = call_user_func($this->capture, function () use ($props, $page, $content) {
                extract($props);
                unset($props);

                require "{$this->projectBase}/resources/views/templates/{$page->template}.php";
            });

            return $content;
        }, status: $status);
    }
}
