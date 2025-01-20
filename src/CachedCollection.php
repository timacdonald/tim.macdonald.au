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
    public function __invoke(string $name, array $props = []): array
    {
        $cachePath = "{$this->projectBase}/cache/{$name}-collection.php";

        try {
            return require $cachePath;
        } catch (Throwable $e) {
            // TODO log?
        }

        $collection = call_user_func($this->collection, $name, $props);

        file_put_contents($cachePath, "<?php return unserialize(<<<'PHP_EOF'\n".serialize($collection)."\nPHP_EOF);");

        return $collection;
    }
}
