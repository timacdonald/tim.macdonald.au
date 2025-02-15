<?php

namespace TiMacDonald\Website\Contracts;

interface Response
{
    public function status(): int;

    /**
     * @return array<string, string>
     */
    public function headers(): array;

    public function render(): string;
}
