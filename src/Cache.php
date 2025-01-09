<?php

namespace TiMacDonald\Website;

class Cache
{
    public function __construct(
        public string $basePath,
    ) {
        //
    }

    public function __invoke(string $path, Response $response): Response
    {
        return $response->decorate(function ($response) use ($path): string {
            $path = "{$this->basePath}/public{$path}/index.html";

            $content = is_file($path)
                ? file_get_contents($path)
                : false;

            if ($content === false || (bool) getenv('LOCAL')) {
                if (! is_dir($directory = dirname($path))) {
                    mkdir($directory, permissions: 0755, recursive: true);
                }

                file_put_contents($path, $content = $response->render());
            }

            return $content;
        });
    }
}
