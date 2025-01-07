<?php

namespace TiMacDonald\Website;

use Closure;
use Throwable;

class Page
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
            extract([
                ...$__data,
                ...call_user_func($this->data),
            ]);

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
                    'page' => require "{$this->basePath}/content/templates/page.php",
                    'post' => require "{$this->basePath}/content/templates/post.php",
                };
                $content = ob_get_clean() ?: '';
            } catch (Throwable $e) {
                // ob_end_clean();

                throw $e;
            }

            return $content;
        });
    }
}

