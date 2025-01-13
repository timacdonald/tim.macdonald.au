<?php

namespace TiMacDonald\Website;

use Closure;
use RuntimeException;
use Throwable;

class Renderer
{
    public function __construct(
        private string $projectBase,
        private Closure $props,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function __invoke(string $path, array $props = []): Response
    {
        $__path = "{$this->projectBase}/resources/views/{$path}";

        if (! is_file($__path)) {
            throw HttpException::notFound();
        }

        $__props = $props;

        return new Response(function () use ($__path, $__props): string {
            $__props = [
                ...$__props,
                ...call_user_func($this->props),
            ];

            extract($__props);

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
                if (! ob_start()) { // @phpstan-ignore booleanNot.alwaysFalse
                    throw new RuntimeException('Unable to start output buffering.');
                }

                require "{$this->projectBase}/resources/views/templates/{$page->template}.php";

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
