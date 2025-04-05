<?php

namespace TiMacDonald\Website\Contracts;

use TiMacDonald\Website\Page;

interface Collection
{
    /**
     * @param  array<string, mixed>  $props
     * @return list<Page>
     */
    public function all(string $name, array $props = []): array;

    /**
     * @param  array<string, mixed>  $props
     * @return list<Page>
     */
    public function published(string $name, array $props = []): array;
}
