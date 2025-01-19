<?php

namespace TiMacDonald\Website;

use Closure;

class Template
{
    public function __construct(
        private string $projectBase,
        private Closure $props,
    ) {
        //
    }

    public function __invoke(string $name, array $props = []): void
    {
        $__path = "{$this->projectBase}/resources/views/templates/{$name}.php";
        $__props = [
            ...$props,
            ...call_user_func($this->props),
            'template' => $this,
        ];

        call_user_func(static function () use ($__path, $__props): void {
            extract($__props);

            require $__path;
        });
    }
}
