<?php

use TiMacDonald\Website\Format;
use TiMacDonald\Website\Page;

/**
 * Props.
 *
 * @var string $projectBase
 * @var \TiMacDonald\Website\Request $request
 * @var \TiMacDonald\Website\Url $url
 * @var (callable(string): void) $e
 * @var \TiMacDonald\Website\Markdown $markdown
 * @var \TiMacDonald\Website\Collection $collection
 */

// ...

$page = Page::fromPost(
    file: __FILE__,
    title: "Sharing PHP-CS-Fixer rules across projects and teams",
    description: "This tutorial will show you how you can setup a repo that contains all your rules, and easily share them with others.",
    date: new DateTimeImmutable('@1588086000', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('rethinking-middleware.png'),
    externalLink: 'https://laravel-news.com/sharing-php-cs-fixer-rules-across-projects-and-teams',
);

?>

Read the full article on [Laravel News]({{ $page->external_link }}).
