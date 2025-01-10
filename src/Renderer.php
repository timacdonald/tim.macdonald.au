<?php

namespace TiMacDonald\Website;

use Closure;
use RuntimeException;
use Throwable;

class Renderer
{
    public function __construct(
        private string $basePath,
        private Closure $data,
    ) {
        //
    }

    public function __invoke(string $path, array $data = []): Response
    {
        $__path = "{$this->basePath}/content/{$path}";

        if (! is_file($__path)) {
            throw HttpException::notFound();
        }

        $__data = $data;

        return new Response(function () use ($__path, $__data): string {
            $__data = [
                ...$__data,
                ...call_user_func($this->data),
            ];

            extract($__data);

            try {
                if (! ob_start()) {
                    throw new RuntimeException('Unable to start output buffering.');
                }

                require $__path;

                $content = ob_get_clean();

                if ($content === false) {
                    throw new RuntimeException('Unable to get the output buffer contents.');
                }
            } catch (Throwable $e) {
                ob_end_clean();

                throw $e;
            }

            if (! isset($page)) {
                return $content;
            }

            try {
                $started = ob_start();

                if (! $started) {
                    throw new RuntimeException('Unable to start output buffering.');
                }

                require "{$this->basePath}/content/templates/{$page->template}.php";

                $content = ob_get_clean();

                if ($content === false) {
                    throw new RuntimeException('Unable to get the output buffer contents.');
                }
            } catch (Throwable $e) {
                ob_end_clean();

                throw $e;
            }

            return $content;
        });
    }
}
