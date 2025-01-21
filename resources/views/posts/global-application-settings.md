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
    title: 'Global application settings',
    description: "In applications it is often useful to have a way to store some global settings. This post outlines my approach using a Spatie package to manage the values instead of Eloquent.",
    date: new DateTimeImmutable('@1543327200', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('collection-voices.png'),
);

?>

Read the full guest article on [Laravel News](https://laravel-news.com/global-application-settings).
