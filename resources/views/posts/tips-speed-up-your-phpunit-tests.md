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
    title: "Tips to speed up your PHPUnit tests",
    description: "Having a fast test suite can be just as important as having a fast application. Here are some ways to make your test suite run faster.",
    date: new DateTimeImmutable('@1546956000', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('laravel-testcase.png'),
    externalLink: 'https://laravel-news.com/tips-to-speed-up-phpunit-tests',
);

?>

Read the full article on [Laravel News](https://laravel-news.com/tips-to-speed-up-phpunit-tests).
