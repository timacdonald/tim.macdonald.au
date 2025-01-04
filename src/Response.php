<?php

namespace TiMacDonald\Website;

use Closure;

readonly class Response
{
    /**
     * @param  (Closure(): string)  $callback
     */
    public function __construct(
        private Closure $callback,
        public int $status = 200,
    ) {
        //
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
