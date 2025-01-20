<?php

namespace TiMacDonald\Website\Contracts;

use TiMacDonald\Website\Page;

interface Collection
{
    /**
     * @param  array<string, mixed>  $props
     * @return list<Page>
     */
    public function __invoke(string $name, array $props = []): array;
}
