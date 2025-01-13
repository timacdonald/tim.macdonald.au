<?php

namespace TiMacDonald\Website;

use Closure;
use RuntimeException;

class Collection
{
    public function __construct(
        private string $projectBase,
        private Closure $props,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $props
     * @return list<object>
     */
    public function __invoke(string $name, array $props = []): array
    {
        $__props = $props;

        $paths = glob("{$this->projectBase}/resources/views/{$name}/*");

        if ($paths === false) {
            throw new RuntimeException("Unable to glob for collection [{$name}].");
        }

        return array_map(function (string $path) use ($__props): object {
            $__props = [
                ...$__props,
                ...call_user_func($this->props),
                'collection' => new Collection($this->projectBase, $this->props),
            ];

            extract($__props);

            if (! ob_start()) {
                throw new RuntimeException('Unable to start output buffering.');
            }

            require $path;
            ob_end_clean();

            if (! isset($page) || ! is_object($page)) {
                throw new RuntimeException("Did not find page object in [{$path}].");
            }

            // TODO ignore hidden
            return $page;
        }, $paths);
    }
}
