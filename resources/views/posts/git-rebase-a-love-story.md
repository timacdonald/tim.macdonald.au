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
    title: 'Git rebase: A love story',
    description: "A (not so lightning) talk about git rebase and the things it enables.",
    date: new DateTimeImmutable('@1627534985', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('rebase.png'),
    format: Format::Video,
);

?>

@include('_partials.youtube_video', ['id' => 'INjj0eGhNXs'])

<div class="text-center mt-4">
    <a href="https://timacdonald.notion.site/git-rebase-a-love-story-d6f158ca41014725a39c98e1c32cbbd5">
        Talk slides
    </a>
</div>

At work I decided to show git rebase the love it deserves with a brown bag session and had a lot of fun doing it so thought I'd record it and share it publicly. Just a casual stream where I dive into all the great things you can do with rebase...but honestly, it's just be a lot of me fumbling around on a keyboard in front of everyone! (...hoping most of this is accurate 😬)
