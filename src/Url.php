<?php

namespace TiMacDonald\Website;

use RuntimeException;

readonly class Url
{
    private string $base;

    public function __construct(
        string $base,
        private string $assetVersion,
        private string $projectBase,
    ) {
        $this->base = rtrim($base, '/');
    }

    public function to(string $path): string
    {
        $path = ltrim($path, '/');

        return $this->base.($path === '' ? '' : '/'.$path);
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
