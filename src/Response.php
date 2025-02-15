<?php

namespace TiMacDonald\Website;

use Closure;
use TiMacDonald\Website\Contracts\Response as ResponseContract;

readonly class Response implements ResponseContract
{
    /**
     * @param  (Closure(): string)  $callback
     * @param  array<string, string>  $headers
     */
    public function __construct(
        private Closure $callback,
        private int $status = 200,
        private array $headers = [],
    ) {
        //
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function render(): string
    {
        return call_user_func($this->callback);
    }
}
