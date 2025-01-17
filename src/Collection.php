<?php

namespace TiMacDonald\Website;

use Closure;
use RuntimeException;

readonly class Collection
{
    public function __construct(
        private string $projectBase,
        private Closure $props,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $props
     * @return list<Page>
     */
    public function __invoke(string $name, array $props = []): array
    {
        $__props = $props;

        $paths = glob("{$this->projectBase}/resources/views/{$name}/*");

        if ($paths === false) {
            throw new RuntimeException("Unable to glob for collection [{$name}].");
        }

        $collection = array_map(function (string $path) use ($__props): ?Page {
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

            if (! isset($page) || ! ($page instanceof Page)) {
                throw new RuntimeException("Did not find Page in [{$path}].");
            }

            if ($page->hidden) {
                return null;
            }

            return $page;
        }, $paths);

        $collection = array_filter($collection);

        usort($collection, static function (Page $a, Page $b): int {
            return $a->date < $b->date ? 1 : -1;
        });

        return $collection;
    }
}
