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
    title: 'Follow the Eloquent road',
    description: "Conference talk given at the 2020 LaraconUS / Online conference. Come for a journey as I show you down the path I've taken along the Eloquent road.",
    date: new DateTimeImmutable('@1613947760', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('yellow-brick.png'),
    format: Format::Video,
);

?>

<?php $template('vimeo', ['id' => '452363091']); ?>

<div class="text-center mt-4">
    <a href="<?php $e($url->to('talks/follow-the-eloquent-road')); ?>">
        Talk slides
    </a>
</div>

This talk was given at the 2020 LaraconUS / Online conference. You can find all the talks from previous years in the [Laracon Online showcase](https://vimeo.com/showcase/laracononline).

## Abstract

When you take the Eloquent road for the first time, it's easy to pour everything into a model...but I think if you follow the road just a little further, you'll find you're guided in a way that makes your app feel more focused, maintainable, and enjoyable. Everything, you'll find, has it's own home.

Under the hood Eloquent is an interconnected web of functionality all working to power the model - but by embracing the interconnected nature of Eloquent, and wielding the power of it yourself, you'll not only find new and interesting ways of structuring your projects, but also new conventions that were hiding just out of sight. All this without the need to abandon the "Laravel way".

Through a handful of tips, I will give you a fresh take on Eloquent within your application and by the end of the talk I'll have changed your mind about what Eloquent is - and more importantly - where Eloquent leads you.
