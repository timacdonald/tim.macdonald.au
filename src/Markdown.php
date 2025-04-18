<?php

namespace TiMacDonald\Website;

use Michelf\MarkdownExtra;

readonly class Markdown
{
    public function __invoke(string $content): string
    {
        /**
         * There is currently a deprecation error in the Markdown Parser.
         * This line fixes the issue by casting `null` to a string. Could
         * probably move this to a deployment script.
         *
         * @see https://github.com/michelf/php-markdown/pull/365
         */
        system('sed -i "232s/, \$attr, /, (string) \$attr, /g" ../vendor/michelf/php-markdown/Michelf/MarkdownExtra.php');

        $parser = new MarkdownExtra;
        $parser->code_class_prefix = 'language-';
        $parser->header_id_func = static function (string $heading): string {
            return preg_replace('/[^a-z0-9]/', '-', mb_strtolower($heading)) ?? '';
        };

        return $parser->transform($content);
    }
}
