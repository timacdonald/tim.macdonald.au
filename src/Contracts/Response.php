<?php

namespace TiMacDonald\Website\Contracts;

use Closure;

interface Response
{
    public function status(): int;

    public function render(): string;

    public function withStatus(int $status): self;

    public function decorate(Closure $callback): self;
}
