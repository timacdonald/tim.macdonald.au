<?php

namespace TiMacDonald\Website;

use RuntimeException;
use TiMacDonald\Website\Contracts\Response;

readonly class CachedResponse implements Response
{
    public function __construct(
        public string $projectBase,
        public Request $request,
        public Response $response,
    ) {
        //
    }

    public function status(): int
    {
        return $this->response->status();
    }

    public function render(): string
    {
        $path = "{$this->projectBase}/public{$this->request->path}/index.html";

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

            $result = file_put_contents($path, $content = $this->response->render());

            if (! $result) {
                throw new RuntimeException("Unable to put file contents [{$path}].");
            }
        }

        return $content;
    }
}
