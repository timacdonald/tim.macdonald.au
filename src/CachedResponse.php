<?php

namespace TiMacDonald\Website;

use RuntimeException;
use TiMacDonald\Website\Contracts\Response;

class CachedResponse implements Response
{
    private bool $cacheMiss = false;

    public function __construct(
        public readonly string $projectBase,
        public readonly Request $request,
        public readonly Response $response,
    ) {
        //
    }

    public function status(): int
    {
        return $this->response->status();
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [
            ...$this->response->headers(),
            'Cache-Miss' => $this->cacheMiss ? '1' : '0',
        ];
    }

    public function render(): string
    {
        $extension = pathinfo($this->request->path, PATHINFO_EXTENSION);

        if ($extension) {
            $path = "{$this->projectBase}/public{$this->request->path}";
        } else {
            $path = "{$this->projectBase}/public{$this->request->path}/index.html";
        }

        $content = is_file($path)
            ? file_get_contents($path)
            : false;

        if ($content === false) {
            $this->cacheMiss = true;

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
