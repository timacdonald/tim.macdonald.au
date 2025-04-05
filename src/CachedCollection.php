<?php

namespace TiMacDonald\Website;

use Throwable;
use TiMacDonald\Website\Contracts\Collection;

class CachedCollection
{
    public function __construct(
        private string $projectBase,
        private Collection $collection,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $props
     * @return list<Page>
     */
    public function all(string $name, array $props = []): array
    {
        return $this->retrieve(
            name: $name,
            props: $props,
            key: "{$name}-all",
            callback: $this->collection->all(...),
        );
    }

    /**
     * @param  array<string, mixed>  $props
     * @return list<Page>
     */
    public function published(string $name, array $props = []): array
    {
        return $this->retrieve(
            name: $name,
            props: $props,
            key: "{$name}-published",
            callback: $this->collection->published(...),
        );
    }

    /**
     * @param  array<string, mixed>  $props
     * @param  (callable(string, array<string, mixed>): list<Page>)  $callback
     * @return list<Page>
     */
    private function retrieve(string $name, array $props, string $key, callable $callback)
    {
        $cachePath = "{$this->projectBase}/cache/{$key}-collection.php";

        try {
            return require $cachePath;
        } catch (Throwable $e) {
            //
        }

        $collection = $callback($name, $props);

        file_put_contents($cachePath, "<?php return unserialize(<<<'PHP_EOF'\n".serialize($collection)."\nPHP_EOF);");

        return $collection;
    }
}
