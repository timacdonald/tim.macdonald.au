<?php

namespace TiMacDonald\Website;

readonly class Page
{
    public function __construct(
        public Template $template,
        public string $image,
        public string $title,
        public string $description,
        public OgType $ogType,
        public bool $showMenu = true,
        public bool $hidden = false,
    ) {
        //
    }
}
