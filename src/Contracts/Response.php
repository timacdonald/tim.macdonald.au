<?php

namespace TiMacDonald\Website\Contracts;

interface Response
{
    public function status(): int;

    public function render(): string;
}
