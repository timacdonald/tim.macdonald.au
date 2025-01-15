<?php

namespace TiMacDonald\Website;

use RuntimeException;

readonly class Url
{
    private string $base;

    public function __construct(
        string $base,
        private string $projectBase,
    ) {
        $this->base = rtrim($base, '/');
    }

    public function to(string $path): string
    {
        $path = trim($path, '/');

        return $this->base.($path === '' ? '' : '/'.$path);
    }

    public function page(Page $page): string
    {
        $resourcesDirectory = $this->projectBase.'/resources/views';

        if (! str_starts_with($page->file, $resourcesDirectory)) {
            throw new RuntimeException('Page file must start with the resources directory.');
        }

        $path = substr($page->file, strlen($resourcesDirectory));

        ['dirname' => $directory, 'filename' => $file] = pathinfo($path);

        return $this->to("{$directory}/{$file}");
    }

    public function asset(string $path): string
    {
        $path = "/assets/{$path}";

        $file = $this->projectBase.'/public'.$path;

        if (! is_file($file)) {
            throw new RuntimeException("Unknown asset [{$path}].");
        }

        $hash = hash_file('xxh3', $file);

        if ($hash === false) {
            throw new RuntimeException("Unable to generate hash for asset [{$path}]");
        }

        return $this->to($path).'?v='.$hash;
    }
}
