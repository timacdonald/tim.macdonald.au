<?php

namespace TiMacDonald\Website;

use Closure;
use TiMacDonald\Website\Contracts\Response as ResponseContract;

readonly class Response implements ResponseContract
{
    /**
     * @param  (Closure(): string)  $callback
     */
    public function __construct(
        private Closure $callback,
        private int $status = 200,
    ) {
        //
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): string
    {
        return call_user_func($this->callback);
    }

    public function withStatus(int $status): self
    {
        return new self($this->callback, $status);
    }

    public function decorate(Closure $callback): self
    {
        return new Response(fn (): string => $callback($this), $this->status);
    }
}
