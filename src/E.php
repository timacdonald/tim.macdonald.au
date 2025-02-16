<?php

namespace TiMacDonald\Website;

readonly class E
{
    public function __invoke(string $content): void
    {
        echo $this->escape($content);
    }

    public function escape(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
