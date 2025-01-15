<?php

namespace TiMacDonald\Website;

readonly class E
{
    public function __invoke(string $content): void
    {
        echo htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
