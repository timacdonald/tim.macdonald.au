<?php

namespace TiMacDonald\Website;

use RuntimeException;

readonly class Cache
{
    public function __construct(
        public string $projectBase,
    ) {
        //
    }

    public function __invoke(string $path, Response $response): Response
    {
        return $response->decorate(function ($response) use ($path): string {
            $path = "{$this->projectBase}/public{$path}/index.html";

            $content = is_file($path)
                ? file_get_contents($path)
                : false;

            if ($content === false) {
                if (! is_dir($directory = dirname($path))) {
                    $result = mkdir($directory, permissions: 0755, recursive: true);

                    if (! $result) {
                        throw new RuntimeException("Unable to create directory [{$directory}].");
                    }
                }

                $result = file_put_contents($path, $content = $response->render());

                if (! $result) {
                    throw new RuntimeException("Unable to put file contents [{$path}].");
                }
            }

            return $content;
        });
    }
}
