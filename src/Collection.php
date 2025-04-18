<?php

namespace TiMacDonald\Website;

use Closure;
use RuntimeException;
use TiMacDonald\Website\Contracts\Collection as CollectionContract;

readonly class Collection implements CollectionContract
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
     * @return list<Page>
     */
    public function all(string $name, array $props = []): array
    {
        $paths = glob("{$this->projectBase}/resources/views/{$name}/*");

        if ($paths === false) {
            throw new RuntimeException("Unable to glob for collection [{$name}].");
        }

        $collection = array_map(function (string $__path) use ($props): Page {
            $props = [
                ...$props,
                ...call_user_func($this->props),
                'collection' => $this,
            ];

            [, $page] = call_user_func($this->capture, static function () use ($__path, $props): ?Page {
                extract($props);
                unset($props);

                require $__path;

                return $page ?? null;
            });

            if ($page === null) {
                throw new RuntimeException("Did not find Page in [{$__path}].");
            }

            return $page;
        }, $paths);

        usort($collection, static function (Page $a, Page $b): int {
            return $a->date < $b->date ? 1 : -1;
        });

        return $collection;
    }

    /**
     * @param  array<string, mixed>  $props
     * @return list<Page>
     */
    public function published(string $name, array $props = []): array
    {
        return array_values(
            array_filter($this->all($name, $props), fn (Page $page) => ! $page->hidden)
        );
    }
}
